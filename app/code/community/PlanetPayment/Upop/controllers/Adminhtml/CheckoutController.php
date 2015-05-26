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
class PlanetPayment_Upop_Adminhtml_CheckoutController extends Mage_Adminhtml_Controller_Action {

    public function getUpopCurrenciesAction() {
        $postData = $this->getRequest()->getpost('payment', array());
        $result['update_section'] = array(
            'name' => 'currency_selector',
            'html' => ''
        );
        try {
            if ($postData) {
                if (isset($postData['cc_number'])) {
                    $postData['cc_last4'] = substr($postData['cc_number'], 0, -4);
                }
                $quote = Mage::helper('upop')->getQuote();
                $payment = $quote->getPayment();
                $payment->importData($postData);

                $quote->save();
                if (isset($postData['method']) && $postData['method'] == PlanetPayment_Upop_Model_Upop::METHOD_CODE) {
                    try {
                        $method = $payment->getMethodInstance();
                        $paymentType = $method->getPaymentType();
                        if ($paymentType == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC) {
                            // //Preparing & Sending request for PYC Rate Query
//                            $quote->setUpopProfileId($payment->getUpopProfileId());
                            $request = $this->_getUpopRequestModel()
                                    ->setPayment($payment)
                                    ->setQuote($quote)
                                    ->setAmount($quote->getGrandTotal());

                            $request->generatePycCurrencyRateQueryRequest()
                                    ->send();

                            //Getting Response Object
                            $response = $request->getResponse();
                            //Checking whether the request succeed
                            if ($response->isSuccess()) {
                                //Loading Currency section if request succeed
                                $this->loadLayout('upopadmin_adminhtml_checkout_getUpopCurrencies');
                                $result['update_section'] = array(
                                    'name' => 'currency_selector',
                                    'html' => $this->_getCurrencySelectorHtml($response)
                                );
                            } else {
                                //Throwing an error to load the review section
                                $result['error'] = true;
                                $result['message'] = "Upop Request Failed. Message: {$response->getMessage()}";
                            }
                        }
                    } catch (Exception $e) {
                        $result['error'] = true;
                        $result['message'] = $e->getMessage();
                    }
                }
            }
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function updateCurrencyAction() {
        $postData = $this->getRequest()->getpost('payment', array());
        if ($postData) {
            if (isset($postData['selected_currency'])) {
                $quote = Mage::helper('upop')->getQuote();
                $payment = $quote->getPayment();
                $payment->setUpopCurrencyCode($postData['selected_currency']);
                $quote->save();
            }
        }
    }

    protected function _getUpopRequestModel() {
        return Mage::getModel('upop/xml_request');
    }

    protected function _getCurrencySelectorHtml($response) {
        return $this->getLayout()->getBlock('root')->setResponse($response)->toHtml();
    }

}
