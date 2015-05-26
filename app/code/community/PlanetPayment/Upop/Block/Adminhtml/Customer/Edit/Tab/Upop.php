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
class PlanetPayment_Upop_Block_Adminhtml_Customer_Edit_Tab_Upop extends Mage_Adminhtml_Block_Template {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('upop/customer/tab/upop.phtml');
    }

    /**
     * Returns the registry customer.
     * 
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return Mage::registry('current_customer');
    }

    /**
     * Returns a collection of payment profiles for the registry customer.
     *
     * @return PlanetPayment_Upop_Model_Resource_PaymentProfile_Collection
     */
//    public function getCimPaymentProfiles() {
//        return Mage::getModel('upop/profile')->getCollection()
//                        ->addCustomerFilter($this->getCustomer());
//    }

    /**
     * URL to create/recreate Upop profile.
     *
     * @return string
     */
    public function getCimRegisterUrl() {
        return $this->getUrl('upopadmin/adminhtml_customer/register', array('customer_id' => $this->getCustomer()->getId()));
    }

    /**
     * URL to delete a Upop payment profile.
     *
     * @param PlanetPayment_Upop_Model_PaymentProfile $profile
     * @return string
     */
    public function getUpopProfileDeleteUrl(PlanetPayment_Upop_Model_Profile $profile) {
        $params = array(
            'customer_id' => $this->getCustomer()->getId(),
            'profile_id' => $profile->getId()
        );
        return $this->getUrl('upopadmin/adminhtml_customer/deletePaymentProfile', $params);
    }

}
