<?php

/**
 * Planet Payment
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * @category    PlanetPayment
 * @package     PlanetPayment_Upop
 * @copyright   Copyright (c) 2012 Planet Payment Inc. (http://www.planetpayment.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Hold Method For Starting Checkout Process
 * 
 * @category PlanetPayment
 * @package PlanetPayment_Upop
 * @author mohds
 */
class PlanetPayment_Upop_Model_Checkout {

    /**
     * Hold Instance of Upop Request Model
     *
     * @var string
     */
    protected $_apiType = 'upop/xml_request';

    /**
     * Hold Upop Model Type
     *
     * @var string
     */
    protected $_upopType = 'upop/upop';

    /**
     * Hold Instance of Upop Model
     * 
     * @var PlanetPayment_Upop_Model_Upop
     */
    protected $_upop;

    /**
     * Hold Instance of Request Model Api
     * 
     * @var PlanetPayment_Upop_Model_Xml_Request
     */
    protected $_api;

    /**
     * Hold Instance Of Quote Model
     * 
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     *  Hold Instance Of Customer Model
     * 
     * @var Mage_Customer_Model_Session
     */
    protected $_customerSession;

    /**
     * Create Billing Agreement flag
     *
     * @var bool
     */
    protected $_isBARequested = false;

    /**
     * Customer ID
     *
     * @var int
     */
    protected $_customerId = null;

    /**
     * Recurring payment profiles
     *
     * @var array
     */
    protected $_recurringPaymentProfiles = array();

    /**
     * Billing agreement that might be created during order placing
     *
     * @var Mage_Sales_Model_Billing_Agreement
     */
    protected $_billingAgreement = null;

    /**
     * Order
     *
     * @var Mage_Sales_Model_QuoteMage_Sales_Model_Quote
     */
    protected $_order = null;

    /**
     * Set quote and config instances
     * 
     * @param array $params
     */
    public function __construct($params = array()) {
        if (isset($params['quote']) && $params['quote'] instanceof Mage_Sales_Model_Quote) {
            $this->_quote = $params['quote'];
        } else {
            throw new Exception('Quote instance is required.');
        }

        $this->_customerSession = Mage::getSingleton('customer/session');
    }

    /**
     * Send First Pass Authentication Request To Ipay 
     * 
     * @return \SimpleXMLElement|boolean
     */
    public function start() {
        $this->_quote->collectTotals();

        if (!$this->_quote->getGrandTotal() && !$this->_quote->hasNominalItems()) {
            Mage::throwException(Mage::helper('upop')->__('Upop does not support processing orders with zero amount. To complete your purchase, proceed to the standard checkout process.'));
        }

        //Prepare API
        $this->_getApi();

        //Instantiate UPOP model
        $this->_getUpop();
        $this->_api->setQuoteCurrencyCode($this->_quote->getQuoteCurrencyCode());

        $paymentType = $this->_upop->getPaymentType();
        $this->_api->setAmount($this->_quote->getBaseGrandTotal());

        if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC) {
            $this->_api->setUpopCurrencyCode($this->_quote->getQuoteCurrencyCode());
            $this->_api->generateApiRequestForPycAuth();
        } else if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_MCP) {
            $this->_api->setAmountInStoreCurrency($this->_quote->getGrandTotal());
            $this->_api->generateApiRequestForMcpAuth();
        } else {
            Mage::log("Couldn't process your request. Please try again later.", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException(Mage::helper('upop')->__("Couldn't process your request. Please try again later."));
        }

        // supress or export shipping address
        if ($this->_quote->getIsVirtual()) {
            $this->_api->setRequireBillingAddress(1);
            $this->_api->setSuppressShipping(true);
        } else {
            $address = $this->_quote->getShippingAddress();
            $isOverriden = 0;
            if (true === $address->validate()) {
                $isOverriden = 1;
                $this->_api->setAddress($address);
            }
            //$this->_quote->getPayment()->save();
        }

        // call first pass API and return uppayload
        $this->_api->send();
        $response = $this->_api->getResponse();
        $result = $response->getXmlContent();
        if ($result instanceof SimpleXMLElement) {
            $this->_getUopSession()->setData('first_pass', $result->asXML());
            $this->_quote->setData('first_pass', $result->asXML())->save();
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Send second pass request to Upop
     * 
     * @param Array $param
     * @return \SimpleXMLElement|boolean
     */
    public function secondPass($param) {
        try {
            $this->_getApi();
            $this->_api->setParam($param);

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
              }
             */
            $upPayload64 = http_build_query($param);
            $this->_getUpop();
            if ($this->_upop->getConfigData('payment_action') == PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE) {
                $transactionalType = 'AUTH';
            } else if ($this->_upop->getConfigData('payment_action') == PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
                $transactionalType = 'SALE';
            }

            $this->_api->setTransactionType($transactionalType);
            //$this->_api->setUpPayload($upPayload);
            $this->_api->setUpPayload64(base64_encode($upPayload64));
            $this->_api->generateSecondPassiPayRequest();
            $this->_api->send();

            $response = $this->_api->getResponse();
            $result = $response->getXmlContent();
            if ($result instanceof SimpleXMLElement) {
                $this->_getUopSession()->setData('second_pass', $result->asXML());
                $this->_quote->setData('second_pass', $result->asXML());
                return $result;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            Mage::log($ex->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($ex->getmessage());
        }
    }

    /**
     * Convert string to hexadecimal
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
     * UPOP session instance getter
     *
     * @return PlanetPayment_Upop_Model_Session
     */
    private function _getUopSession() {
        return Mage::getSingleton('upop/session');
    }

    /**
     * Return Request Model Intsance
     * 
     * @return PlanetPayment_Model_Xml_Request
     */
    protected function _getApi() {
        if (null === $this->_api) {
            $this->_api = Mage::getModel($this->_apiType);
        }
        return $this->_api;
    }

    /**
     * Return Upop Model Instance
     * 
     * @return PlanetPayment_Model_Upop
     */
    protected function _getUpop() {
        if (null === $this->_upop) {
            $this->_upop = Mage::getModel($this->_upopType);
        }
        return $this->_upop;
    }

    /**
     * Setter for customer with billing and shipping address changing ability
     *
     * @param  Mage_Customer_Model_Customer   $customer
     * @param  Mage_Sales_Model_Quote_Address $billingAddress
     * @param  Mage_Sales_Model_Quote_Address $shippingAddress
     * @return PlanetPayment_Model_Upop_Checkout
     */
    public function setCustomerWithAddressChange($customer, $billingAddress = null, $shippingAddress = null) {
        $this->_quote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);
        $this->_customerId = $customer->getId();
        return $this;
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sale_Model_Quote
     */
    private function _getQuote() {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

}
