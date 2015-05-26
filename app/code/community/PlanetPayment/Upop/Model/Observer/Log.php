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
class PlanetPayment_Upop_Model_Observer_Log extends PlanetPayment_Upop_Model_Observer_Abstract {

    public function cleanLog() {
        $logCleanConfig = Mage::getStoreConfig('planet_payment/upop_logging/lifetime');

        $daysBefore = strtotime("-$logCleanConfig days", time());
        $formated = date('Y-m-d H:i:s', $daysBefore);
        $logs = Mage::getModel('upop/log')->getCollection()->addFieldToFilter('create_date', array('lt' => $formated));
        
        if (count($logs)) {
            foreach ($logs as $log) {
                Mage::getModel('upop/log')->load($log->getId())->delete();
            }
        }
        return $this;
    }

}
