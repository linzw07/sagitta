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
class PlanetPayment_Upop_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action {

    /**
     * Inits the customer from the request.
     * 
     * @return Mage_Customer_Model_Customer | false
     */
    protected function _initCustomer() {
        $customerId = $this->getRequest()->getParam('customer_id');
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                //Mage::register('current_customer', $customer);
                return $customer;
            }
        }
        return false;
    }

    /**
     * Purges a customer profile and any payment profiles.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return PlanetPayment_Upop_Adminhtml_CustomerController
     */
//    protected function _purgeProfile(Mage_Customer_Model_Customer $customer) {
//        $profileId = $customer->getCimProfileId();
//
//        // delete with CIM
//        Mage::getModel('authnetcim/xml_customer')
//                ->setCustomer($customer)
//                ->delete();
//
//        // delete local profiles
//        Mage::getModel('authnetcim/paymentProfile')
//                ->getResource()
//                ->deleteByCustomerProfileId($profileId);
//    }

    /**
     * Deletes a payment profile.
     */
//    public function deletePaymentProfileAction() {
//        $customer = $this->_initCustomer();
//        if ($customer) {
//            $profileId = $this->getRequest()->getParam('profile_id');
//            $profile = Mage::getModel('upop/profile')->load($profileId);
//            if ($profile->getId()) {
//                try {
//                    $requestModel = Mage::getModel('upop/xml_request')->setUpopPaymentProfile($profile)
//                            ->generateDeleteClientRequest()
//                            ->send();
//                    $response = $requestModel->getResponse();
//                    if ($response->isSuccess()) {
//                        $profile->delete();
//                        $this->_getSession()->addSuccess($this->__('The profile has been deleted.'));
//                    } else {
//                        Mage::throwException("failed to delete your profile from Upop. please try later.");
//                    }
//                } catch (Exception $e) {
//                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//                }
//            } else {
//                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upop')->__('Payment Profile not found.'));
//            }
//            $this->_redirect('adminhtml/customer/edit', array('id' => $customer->getId(), 'tab' => 'upop'));
//            return;
//        }
//        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upop')->__('Customer not found.'));
//        $this->_redirect('adminhtml/customer');
//    }

    /**
     * ACL check.
     *
     * @return bool
     */
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('customer/manage');
    }

}
