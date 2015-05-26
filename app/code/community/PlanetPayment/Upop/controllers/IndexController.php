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
 * To Call For Method For Starting Chekout Process
 * 
 * @category PlanetPayment
 * @package PlanetPayment_Upop
 * @author mohds
 */
class PlanetPayment_Upop_IndexController extends Mage_Core_Controller_Front_Action {

    /**
     * Contain the Checkout model object
     * 
     * @var PlanetPayment_Upop_Model_Checkout
     */
    protected $_checkout = null;

    /**
     * Contain the Quote object
     * 
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = false;

    /**
     * Initilize The Parent Front Action Controller Action
     */
    protected function _construct() {
        parent::_construct();
    }

    /**
     * Start the Checkout Process for Order
     * 
     * @return Null
     */
    public function startAction() {
        try {
            
            $this->_initCheckout();

            if ($this->_getQuote()->getIsMultiShipping()) {
                $this->_getQuote()->setIsMultiShipping(false);
                $this->_getQuote()->removeAllAddresses();
            }

            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ($customer && $customer->getId()) {
                $this->_checkout->setCustomerWithAddressChange(
                        $customer, $this->_getQuote()->getBillingAddress(), $this->_getQuote()->getShippingAddress()
                );
            }

            //First pass request
            $response = $this->_checkout->start();
            if ($response) {
                $up_payload = $response->FIELDS->UP_PAYLOAD;
                if ($up_payload) {
                    $this->getResponse()->setBody($up_payload);
                    return;
                } else {
                    Mage::log("Couldn't process your request. Please try again later or contact us.", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                    $this->_getCheckoutSession()->addError($this->__("Couldn't process your request. Please try again later or contact us."));
                    $this->_redirect('checkout/cart/index');
                }
            } else {
                Mage::log("Unable to authorize first pass with iPay.", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                $this->_getCheckoutSession()->addError($this->__('Unable to authorize first pass with iPay.'));
                $this->_redirect('checkout/cart/index');
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            $this->_getCheckoutSession()->addError($this->__('%s', $e->getMessage()));
            $this->_redirect('checkout/cart/index');
        }
    }

    /**
     * Return from the Payment Gateway 
     * 
     * @return Null
     */
    public function responseAction() {
        $param = $this->getRequest()->getPost();
        try {
            $this->_initCheckout();
            $response = $this->_checkout->secondPass($param);
            if ($response) {
                if ($response->FIELDS->RESPONSE_TEXT == 'Approved') {
                    $result = array();
                    try {
                        $this->_getQuote()->collectTotals()->save();
                        $this->getOnepage()->saveOrder();

                        $result['success'] = true;
                        $result['error'] = false;
                    } catch (Mage_Payment_Model_Info_Exception $e) {
                        Mage::log($e->getMessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                        $message = $e->getMessage();
                        if (!empty($message)) {
                            $result['success'] = false;
                            $result['error'] = true;
                            $result['error_messages'] = $message;
                        }
                    } catch (Mage_Core_Exception $e) {
                        Mage::log($e->getMessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                        Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                        $result['success'] = false;
                        $result['error'] = true;
                        $result['error_messages'] = $e->getMessage();
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                        Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                        $result['success'] = false;
                        $result['error'] = true;
                        $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
                    }
                } else {
                    Mage::log("There was an error processing your order. Please contact us or try again later.", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                    $result['success'] = false;
                    $result['error'] = true;
                    $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
                }
            } else {
                Mage::log("There was an error processing your order. Please contact us or try again later.", null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
                $result['success'] = false;
                $result['error'] = true;
                $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $this->__('%s', $e->getMessage());
        }

        if ($result['error'] === true) {
            Mage::log($result['error_messages'], null, PlanetPayment_Upop_Model_Upop::LOG_FILE);
            $this->_getCheckoutSession()->addError($this->__("%s", $result['error_messages']));
            $this->_redirect('checkout/cart');
            return;
        }

        $this->getOnepage()->getQuote()->save();
       $this->_getUopSession()->unsetAll();
        $this->_redirect('checkout/onepage/success');
        return;
    }

    /**
     * Instantiate quote and checkout
     * 
     * @throws Mage_Core_Exception
     */
    private function _initCheckout() {
        $quote = $this->_getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            Mage::throwException(Mage::helper('upop')->__('Unable to initialize UPOP Checkout.'));
        }
        $this->_checkout = Mage::getSingleton('upop/checkout', array(
                    'quote' => $quote,
        ));
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
     * Return checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
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

    /**
     * To Get The Upop Model Object
     * 
     * @return PlanetPayment_Upop_Model_Upop
     */
    protected function _getUpopModel() {
        return Mage::getModel('upop/upop');
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

}
