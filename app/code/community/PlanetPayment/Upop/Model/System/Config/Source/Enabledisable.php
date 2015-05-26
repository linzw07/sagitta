<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Enabledisable
 *
 * @author mohds
 */
class PlanetPayment_Upop_Model_System_Config_Source_Enabledisable {
    
    //put your code here
     public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Enable'))
            
        );
    }
}
