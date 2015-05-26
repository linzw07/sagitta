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
class PlanetPayment_Upop_Model_Xml_Request extends PlanetPayment_Upop_Model_Xml_Abstract {

    /**
     * Returns CC Expiration date in Palnet Payment's Format
     * 
     * @param string $month
     * @param int $year
     * @return string
     */
    protected function _getCcExpiration($month, $year) {
        if (strlen($month) == 1) {
            $month = '0' . $month;
        }

        return (string) $month . substr($year, -2);
    }

    /**
     * Returns the response model object
     * 
     * @return object PlanetPayment_Upop_Model_Xml_Response 
     */
    protected function _getResponseModel() {
        return Mage::getModel('upop/xml_response');
    }

    /**
     * Returns the root node. Two options here, either get a simple xml root node
     * as a varien_simplexml object or get the complete encrypted request wrapped in
     * root node.
     * 
     * @param bool $afterEncrypt
     * @param string $encryptedXml
     * @return Varien_Simplexml_Element 
     */
    protected function _getRootNode($afterEncrypt = false, $encryptedXml = false) {
        $hasEncryption = $this->_hasEncryption();

        $key = $this->_getConfig('key', 'upop_general');
        $encryption = $hasEncryption ? '1' : '0';
        if ($afterEncrypt) {
            $rootNodeString = '<REQUEST KEY="' . $key . '" PROTOCOL="1" ENCODING="' . $encryption . '" FMT="1">' . $encryptedXml . '</REQUEST>';
        } else {
            $rootNodeString = '<REQUEST KEY="' . $key . '" PROTOCOL="1" ENCODING="' . $encryption . '" FMT="1"/>';
        }

        return new Varien_Simplexml_Element($rootNodeString);
    }

    /**
     * Condition the address text passed in to be limited to 30 characters
     * 
     * @param string $text
     * * @return string
     */
    protected function _conditionAddress($text) {
        if (!$text || $text == "" || strlen($text) <= 30) {
            return $text;
        }

        /**
         * Two instances of calls in this file used to call htmlentities on the
         * value, which I don't think we actually want. HTML Encoding will only
         * make the values longer...
         */
        //htmlentities($profile->getAddress(), ENT_QUOTES);
        return substr($text, 0, 30);
    }

    /**
     * If postal code is longer than 9 characters, strip to the first five. This
     * handles U.S. formatted postal codes inserted with a hyphen (i.e. 12345-6789)
     *
     * In that case, 12345 will be returned. Any text shorter than 9 characters
     * passed to this function will be returned unchanged.
     * @param string $text
     * @return string 
     */
    protected function _conditionPostalCode($text) {
        if (!$text || $text == "" || strlen($text) <= 9) {
            return $text;
        }

        return substr($text, 0, 5);
    }

    /**
     * Generates the Transaction Xml for authorization
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    /* public function generateRequestForPycAuth() {
      try {
      $payment = $this->getPayment();
      $hasEncryption = $this->_hasEncryption();

      $key = $this->_getConfig('key', 'upop_general');
      $encryption = $hasEncryption ? '1' : '0';

      $request = $this->_getRootNode();
      $transaction = $request->addChild('TRANSACTION');
      $fields = $transaction->addChild('FIELDS');
      $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
      $fields->addChild('SERVICE', 'DBT');
      $fields->addChild('SERVICE_TYPE', 'DEBIT');
      $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
      $fields->addChild('SERVICE_FORMAT', '1010');
      $fields->addChild('TRANSACTION_INDICATOR', '7');
      $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
      $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
      //If a different currecy is selected by the customer
      if ($payment->getId() && $payment->getUpopCurrencyCode()) {
      if ($payment->getUpopCurrencyCode() == $this->getNativeCurrency()) {
      $fields->addChild('CURRENCY_INDICATOR', '0');
      } else {
      $fields->addChild('CURRENCY_INDICATOR', '1');
      }
      } else {
      $fields->addChild('CURRENCY_INDICATOR', '0');
      }

      $fields->addChild('AMOUNT', number_format((float) $this->getAmount(), 2, '.', ''));

      //$url = ($_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:($_SERVER['HTTPS']?'https':'http')).'://' . $_SERVER['HTTP_HOST'];
      $url = Mage::getUrl('upop/onepage/response', array('_secure' => true));
      $fields->addChild('UP_FRONTENDURL', $url);
      //$fields->addChild('ENTRY_MODE', '');
      //$fields->addChild('FESP_IND', '9'); //added
      // $fields->addChild('CLIENT_IP', $_SERVER['SERVER_ADDR']);//alredy here
      //Sending Few Additional data to Gateway
      $this->addAdditionalData($fields, true);
      $this->setTransactionForLog($request);

      if ($hasEncryption) {
      $this->_encryptRequest($request);
      }
      $this->setTransaction($request);
      } catch (Exception $e) {
      Mage::throwException($e->getmessage());
      }

      return $this;
      } */

    /**
     * Generates the Transaction Xml for authorization
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateApiRequestForPycAuth() {
        try {
            $hasEncryption = $this->_hasEncryption();

            //$key = $this->_getConfig('key', 'upop_general');
            //$encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');
            $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
            //If a different currecy is selected by the customer
            if ($this->getUpopCurrencyCode() == $this->getNativeCurrency()) {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '1');
            }

            $fields->addChild('AMOUNT', number_format((float) $this->getAmount(), 2, '.', ''));

            $url = Mage::getUrl('upop/onepage/response', array('_secure' => true));
            $fields->addChild('UP_FRONTENDURL', $url);
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);

            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml for authorization
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    /* public function generateRequestForMcpAuth() {
      try {
      $payment = $this->getPayment();
      $quote = Mage::helper('upop')->getQuote();
      $quoteCurrency = $quote->getQuoteCurrencyCode();
      $billingAddress = $quote->getBillingAddress();
      $hasEncryption = $this->_hasEncryption();

      $key = $this->_getConfig('key', 'upop_general');
      $encryption = $hasEncryption ? '1' : '0';

      $request = $this->_getRootNode();
      $transaction = $request->addChild('TRANSACTION');
      $fields = $transaction->addChild('FIELDS');
      $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
      $fields->addChild('SERVICE', 'DBT');
      $fields->addChild('SERVICE_TYPE', 'DEBIT');
      $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
      $fields->addChild('SERVICE_FORMAT', '1010');
      $fields->addChild('TRANSACTION_INDICATOR', '7');
      $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
      $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($quoteCurrency));
      if ($quoteCurrency != $this->getNativeCurrency()) {
      $fields->addChild('CURRENCY_INDICATOR', '1');
      } else {
      $fields->addChild('CURRENCY_INDICATOR', '0');
      }
      $fields->addChild('AMOUNT', number_format((float) $this->getAmountInStoreCurrency(), 2, '.', ''));
      $url = Mage::getUrl('upop/onepage/response', array('_secure' => true));
      $fields->addChild('UP_FRONTENDURL', $url);
      //$fields->addChild('ENTRY_MODE', '');
      // $fields->addChild('CLIENT_IP', $_SERVER['SERVER_ADDR']);
      // $fields->addChild('FESP_IND', '9'); //uncomment
      //Sending Few Additional data to Gateway
      $this->addAdditionalData($fields, true);
      $this->setTransactionForLog($request);

      if ($hasEncryption) {
      $this->_encryptRequest($request);
      }
      $this->setTransaction($request);
      } catch (Exception $e) {
      Mage::throwException($e->getmessage());
      }

      return $this;
      } */

    /**
     * Generates the Transaction Xml for authorization
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateApiRequestForMcpAuth() {
        try {
            //$payment = $this->getPayment();
            //$quote = Mage::helper('upop')->getQuote();
            $quoteCurrency = $this->getQuoteCurrencyCode();
            //$billingAddress = $quote->getBillingAddress();
            $hasEncryption = $this->_hasEncryption();
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');

            $payment_action = Mage::getModel('upop/upop')->getConfigData('payment_action');
            if ($payment_action == PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE) {
                $fields->addChild('SERVICE_SUBTYPE', 'AUTH');
            } elseif ($payment_action == PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
                $fields->addChild('SERVICE_SUBTYPE', 'SALE');
            }


            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');

            //$fields->addChild('SERVICE_SUBTYPE', 'SALE');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($quoteCurrency));
            if ($quoteCurrency != $this->getNativeCurrency()) {
                $fields->addChild('CURRENCY_INDICATOR', '1');
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            }
            $fields->addChild('AMOUNT', number_format((float) $this->getAmountInStoreCurrency(), 2, '.', ''));
            $url = Mage::getUrl('upop/index/response', array('_secure' => true));
            $fields->addChild('UP_FRONTENDURL', $url);
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml Fo Pyc Currency rate query
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generatePycCurrencyRateQueryRequest() {
        //print "3";exit;//
        try {
            $payment = $this->getPayment();
            $profile = $this->_getProfile($payment->getUpopProfileId());
            $quote = $this->getQuote();
            $currencyCode = $this->_getCurrencyIsoCode($quote->getQuoteCurrencyCode());
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
            $fields->addChild('CURRENCY_INDICATOR', '2');
            $fields->addChild('SERVICE', 'CURRENCY');
            $fields->addChild('SERVICE_TYPE', 'RATE');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('AMOUNT', number_format((float) $this->getAmount(), 2, '.', ''));
            if ($profile->getAccountId()) {
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
            } else {
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($payment->getCcExpMonth(), $payment->getCcExpYear()));
                $fields->addChild('ACCOUNT_NUMBER', $payment->getCcNumber());
            }
            $fields->addChild('QUERY_TYPE', '0');
            // $fields->addChild('FESP_IND', '9');
            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, true);

            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Transaction request for currency rate look up for mcp
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateCurrencyRateLookUpRequest() {
        //print "4";exit;//
        try {
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('CURRENCY_INDICATOR', '1');
            $fields->addChild('SERVICE', 'CURRENCY');
            $fields->addChild('SERVICE_TYPE', 'RATE');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('QUERY_TYPE', '1');
            // $fields->addChild('FESP_IND', '9'); //added
            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);
            $this->setCurrencyRate(true);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generating transaction request for adding a new Client Profille
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateNewWalletProfileRequest() {
        //print "5";exit;//
        try {
            $profile = $this->getUpopPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'INSERT');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                $fields->addChild('FIRST_NAME', htmlentities($profile->getFirstName(), ENT_QUOTES));
                $fields->addChild('LAST_NAME', htmlentities($profile->getLastName(), ENT_QUOTES));
                $fields->addChild('ADDRESS', $this->_conditionAddress($profile->getAddress()));
                $fields->addChild('CITY', htmlentities($profile->getCity(), ENT_QUOTES));
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($profile->getZip()));
                $fields->addChild('STATE', htmlentities($profile->getState(), ENT_QUOTES));
                $fields->addChild('COUNTRY', htmlentities($profile->getCountry(), ENT_QUOTES));
                $fields->addChild('ACCOUNT', 'CC');
                $fields->addChild('ACCOUNT_NUMBER', $profile->getCardNumber());
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($profile->getExpirationMonth(), $profile->getExpirationYear()));
                $fields->addChild('CVV', $profile->getCardCode());
                $fields->addChild('BILLING_TRANSACTION', '2');
                //$fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, true);

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("failed to create payment profile", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("failed to create payment profile");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Xml request for updating customer profile
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateUpdateClientRequest() {
        //print "6";exit;//
        try {
            $profile = $this->getUpopPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'MODIFY');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                $fields->addChild('CLIENT_ID', $profile->getClientId());
                $fields->addChild('FIRST_NAME', htmlentities($profile->getFirstName(), ENT_QUOTES));
                $fields->addChild('LAST_NAME', htmlentities($profile->getLastName(), ENT_QUOTES));
                $fields->addChild('ADDRESS', $this->_conditionAddress($profile->getAddress()));
                $fields->addChild('CITY', htmlentities($profile->getCity(), ENT_QUOTES));
                $fields->addChild('POSTAL_CODE', $this->_conditionPostalCode($profile->getZip()));
                $fields->addChild('STATE', htmlentities($profile->getState(), ENT_QUOTES));
                $fields->addChild('COUNTRY', htmlentities($profile->getCountry(), ENT_QUOTES));
                //$fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, true);

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("failed to Update payment profile", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("failed to Update payment profile");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generating the xml reauest for updating customer card details
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateUpdateAccountRequest() {
        //print "8";exit;//
        try {
            $profile = $this->getUpopPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'ACCOUNT');
                $fields->addChild('SERVICE_SUBTYPE', 'MODIFY');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                $fields->addChild('ACCOUNT_ID', $profile->getAccountId());
                $fields->addChild('ACCOUNT_NUMBER', $profile->getCardNumber());
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('EXPIRATION', $this->_getCcExpiration($profile->getExpirationMonth(), $profile->getExpirationYear()));
                $fields->addChild('CVV', $profile->getCardCode());
                //$fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, true);

                $this->setTransactionForLog($request);


                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                } else {
                    $fields->addChild('PIN', $this->_getConfig('terminal_id', 'pin'));
                }

                $this->setTransaction($request);
            } else {
                Mage::log("failed to update payment profile", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("failed to update payment profile");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Deleting Customer profile from Planet payment
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateDeleteClientRequest() {
        //print "8";exit;//
        try {
            $profile = $this->getUpopPaymentProfile();
            if ($profile) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('SERVICE', 'WALLET');
                $fields->addChild('SERVICE_TYPE', 'CLIENT');
                $fields->addChild('SERVICE_SUBTYPE', 'DELETE');
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                $fields->addChild('CLIENT_ID', $profile->getClientId());
                // $fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, true);

                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("failed to delete payment profile", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("failed to delete payment profile");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Test configurations
     * @return \PlanetPayment_Upop_Model_Xml_Request
     */
    public function generateTestConfigurationRequest() {
        //print "9";exit;//
        try {
            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('SERVICE', 'NETWORK');
            $fields->addChild('SERVICE_TYPE', 'STATUS');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('SERVICE_FORMAT', '0000');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            //$fields->addChild('FESP_IND', '9');
            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, false);

            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }

            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Request for Capture
     * 
     * @return \PlanetPayment_Upop_Model_Xml_Request
     */
    public function generateRequestForCapture() {
        //print "10";exit;//
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();
                $billingAddress = $payment->getOrder()->getBillingAddress();
                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $paymentType = $this->_getPaymentType($payment);

                if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                    if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                        $fields->addChild('CURRENCY_INDICATOR', '2');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                } elseif ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_MCP) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
                    if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {
                        $fields->addChild('CURRENCY_INDICATOR', '1');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                }

                $fields->addChild('TRANSACTION_ID', $payment->getLastTransId());
                $fields->addChild('SERVICE', 'DBT');
                $fields->addChild('SERVICE_TYPE', 'DEBIT');
                $fields->addChild('SERVICE_SUBTYPE', 'CAPTURE');
                $fields->addChild('AMOUNT', number_format((float) $this->getAmountInStoreCurrency(), 2, '.', ''));
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                // $fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, false);

                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("Unable to capture", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("Unable to capture");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Request for Sale
     * 
     * @return \PlanetPayment_Upop_Model_Xml_Request
     */
    public function generateRequestForSale() {

        try {
            $payment = $this->getPayment();
            $quote = Mage::helper('upop')->getQuote();
            $billingAddress = $quote->getBillingAddress();

            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');
            $fields->addChild('SERVICE_SUBTYPE', 'SALE');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $paymentType = $this->_getPaymentType($payment);
            if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC) {
                $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                    $fields->addChild('CURRENCY_INDICATOR', '2');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                }
            } elseif ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_MCP) {
                $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
                if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {

                    $fields->addChild('CURRENCY_INDICATOR', '1');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                }
            }
            $fields->addChild('AMOUNT', round($quote->getGrandTotal(), 2));
            $url = Mage::getUrl('upop/onepage/response', array('_secure' => true));
            $fields->addChild('UP_FRONTENDURL', $url);
            $fields->addChild('ENTRY_MODE', '3');
            // $fields->addChild('CLIENT_IP', $_SERVER['SERVER_ADDR']);
            //  $fields->addChild('FESP_IND', '9');
            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Request for Void
     * 
     * @return \PlanetPayment_Upop_Model_Xml_Request
     */
    public function generateRequestForVoid() {
        //print "12";exit;
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $fields->addChild('TRANSACTION_ID', $payment->getLastTransId());
                $fields->addChild('SERVICE', 'DBT');
                $fields->addChild('SERVICE_TYPE', 'DEBIT');
                $fields->addChild('SERVICE_SUBTYPE', 'VOID');
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                // $fields->addChild('FESP_IND', '9');
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, false);
                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("Unable to Void", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("Unable to Void");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generate Request for Refund
     * 
     * @return \PlanetPayment_Upop_Model_Xml_Request
     */
    public function generateRequestForRefund() {
        try {
            $payment = $this->getPayment();
            if ($payment) {
                $hasEncryption = $this->_hasEncryption();

                $key = $this->_getConfig('key', 'upop_general');
                $encryption = $hasEncryption ? '1' : '0';
                $request = $this->_getRootNode();
                $transaction = $request->addChild('TRANSACTION');
                $fields = $transaction->addChild('FIELDS');
                $fields->addChild('TRANSACTION_INDICATOR', '7');
                $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
                $fields->addChild('SERVICE_FORMAT', '1010');
                $paymentType = $this->_getPaymentType($payment);

                if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($this->getNativeCurrency()));
                    if ($this->getNativeCurrency() != $payment->getOrder()->getOrderCurrencyCode()) {
                        $fields->addChild('CURRENCY_INDICATOR', '2');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                    $fields->addChild('AMOUNT', number_format((float) $this->getAmount(), 2, '.', ''));
                } elseif ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_MCP) {
                    $fields->addChild('CURRENCY_CODE', $this->_getCurrencyIsoCode($payment->getOrder()->getOrderCurrencyCode()));
                    if ($payment->getOrder()->getOrderCurrencyCode() != $this->getNativeCurrency()) {
                        $fields->addChild('CURRENCY_INDICATOR', '1');
                    } else {
                        $fields->addChild('CURRENCY_INDICATOR', '0');
                    }
                    $fields->addChild('AMOUNT', number_format((float) $this->getAmountInStoreCurrency(), 2, '.', '')); //refund in the currency of the charge
                }

                $fields->addChild('TRANSACTION_ID', $payment->getRefundTransactionId());
                $fields->addChild('SERVICE', 'DBT');
                $fields->addChild('SERVICE_TYPE', 'CREDIT');
                $fields->addChild('SERVICE_SUBTYPE', 'REFUND');
                $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
                //  $fields->addChild('FESP_IND', '9'); //uncomment
                //Sending Few Additional data to Gateway
                $this->addAdditionalData($fields, false);
                $this->setTransactionForLog($request);

                if ($hasEncryption) {
                    $this->_encryptRequest($request);
                }

                $this->setTransaction($request);
            } else {
                Mage::log("Unable to Refund", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException("Unable to Refund");
            }
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml for seond-pass request
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateSecondRequest() {
        try {
            //Get Payment instance
            $payment = $this->getPayment();
            $param = $this->getParam();

            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');

            $fields->addChild('SERVICE_SUBTYPE', $this->getTransactionType());
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('CURRENCY_CODE', $param['orderCurrency']);
            //If a different currency is selected by the customer
            if ($param['orderCurrency']) {
                if ($this->_getCurrencyFromIsoCode($param['orderCurrency']) == $this->getNativeCurrency()) {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '1');
                }
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            }
            $fields->addChild('AMOUNT', number_format($param['orderAmount'] / 100, 2, '.', ''));
            $fields->addChild('UP_ORDERNUMBER', $param['orderNumber']);
            // $fields->addChild('UP_PAYLOAD', htmlspecialchars($this->getUpPayload()));
            $fields->addChild('UP_PAYLOAD64', $this->getUpPayload64());

            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Generates the Transaction Xml for seond-pass request
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateSecondPassiPayRequest() {
        try {
            $param = $this->getParam();

            $hasEncryption = $this->_hasEncryption();

            $key = $this->_getConfig('key', 'upop_general');
            $encryption = $hasEncryption ? '1' : '0';

            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('PIN', $this->_getConfig('pin', 'general'));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'DEBIT');

            $fields->addChild('SERVICE_SUBTYPE', $this->getTransactionType());
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TRANSACTION_INDICATOR', '7');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('CURRENCY_CODE', $param['orderCurrency']);
            //If a different currency is selected by the customer
            if ($param['orderCurrency']) {
                if ($this->_getCurrencyFromIsoCode($param['orderCurrency']) == $this->getNativeCurrency()) {
                    $fields->addChild('CURRENCY_INDICATOR', '0');
                } else {
                    $fields->addChild('CURRENCY_INDICATOR', '1');
                }
            } else {
                $fields->addChild('CURRENCY_INDICATOR', '0');
            }
            $fields->addChild('AMOUNT', number_format($param['orderAmount'] / 100, 2, '.', ''));
            $fields->addChild('UP_ORDERNUMBER', $param['orderNumber']);
            //$fields->addChild('UP_PAYLOAD', htmlspecialchars($this->getUpPayload()));
            $fields->addChild('UP_PAYLOAD64', $this->getUpPayload64());

            //Sending Few Additional data to Gateway
            $this->addAdditionalData($fields, true);
            $this->setTransactionForLog($request);

            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }

        return $this;
    }

    /**
     * Sending the DBT STATUS request to Planet Payment
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function generateRequestForDBT() {
        try {
            $order = $this->getOrder();
            $payment = $order->getPayment();
            $this->setPayment($payment);
            $hasEncryption = $this->_hasEncryption();
            $request = $this->_getRootNode();
            $transaction = $request->addChild('TRANSACTION');
            $fields = $transaction->addChild('FIELDS');
            $fields->addChild('AMOUNT', round($payment->getOrder()->getGrandTotal(), 2));
            $fields->addChild('SERVICE', 'DBT');
            $fields->addChild('SERVICE_TYPE', 'STATUS');
            $fields->addChild('SERVICE_SUBTYPE', 'QUERY');
            $fields->addChild('SERVICE_FORMAT', '1010');
            $fields->addChild('TERMINAL_ID', $this->_getConfig('terminal_id', 'upop_general'));
            $fields->addChild('TRANSACTION_ID', $payment->getLastTransId());
            $fields->addChild('PIN', $this->_getConfig('pin', 'upop_general'));
            $this->addAdditionalData($fields, false);
            $this->setTransactionForLog($request);
            if ($hasEncryption) {
                $this->_encryptRequest($request);
            }
            $this->setTransaction($request);
        } catch (Exception $e) {
            Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException($e->getmessage());
        }
        return $this;
    }

    /**
     * Adding Order Id and Incremented Order id and Customer Ip to Request XML
     * 
     * @param obj $fields
     * @param boolean $frontrequest
     */
    public function addAdditionalData($fields, $frontrequest = false) {
        $payment = $this->getPayment();
        if ($payment) {
            $order = $payment->getOrder(); //to create a order object
            //Set User data
            if ($order) {
                $fields->addChild('TICKET', $order->getIncrementId());
                $fields->addChild('USER_DATA_1', $order->getId());
                $fields->addChild('CLIENT_IP', $order->getData('remote_ip'));
                $fields->addChild('FESP_IND', '9');
                //Get Store Details from where order placed...
                $store = $order->getData('store_name');
                $store = str_replace(array("<br>", "\n", "\r"), '-', $store);
                $stores = explode('-', $store);
                $fields->addChild('USER_DATA_3', $stores[0]);
                $fields->addChild('USER_DATA_4', $stores[1]);
                $fields->addChild('USER_DATA_5', $stores[2]);

                $frontrequest = false;
            }
        }

        if ($frontrequest) {
            $fields->addChild('CLIENT_IP', $_SERVER['REMOTE_ADDR']);
            $fields->addChild('FESP_IND', '9');
            $fields->addChild('USER_DATA_3', Mage::app()->getWebsite()->getName());
            $fields->addChild('USER_DATA_4', Mage::app()->getGroup()->getName());
            $fields->addChild('USER_DATA_5', Mage::app()->getStore()->getName());
        }

        $fields->addChild('USER_DATA_6', (string) Mage::getConfig()->getNode()->modules->PlanetPayment_Upop->version);
        $fields->addChild('USER_DATA_7', Mage::getVersion());
    }

    /**
     * Sending the request to Planet Payment
     * 
     * @return PlanetPayment_Upop_Model_Xml_Request 
     */
    public function send() {
        $transaction = $this->getTransaction();

        if ($transaction) {
            try {
                $isProduction = $this->_isProductionMode();
                if ($isProduction) {
                    $url = PlanetPayment_Upop_Model_Upop::GATEWAY_URL_PRODUCTION;
                } else {
                    $url = PlanetPayment_Upop_Model_Upop::GATEWAY_URL_TESTING;
                }
                //Selecting port based on the url
                $port = 86;
                if (strstr($url, 'https://')) {
                    $port = 443;
                }
                //print $transaction->asXML();exit;
                $client = new Zend_Http_Client($url, array('keepalive' => true, 'timeout' => 360));
                $client->getUri()->setPort($port);
                $client->setRawData($transaction->asXML(), 'text/xml');
                $client->setMethod(Zend_Http_Client::POST);
                $response = $client->request()->getBody();

                //Setting response to response model object
                $responseModel = $this->_getResponseModel();
                $responseModel->setUpopRequest($this);
                $responseModel->setUpopResponse($response);
                $this->setResponse($responseModel);
            } catch (Exception $e) {
                Mage::log($e->getmessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                Mage::throwException($e->getmessage());
            }

            return $this;
        } else {
            Mage::log("invalid Transaction", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            Mage::throwException('invalid Transaction');
        }
    }

}
