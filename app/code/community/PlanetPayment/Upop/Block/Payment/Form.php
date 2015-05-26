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
class PlanetPayment_Upop_Block_Payment_Form extends Mage_Payment_Block_Form_Cc {

    protected function _construct() {
		parent::_construct();

		$mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('upop/payment/mark.phtml')
            ->setPaymentAcceptanceMarkSrc('http://en.unionpay.com//images/english/logo.jpg');

        $this->setTemplate('upop/payment/form.phtml')
            ->setMethodLabelAfterHtml($mark->toHtml());

    }

    /**
     * Returns true if it's guest checkout.
     *
     * @return bool
     */
    public function isGuestCheckout() {
        return Mage::helper('upop')->isGuestCheckout();
    }

    /**
     * Returns the logged in user.
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer() {
        return Mage::helper('upop')->getCustomer();
    }

}
