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
class PlanetPayment_Upop_Adminhtml_SystemController extends Mage_Adminhtml_Controller_Action {

    /**
     * Exports the logs into a text file
     */
    public function exportAction() {
        try {
            $logModel = Mage::getmodel('upop/log');
            $logs = $logModel->getCollection();
            if (count($logs)) {
                $content = "CREATE TABLE IF NOT EXISTS `" . $logModel->getResource()->getTable('upop/log') . "` 
                    (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `request` text NULL,
  `response` text NULL,
  `create_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                $content .= "INSERT INTO `" . $logModel->getResource()->getTable('upop/log') . "` (`request`, `response` , `create_date`) VALUES ";
                $i = 0;
                foreach ($logs as $log) {
                    $content .= $i != 0 ? ", " : "";
                    $content .= "('" . addslashes($log->getRequest()) . "','" . addslashes($log->getResponse()) . "','" . $log->getCreateDate() . "')";
                    $i++;
                }
                $this->_prepareDownloadResponse("LogExport.sql", $content);
                return;
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upop')->__('Log is empty'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }

    public function testAction() {
        $requestModel = Mage::getModel('upop/xml_request')->generateTestConfigurationRequest()
                ->send();
        $response = $requestModel->getResponse();

        if ($response->isSuccess()) {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('upop')->__('Configurations tested successfully.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upop')->__('Error in configuration.</br> Message:'.$response->getMessage()));
        }
        $this->_redirectReferer();
    }

    /**
     * ACL check.
     *
     * @return bool
     */
    protected function _isAllowed() {
        $actionName = $this->getRequest()->getActionName();
        return Mage::getSingleton('admin/session')->isAllowed('upop/system/' . $actionName);
    }

}
