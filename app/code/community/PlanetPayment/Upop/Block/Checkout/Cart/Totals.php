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
class PlanetPayment_Upop_Block_Checkout_Cart_Totals extends Mage_Checkout_Block_Cart_Totals {

    /**
     * Check if we have display grand total in base currency
     *
     * @return bool
     */
    public function needDisplayBaseGrandtotal() {
        $quote = $this->getQuote();
        if ($quote->getBaseCurrencyCode() != $quote->getQuoteCurrencyCode() ||
                ($this->_isPyc() && $quote->getQuoteCurrencyCode() != $quote->getPayment()->getUpopCurrencyCode())) {
            return true;
        }
        return false;
    }

    /**
     * Get formated in base currency base grand total value
     *
     * @return string
     */
    public function displayBaseGrandtotal() {
        $firstTotal = reset($this->_totals);
        if ($firstTotal) {
            $quote = $this->getQuote();
            $exchangeRate = $quote->getUpopExchangeRate();
            if ($this->_isPyc() && $exchangeRate) {
                $total = $firstTotal->getAddress()->getBaseGrandTotal();
                $currency = Mage::app()->getLocale()->currency($quote->getPayment()->getUpopCurrencyCode())->getSymbol() . " " . number_format($total * $exchangeRate, 2);
            } elseif ($this->_isMcp()) {
                $total = $firstTotal->getAddress()->getGrandTotal();
                $currency = Mage::app()->getStore()->getCurrentCurrency()->format($total, array(), true);
            } else {
                $total = $firstTotal->getAddress()->getBaseGrandTotal();
                $currency = Mage::app()->getStore()->getBaseCurrency()->format($total, array(), true);
            }
            return $currency;
        }
        return '-';
    }

    protected function _isPyc() {
        return Mage::getmodel('upop/upop')->getConfigData("service") == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_PYC;
    }

    protected function _isMcp() {
        return Mage::getmodel('upop/upop')->getConfigData("service") == PlanetPayment_Upop_Model_Upop::PAYMENT_SERVICE_MCP;
    }

}
