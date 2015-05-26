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
class PlanetPayment_Upop_Model_System_Config_Payment_PaymentAction {

    public function toOptionArray() {
        return array(
            array(
                'value' => PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE,
                'label' => Mage::helper('upop')->__('Authorize Only')
            ),
            array(
                'value' => PlanetPayment_Upop_Model_Upop::PAYMENT_ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('upop')->__('Authorize and Capture')
            )
        );
    }

}
