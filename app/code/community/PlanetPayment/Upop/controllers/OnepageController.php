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
require_once 'Mage/Checkout/controllers/OnepageController.php';

class PlanetPayment_Upop_OnepageController extends Mage_Checkout_OnepageController {

	/**
	 * Save payment ajax action
	 *
	 * Sets either redirect or a JSON response
	 */
	public function savePaymentAction() {
		if ($this->_expireAjax()) {
			return;
		}
		
		$data = $this->getRequest()->getPost('payment', array());
		try {
			if (!$this->getRequest()->isPost()) {
				$this->_ajaxRedirectResponse();
				return;
			}
            
			// set payment to quote
			$result = array();
			$result = $this->getOnepage()->savePayment($data);
            
			// get section and redirect data
			$redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();

			if (empty($result['error']) && !$redirectUrl) {
				$this->loadLayout('checkout_onepage_review');
				$result['goto_section'] = 'review';
				$result['update_section'] = array(
						'name' => 'review',
						'html' => $this->_getReviewHtml()
				);
			}
			if ($redirectUrl) {
				$result['redirect'] = $redirectUrl;
			}
		} catch (Mage_Payment_Exception $e) {
			if ($e->getFields()) {
				$result['fields'] = $e->getFields();
			}
			$result['error'] = $e->getMessage();
		} catch (Mage_Core_Exception $e) {
			$result['error'] = $e->getMessage();
		} catch (Exception $e) {
			Mage::logException($e);
			$result['error'] = $this->__('Unable to set Payment Method.');
		}
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}

	public function redirectAction() { 
		$session = Mage::getSingleton('checkout/session');
		$this->getResponse()->setBody('<p>'.$this->__('You will be redirected to the UnionPay website in a few seconds.').'</p>'.$session->getRedirectParam());
		$session->unsRedirectUrl();
	}

	public function responseAction() {
		
		$param = $this->getRequest()->getPost();	
		$session = Mage::getSingleton('checkout/session');
		//$order = Mage::getModel('sales/order')->load($session->getOrderId()); 
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
		$payment = $order->getPayment();

		try {
			$requestModel = $this->_getUpopModel()->secondPass($payment, $param);
			if ($requestModel) {
				$this->_getSession()->addSuccess($this->__('The order has been approved.'));
				$this->_redirectUrl(Mage::getUrl('checkout/onepage/success', array('_secure'=>true)));
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
			$this->_redirectUrl(Mage::getUrl('checkout/onepage/failure', array('_secure'=>true)));
		} catch (Exception $e) {
			Mage::logException($e);
			$this->_getSession()->addException($e, $this->__('An error occurred while send second-pass request.'));
			$this->_redirectUrl(Mage::getUrl('checkout/onepage/failure', array('_secure'=>true)));
		}
	}

	/**
	 * Retrieve customer session object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession() {
		return Mage::getSingleton('core/session');
	}

	protected function _getUpopRequestModel() {
		return Mage::getModel('upop/xml_request');
	}

	protected function _getUpopModel() {
		return Mage::getModel('upop/upop');
	}



}   
