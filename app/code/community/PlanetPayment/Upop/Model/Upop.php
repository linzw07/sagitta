<?php

/**
 * One Pica
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to codemaster@onepica.com so we can send you a copy immediately.
 * 
 * @category    PlanetPayment
 * @package     PlanetPayment_Upop
 * @copyright   Copyright (c) 2012 Planet Payment Inc. (http://www.planetpayment.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Planet Payment
 *
 * @category   PlanetPayment
 * @package    PlanetPayment_Upop
 * @author     One Pica Codemaster <codemaster@onepica.com>
 */
class PlanetPayment_Upop_Model_Upop extends Mage_Payment_Model_Method_Cc {

    const GATEWAY_URL_PRODUCTION = 'https://prd.txngw.com';
    const GATEWAY_URL_TESTING = 'http://uap.txngw.com';
    const PAYMENT_ACTION_AUTHORIZE = "authorize";
    const PAYMENT_ACTION_AUTHORIZE_CAPTURE = "authorize_capture";
    const PAYMENT_SERVICE_PYC = "pyc";
    const PAYMENT_SERVICE_MCP = "mcp";
    const PAYMENT_SERVICE_NORMAL = "normal";
    const VALIDATE_EXISTING = 1;
    const VALIDATE_NEW = 2;
    const METHOD_CODE = 'upop';
    // Xmls wil be logged in this file if any error occures while authorising,
    // ie the authorize process is wrapped inside a mysql transaction in magento, 
    // if anything goes wrong,
    // The complete process will be rolled back. Since our database logging
    // is inside this transaction, we will loose the log.
    const LOG_FILE = 'upop_log.txt';

    /**
     * Hold the Upop Payment Form Block
     * 
     * @var string
     */
    protected $_formBlockType = 'upop/payment_form';

    /**
     * Hold The Admin payment Form For Upop
     * 
     * @var string
     */
    protected $_formBlockTypeAdmin = 'upop/adminhtml_payment_form';

    /**
     * Hold Payment Method Code
     * 
     * @var constant
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Hold Upop Payment Info Block
     * 
     * @var string
     */
    protected $_infoBlockType = 'upop/payment_info';

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canUseInternal = false;  // enables admin use

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canUseCheckout = true;  // enables frontend use

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canUseForMultishipping = true;

    /**
     * Hold Bool Value
     * 
     * @var bool
     */
    protected $_canSaveCc = true;

    /**
     * Hold Null Value
     * 
     * @var empty
     */
    protected $_redirectParam = '';

    /**
     * Assigning data
     * 
     * @param string $data
     * @return PlanetPayment_Upop_Model_Upop 
     */
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        parent::assignData($data);
        $this->getInfoInstance()
                ->setIsVisible($data->getIsVisible());
        $this->setValidationMode(self::VALIDATE_NEW);
        return $this;
    }

    /**
     * Capture previously Athorized payment
     * 
     * @param Varien_Object $payment
     * @param int $amount 
     */
    public function capture(Varien_Object $payment, $amount) {
        try {
            if (!$payment->getTransactionId() && $this->getConfigData('payment_action') == self::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
                return $this->sale($payment, $amount);
            }

            $payment->setAmount($amount);
            $request = $this->_getRequest();
            $request->setPayment($payment)
                    ->setAmount($amount)
                    ->setAmountInStoreCurrency(round($payment->getOrder()->getGrandTotal(), 2));

            $request->generateRequestForCapture();
            $request->send();
            $response = $request->getResponse();

            $result = $response->getXmlContent();

            if ($result->FIELDS->RESPONSE_TEXT == 'Approved') {
                $this->_redirectParam =(string)$result->FIELDS->UP_PAYLOAD;
                $session = $this->_getSession();
                $session->setRedirectParam('' . $this->_redirectParam);
                $payment->setLastTransId((string)$result->FIELDS->TRANSACTION_ID);
                if (!$payment->getParentTransactionId() || (string)$result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
                    $payment->setTransactionalId((string)$result->FIELDS->TRANSACTION_ID);
                }
                $payment->setIsFraudDetected(false); //to set the fraud detection false
            } else {
                Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
                if ((string)$result->FIELDS->ARC == 'PF') {
                    Mage::throwException(Mage::helper('upop')->__("Please Retry to Capture"));
                } else {
                    Mage::log("Couldn't process your request. Please try again later or contact us", null, self::LOG_FILE, true);
                    Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later or contact us"));
                }
            }

            if ($response->isSuccess()) {
                $result = $response->getXmlContent();
                $payment->setStatus(self::STATUS_APPROVED);
                //$payment->setCcTransId($result->getTransactionId());
                $payment->setLastTransId((string)$result->FIELDS->TRANSACTION_ID);
                if (!$payment->getParentTransactionId() || (string)$result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
                    $payment->setTransactionId((string)$result->FIELDS->TRANSACTION_ID);
                }
            } else {
                Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
                if ((string)$result->FIELDS->ARC == 'PF') {
                    Mage::log("Please Retry to Capture", null, self::LOG_FILE);
                    Mage::throwException(Mage::helper('upop')->__("Please Retry to Capture"));
                } else {
                    Mage::log("Couldn't process your request. Please try again later or contact us", null, self::LOG_FILE);
                    Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later or contact us"));
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
            Mage::throwException(Mage::helper('upop')->__($e->getMessage()));
        }
    }

    /**
     * Capture previously athorized payment
     * 
     * @param Varien_Object $payment
     * @param type $amount 
     */
    /* public function capture(Varien_Object $payment, $amount) {
      try {
      if (!$payment->getTransactionId() && $this->getConfigData('payment_action') == self::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
      return $this->sale($payment, $amount);
      }

      $payment->setAmount($amount);
      $request = $this->_getRequest();
      $request->setPayment($payment)
      ->setAmount($amount)
      ->setAmountInStoreCurrency(round($payment->getOrder()->getGrandTotal(), 2));

      $request->generateRequestForCapture();
      $request->send();
      $response = $request->getResponse();

      $result = $response->getXmlContent();

      if ($result->FIELDS->RESPONSE_TEXT == 'Approved') {
      $this->_redirectParam = $result->FIELDS->UP_PAYLOAD;
      $session = $this->_getSession();
      $session->setRedirectParam('' . $this->_redirectParam);
      $payment->setLastTransId($result->FIELDS->TRANSACTION_ID);
      if (!$payment->getParentTransactionId() || $result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
      $payment->setTransactionalId($result->FIELDS->TRANSACTION_ID);
      }
      $payment->setIsFraudDetected(false); //to set the fraud detection false
      } else {
      Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
      if ($result->FIELDS->ARC == 'PF') {
      Mage::throwException(Mage::helper('upop')->__("Please Retry to Capture"));
      } else {
      Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later or contact us"));
      }
      }

      if ($response->isSuccess()) {
      $result = $response->getXmlContent();
      $payment->setStatus(self::STATUS_APPROVED);
      //$payment->setCcTransId($result->getTransactionId());
      $payment->setLastTransId($result->FIELDS->TRANSACTION_ID);
      if (!$payment->getParentTransactionId() || $result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
      $payment->setTransactionId($result->FIELDS->TRANSACTION_ID);
      }
      } else {
      Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
      if ($result->FIELDS->ARC == 'PF') {
      Mage::throwException(Mage::helper('upop')->__("Please Retry to Capture"));
      } else {
      Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later or contact us"));
      }
      }
      } catch (Exception $e) {
      Mage::throwException(Mage::helper('upop')->__($e->getMessage()));
      }
      } */

    /**
     * Authorize and Capture
     * 
     * @param Varien_Object $payment
     * @param int $amount 
     */
    public function sale(Varien_Object $payment, $amount) {
        try {
            $secondpassdata = $this->_getSessionUpop()->getData('second_pass');
            if (is_null($secondpassdata)) {
                Mage::throwException(Mage::helper('upop')->__("Session is timeout for second pass auth/sale with iPay."));
            }
            $quoteCurrency = Mage::helper('upop')->getQuote()->getQuoteCurrencyCode();
            $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode(); 
            $secondpassdata_xml_obj = simplexml_load_string($secondpassdata);
            if ($secondpassdata_xml_obj instanceof SimpleXMLElement) {
                $transaction_id_last =(string)$secondpassdata_xml_obj->FIELDS->TRANSACTION_ID;
                $transaction_id = (string)$secondpassdata_xml_obj->FIELDS->TRANSACTION_ID;
                $upop_order_number = (string)$secondpassdata_xml_obj->FIELDS->UP_ORDERNUMBER;
                $paymentType = $this->getPaymentType();
                $exchangeRate = ($paymentType == self::PAYMENT_SERVICE_PYC) ? (1 / (double)$secondpassdata_xml_obj->FIELDS->PYC_EXCHANGE_RATE) : Mage::helper('upop')->getQuote()->getBaseToQuoteRate();
                $payment->setAmount($amount);
                $payment->setLastTransId($transaction_id_last)
                        ->setTransactionId($transaction_id)
                        ->setUpopOrderNumber($upop_order_number)
                        ->setUpopExchangeRate($exchangeRate)
                        ->setUpopCurrencyCode($currency_code)
                        ->setIsTransactionClosed(0);
                $payment->setStatus(self::STATUS_APPROVED);
                $payment->setIsFraudDetected(false);
            } else {
                Mage::log("Second pass auth/sale response in not valid.", null, self::LOG_FILE);
                Mage::throwException(Mage::helper('upop')->__("Second pass auth/sale response in not valid."));
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
            Mage::throwException(Mage::helper('upop')->__($e->getMessage()));
        }
    }

    /**
     * Authorize and Capture
     * 
     * @param Varien_Object $payment
     * @param type $amount 
     */
    /* public function sale(Varien_Object $payment, $amount) {
      try {
      $payment->setAmount($amount);
      $request = $this->_getRequest();
      $request->setPayment($payment)
      ->setAmount($amount)
      ->setAmountInStoreCurrency(round($payment->getOrder()->getGrandTotal(), 2));

      $request->generateRequestForSale();
      $request->send();

      $response = $request->getResponse();
      $result = $response->getXmlContent();

      if ($result->FIELDS->UP_PAYLOAD) {
      $this->_redirectParam = $result->FIELDS->UP_PAYLOAD;
      $session = $this->_getSession();
      $session->setRedirectParam('' . $this->_redirectParam);
      $session->setOrderId($payment->getOrder()->getId());

      $exchangeRate = ($paymentType == self::PAYMENT_SERVICE_PYC) ? (1 / $result->FIELDS->PYC_EXCHANGE_RATE) : Mage::helper('upop')->getQuote()->getBaseToQuoteRate();
      $payment->setUpopOrderNumber($result->FIELDS->UP_ORDERNUMBER)
      ->setUpopExchangeRate($exchangeRate)
      ->setIsTransactionPending(true)
      ->setIsTransactionClosed(0);
      $payment->setLastTransId($result->FIELDS->TRANSACTION_ID);
      if (!$payment->getParentTransactionId() || $result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
      $payment->setTransactionId($result->FIELDS->TRANSACTION_ID);
      }
      } else {
      Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
      Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later or contact us"));
      }
      } catch (Exception $e) {
      Mage::throwException(Mage::helper('upop')->__($e->getMessage()));
      }
      } */

    /**
     * Authorizing payment
     * 
     * @param Varien_Object $payment
     * @param int $amount
     * @return PlanetPayment_Upop_Model_Upop 
     */
    public function authorize(Varien_Object $payment, $amount) {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('upop')->__('Invalid amount for authorization.'));
        }

        try {
            $secondpassdata = $this->_getSessionUpop()->getData('second_pass');
            if (is_null($secondpassdata)) {
                Mage::throwException(Mage::helper('upop')->__("Session is timeout for second pass auth/sale with iPay."));
            }
            $quoteCurrency = Mage::helper('upop')->getQuote()->getQuoteCurrencyCode();
            $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode(); 
            $secondpassdata_xml_obj = simplexml_load_string($secondpassdata);
            if ($secondpassdata_xml_obj instanceof SimpleXMLElement) {
                $transaction_id_last = (string)$secondpassdata_xml_obj->FIELDS->TRANSACTION_ID;
                $transaction_id = (string)$secondpassdata_xml_obj->FIELDS->TRANSACTION_ID;
                $upop_order_number = (string)$secondpassdata_xml_obj->FIELDS->UP_ORDERNUMBER;
                $paymentType = $this->getPaymentType();
                $exchangeRate = ($paymentType == self::PAYMENT_SERVICE_PYC) ? (1 / (double)$secondpassdata_xml_obj->FIELDS->EXCHANGE_RATE) : Mage::helper('upop')->getQuote()->getBaseToQuoteRate();
                $payment->setAmount($amount);
                $payment->setLastTransId($transaction_id)
                        ->setTransactionId($transaction_id_last)
                        ->setUpopOrderNumber($upop_order_number)
                        ->setUpopExchangeRate($exchangeRate)
                        ->setUpopCurrencyCode($currency_code)
                        ->setIsTransactionClosed(0);
                //$payment->setStatus(self::STATUS_APPROVED);
                $payment->setIsFraudDetected(false); //to set the fraud detection false
            } else {
                Mage::log("Second pass auth/sale response from iPay in not valid.", null, self::LOG_FILE);
                Mage::throwException(Mage::helper('upop')->__("Second pass auth/sale response from iPay in not valid."));
            }
        } catch (Exception $e) {
            Mage::log('Payment authorization error.', null, self::LOG_FILE);
            //Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
            Mage::throwException(Mage::helper('upop')->__('Payment authorization error.'));
        }

        return $this;
    }

    /**
     * Authorizing payment
     * 
     * @param Varien_Object $payment
     * @param type $amount
     * @return PlanetPayment_Upop_Model_Upop 
     */
    /* public function authorize(Varien_Object $payment, $amount) {
      if ($amount <= 0) {
      Mage::throwException(Mage::helper('upop')->__('Invalid amount for authorization.'));
      }

      $payment->setAmount($amount);
      $request = $this->_getRequest();
      $request->setTransactionType(self::PAYMENT_ACTION_AUTHORIZE)
      ->setPayment($payment)
      ->setAmount($amount);
      $paymentType = $this->getPaymentType();

      if ($paymentType == self::PAYMENT_SERVICE_PYC) {
      $request->generateRequestForPycAuth();
      } else if ($paymentType == self::PAYMENT_SERVICE_MCP) {
      $request->setAmountInStoreCurrency(round(Mage::helper('upop')->getQuote()->getGrandTotal(), 2));
      $request->generateRequestForMcpAuth();
      } else {
      Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try agin later."));
      }
      $request->send();

      $response = $request->getResponse();
      $result = $response->getXmlContent();

      if ($result->FIELDS->UP_PAYLOAD) {
      $this->_redirectParam = $result->FIELDS->UP_PAYLOAD;
      $session = $this->_getSession();
      $session->setRedirectParam('' . $this->_redirectParam);
      $session->setOrderId($payment->getOrder()->getId());
      $exchangeRate = $paymentType == self::PAYMENT_SERVICE_PYC ? (1 / $result->FIELDS->EXCHANGE_RATE) : Mage::helper('upop')->getQuote()->getBaseToQuoteRate();

      $order = $payment->getOrder();
      $order->setUpopOrderNumber($result->FIELDS->UP_ORDERNUMBER);

      $payment->setLastTransId($result->FIELDS->TRANSACTION_ID)
      ->setTransactionId($result->FIELDS->TRANSACTION_ID)
      ->setUpopOrderNumber($result->FIELDS->UP_ORDERNUMBER)
      ->setIsTransactionPending(true)
      ->setUpopExchangeRate($exchangeRate)
      ->setIsTransactionClosed(0);

      $payment->setIsFraudDetected(false); //to set the fraud detection false
      } else {
      //The last added log will be rolled back if error occured. So if any
      //exception occured, the xmls will be logged in var/log/upop_log.txt
      Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
      Mage::throwException(Mage::helper('upop')->__('Payment authorization error.'));
      }

      return $this;
      } */

    /**
     * Void the payment through gateway
     *
     * @param Varien_Object $payment
     * @return PlanetPayment_Upop_Model_Upop
     */
    public function void(Varien_Object $payment) {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        if ($payment->getParentTransactionId()) {
            $request = $this->_getRequest();
            $request->setPayment($payment);

            $request->generateRequestForVoid()
                    ->send();

            $response = $request->getResponse();
            $result = $response->getXmlContent();

            if ($response->isSuccess()) {
                $payment->setStatus(self::STATUS_SUCCESS);
                return $this;
            } else {
                $payment->setStatus(self::STATUS_ERROR);
                Mage::log($response->getMessage(), null, self::LOG_FILE);
                Mage::throwException(Mage::helper('upop')->__('Error in void the payment. Message:' . $response->getMessage()));
            }
        } else {
            $payment->setStatus(self::STATUS_ERROR);
            Mage::log("Invalid transaction id", null, self::LOG_FILE);
            Mage::throwException(Mage::helper('upop')->__('Invalid transaction id'));
        }
    }

    /**
     * Refund the amount with transaction id
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return PlanetPayment_Upop_Model_Upop
     * @todo Ensure  removal of  -capture in logs
     */
    public function refund(Varien_Object $payment, $amount) {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        if ($payment->getRefundTransactionId() && $amount > 0) {
            if ($amount == $payment->getBaseAmountPaid()) {
                //avoid a rounding error
                $amountConverted = round($payment->getAmountPaid(), 2);
            } else {
                //calculate the amount based on the payment exchange rate
                $amountConverted = round($amount * $payment->getUpopExchangeRate(), 2);
            }

            $request = $this->_getRequest();
            $request->setPayment($payment)
                    ->setAmount($amount)
                    ->setAmountInStoreCurrency($amountConverted);

            $request->generateRequestForRefund()
                    ->send();

            $response = $request->getResponse();
            $result = $response->getXmlContent();

            if ($response->isSuccess()) {
                $payment->setStatus(self::STATUS_SUCCESS);
                return $this;
            } else {
                $payment->setStatus(self::STATUS_ERROR);
                Mage::log($response->getMessage(), null, self::LOG_FILE);
                Mage::throwException(Mage::helper('upop')->__('Error in refunding the payment. Message:' . $response->getMessage()));
            }
        }
        Mage::log("Error in refunding the payment", null, self::LOG_FILE);
        Mage::throwException(Mage::helper('upop')->__('Error in refunding the payment'));
    }

    /**
     * Second-Pass request payment
     * 
     * @param Varien_Object $payment
     * @param Array $param
     * @return PlanetPayment_Upop_Model_Upop 
     */
    public function secondPass(Varien_Object $payment, $param) {

        $order = $payment->getOrder();
        $upPayload64 = http_build_query($param);
        /* $upPayload = '';
          foreach ($param as $key => $value) {
          if ($key == 'respMsg') {
          $value = $this->strToHex($value); //up_payload diable hex decimal
          }

          if ($key != 'signature') {
          $upPayload .= ($upPayload ? '&' : '') . $key . '=' . $value; //up_payload diable signature
          }
          }
          if (isset($param['signature'])) {
          $upPayload .= ($upPayload ? '&' : '') . 'signature=' . $param['signature'];
          } */

        if ($this->getConfigData('payment_action') == self::PAYMENT_ACTION_AUTHORIZE) {
            $transactionalType = 'AUTH';
        } else if ($this->getConfigData('payment_action') == self::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
            $transactionalType = 'SALE';
        }

        $paymentType = $this->getPaymentType();

        $request = $this->_getRequest();
        $request->setPayment($payment);
        $request->setTransactionType($transactionalType);
        //$request->setUpPayload($upPayload);
        $request->setUpPayload64(base64_encode($upPayload64));
        $request->setParam($param);
        $request->generateSecondRequest();
        $request->send();

        $response = $request->getResponse();
        $result = $response->getXmlContent();

        if ($result->FIELDS->RESPONSE_TEXT == 'Approved') {

            $order->setData('dbt_status', '1'); //to add dbt status
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
            $status = true;
            $amount = round($payment->getAmountOrdered(), 2);
            $formatedPrice = $order->getBaseCurrency()->formatTxt($amount);
            $transactionId = $result->FIELDS->TRANSACTION_ID;
            $orderTransactionId = $payment->getLastTransId();

            if ($transactionalType == 'SALE') {
                $message = Mage::helper('upop')->__('Capturing amount of %s is approved.', $formatedPrice);
            } else if ($transactionalType == 'AUTH') {
                $message = Mage::helper('upop')->__('Authorized amount of %s.', $formatedPrice);
            } else {
                Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
                Mage::throwException(Mage::helper('upop')->__('Payment second-pass error.'));
            }

            $exchangeRate = $paymentType == self::PAYMENT_SERVICE_PYC ? (1 / $result->FIELDS->EXCHANGE_RATE) : Mage::helper('upop')->getQuote()->getBaseToQuoteRate();
            $payment->setLastTransId($transactionId);
            if ($transactionalType == 'AUTH') {
                $payment->setTransactionId($transactionId);
                $payment->setParentTransactionId($orderTransactionId);
            } else {
                if (!$payment->getParentTransactionId() || $result->FIELDS->TRANSACTION_ID != $payment->getParentTransactionId()) {
                    $payment->setTransactionId($transactionId);
                }
            }
            $payment->setUpopOrderNumber($result->FIELDS->UP_ORDERNUMBER);
            $payment->setUpopExchangeRate($result->FIELDS->EXCHANGE_RATE);
            $payment->setIsTransactionClosed(0);
            $payment->setStatus(self::STATUS_APPROVED);
            $payment->setIsFraudDetected(false); //to set the fraud detection false

            if ($transactionalType == 'AUTH') {
                $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false, $message);
                $transaction->setParentTxnId($orderTransactionId);
                $transaction->setIsClosed(0);
                $transaction->save();
            } else if ($transactionalType == 'SALE') {
                if ($order->hasInvoices()) {
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoice->setTransactionId($transactionId);
                        $invoice->pay()->save();
                        break;
                    }
                }
            }
            $message = Mage::helper('upop')->__('Order status was change.');
            $order->setState($state, true, $message)->save();
        } else {
            $order->setData('dbt_status', '0'); //to add response for dbt query
            //The last added log will be rolled back if error occured. So if any
            //exception occured, the xmls will be logged in var/log/upop_log.txt
            Mage::log($response->getLogInfo(), null, self::LOG_FILE, true);
            Mage::throwException(Mage::helper('upop')->__('Payment error.'));
        }

        return $this;
    }

    /**
     * Identifying the payment type PYC or MCP
     * 
     * @return string
     */
    public function getPaymentType() {
        $typeConfig = $this->getConfigData("service");
        $nativeCurrency = $this->getConfigData("currency");
        $quoteCurrency = Mage::helper('upop')->getQuote()->getQuoteCurrencyCode();
        $acceptedCurrencies = explode(",", $this->getConfigData("accepted_currencies"));

        if ($typeConfig == self::PAYMENT_SERVICE_PYC) {
            if ($quoteCurrency == $nativeCurrency) {
                return self::PAYMENT_SERVICE_PYC;
            }
        } else if (in_array($quoteCurrency, $acceptedCurrencies)) {
            return self::PAYMENT_SERVICE_MCP;
        }

        return self::PAYMENT_SERVICE_NORMAL;
    }

    /**
     * Retrieve block type for method form generation
     *
     * @return string
     */
    public function getFormBlockType() {
        if (Mage::app()->getStore()->isAdmin()) {
            return $this->_formBlockTypeAdmin;
        } else {
            return $this->_formBlockType;
        }
    }

    /**
     * returns the Xml request object
     * 
     * @return  object PlanetPayment_Upop_Model_Xml_Request
     */
    protected function _getRequest() {
        return Mage::getmodel('upop/xml_request');
    }

    /**
     * Returns checkout session
     * 
     * @return  Mage_Checkout_Model_Session
     */
    protected function _getSession() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Returns UPOP session
     * 
     * @return PlanetPayment_Upop_Model_Session 
     */
    protected function _getSessionUpop() {
        return Mage::getSingleton('upop/session');
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate() {
        /*
         * calling parent validate function
         */
        if ($this->getValidationMode() == self::VALIDATE_NEW) {

            //This must be after all validation conditions
            if ($this->getIsCentinelValidationEnabled()) {
                $this->getCentinelValidator()->validate($this->getCentinelValidationData());
            }
        } else {
            Mage_Payment_Model_Method_Abstract::validate();
        }

        return $this;
    }

    /**
     * Convert string to Hexadecimal
     * 
     * @param string $string
     * @return string
     */
    public function strToHex($string) {
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $hex .= '%' . dechex(ord($string[$i]));
        }
        return $hex;
    }

    /**
     * Redirect url to ogone submit form
     *
     * @return string
     */
    /* public function getOrderPlaceRedirecgtUrl() {
      return Mage::getUrl('upop/onepage/redirect', array('_secure' => true));
      } */

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     * 
     * @see Mage_Checkout_OnepageController::savePaymentAction()
     * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl() {
        return Mage::getUrl('upop/index/start');
    }

    /**
     * Cancel payment
     * 
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Paypal_Model_Express
     */
    public function cancel(Varien_Object $payment) {
        $this->void($payment);

        return $this;
    }

    /**
     * Send UPOP authorization debit status query
     * 
     * @param int $id
     */
    public function getDbtStatus($id) {
        $order = Mage::getModel('sales/order')->load($id);
        if (!$order->getId()) {
            return false;
        }
        $request = $this->_getRequest();
        $request->setOrder($order);
        try {
            $request->generateRequestForDBT();
            $request->send();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE, true);
            Mage::throwException($e->getMessage());
        }

        $response = $request->getResponse();
        $result = $response->getXmlContent();
        return $result;
    }

}
