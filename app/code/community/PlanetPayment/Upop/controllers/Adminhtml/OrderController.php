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
class PlanetPayment_Upop_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action {

    /**
     * Debit Status Action
     */
    public function getDbtStatusAction() {

        $order_id = $this->getRequest()->getParam('order_id');
        $upop = Mage::getModel('upop/upop');
        $error = false;
        try {
            $result = $upop->getDbtStatus($order_id);
            if(!$result) {
                $error = true;
            }
            
            if(!$error) {
                $response_text = $result->FIELDS->RESPONSE_TEXT;// to show the message on click of dbt status button
                if($response_text=='Approved') {
                    $this->_getSession()->addSuccess($this->__('%s', $response_text));
                } else {
                    $this->_getSession()->addError($this->__('%s', $response_text));
                }
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        
        $url = Mage::helper("adminhtml")->getUrl('adminhtml/sales_order/view', array('order_id' => $order_id));
        $this->getResponse()->setRedirect($url);
    }

}
