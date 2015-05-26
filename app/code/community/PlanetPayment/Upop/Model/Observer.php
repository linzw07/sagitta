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
class PlanetPayment_Upop_Model_Observer extends Mage_Core_Model_Abstract {

    /**
     * Display debit status button in case of fail in UPOP payment authorization  
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addDbtStatusButton(Varien_Event_Observer $observer) {
        
        $block = $observer->getEvent()->getData('block');

        if(get_class($block) == 'Mage_Adminhtml_Block_Sales_Order_View' && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $order = $block->getOrder();
            if(!$order->getData('dbt_status') && $order->getPayment()->getMethod()==PlanetPayment_Upop_Model_Upop::METHOD_CODE) {
                $block->addButton('dbt_status', array(
                    'label'     => 'Debit Status',
                    'onclick'   => 'setLocation(\'' . $block->getUrl('upopadmin/adminhtml_order/getDbtStatus') . '\')',
                    'class'     => 'go'
                ));
            }
        }
        
    }

}
