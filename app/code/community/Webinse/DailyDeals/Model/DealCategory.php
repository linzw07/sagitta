<?php

/**
 * @category   Webinse
 * @package    Webinse_DailyDeals
 * @author     Webinse Team <info@webinse.com.com>
 */
class Webinse_DailyDeals_Model_DealCategory
{

    /**
     * @var string
     */
    protected $_logFile = 'deals.log';

    /**
     * @var array key => category_id : value = > next update day
     */
    protected $_startDayForCategoryDeals = array();
    /**
     * @var array key => category_id : value = > next update day
     */
    protected $_endDayForCategoryDeals = array();
    /**
     * @var null all deal params
     */
    protected $_dealCategoryAtt = null;

    /**
     * @return array With Categories of deal status
     */
    public function getAllDealCategory()
    {
        $collection = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('deal_update_days', array('notnull' => true))
            ->load();
        if (!count($collection)) {
            return false;
        } else {
            foreach ($collection as $item) {
                $this->_dealCategoryAtt[$item->getEntityId()] = array(
                    'deal_days' => explode(',', $item->getDealUpdateDays()),
                    'deal_product_qty' => $item->getQtyDealProduct(),
                    'deal_discount_percent' => $item->getDealDiscountPercent(),
                    'deal_qty_product_percent' => $item->getDealQtyProductPercent()
                );
            }
            return true;
        }
    }

    /**
     * Generation array with category to update today
     *
     * @return array key = category id : value = deal_product_qty
     */
    public function getArrayUpdateCategory()
    {
        $currentDay = Mage::getModel('core/date')->date('N');
        $arrayUpdateCategory = array();
        $startNumber = null;
        $endNumber = null;
        $i = 0;
        foreach ($this->_dealCategoryAtt as $categoryId => $value ) {
            foreach($value['deal_days'] as $number){
                if($currentDay == $number){
                    $startNumber = $number;
                    $endNumber = $number + 1;
                    $next = '';
                    break;
                } elseif($currentDay < $number) {
                    $startNumber = $number;
                    $endNumber = $number + 1;
                    $next = 'next ';
                    break;
                } else {
                    $startNumber = $number;
                    $endNumber = $number + 1;
                    $next = 'next ';
                }
                $i++;
            }
            $startDay = Mage::helper('dailydeals')->getNextDayNameWithNumber($startNumber, $next);
            $endDay = Mage::helper('dailydeals')->getNextDayNameWithNumber($endNumber, $next);

            $this->_startDayForCategoryDeals[$categoryId] = $startDay;
            $this->_endDayForCategoryDeals[$categoryId] = $endDay;
            $arrayUpdateCategory[$categoryId] = $value['deal_product_qty'];
        }

        return $arrayUpdateCategory;
    }

    /**
     * Generation product's array by category's Ids
     *
     * @param array $categoryIdsAndCountDays Array category's for update today
     */
    public function updateCategoryDealStatusInProduct(array $categoryIdsAndCountDays)
    {
        $categoryModel = Mage::getModel('catalog/category');
        $stockIds = Mage::getModel('cataloginventory/stock_item')
            ->getCollection()
            ->addQtyFilter('>', 0)
            ->getAllIds();
        foreach ($categoryIdsAndCountDays as $categoryId => $countDealProducts) {
            $categoryModel->load($categoryId);
            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addIdFilter($stockIds)
                //->addAttributeToFilter('is_salable', true)
                ->addAttributeToFilter('visibility', array('neq' => 1))
                ->addAttributeToFilter(
                    array(
                        array('attribute' => 'deal_status', 'null' => true),
                        array('attribute' => 'deal_status', 'eq' => null),
                        array('attribute' => 'deal_status', 'neq' => 1),
                        array('attribute' => 'deal_status', 'eq' => true),
                    ), '', 'left')
                ->addAttributeToFilter(
                    array(
                        array('attribute' => 'type_id', 'eq' => 'simple'),
                        array('attribute' => 'type_id', 'eq' => 'downloadable'),
                        array('attribute' => 'type_id', 'eq' => 'virtual')
                    ), '', 'left')
                ->addCategoryFilter($categoryModel);
            //Products from the group
            $productsArray = $collection->getData();
            $count = 0; $i = 0;
            foreach($productsArray as $product){
                if($product['deal_status'] != null) {
                    unset($productsArray[$i]);
                    $count++;
                }
                $i++;
            }
            if($count != $countDealProducts){
                if (!empty($productsArray)) {
                    if (count($productsArray) > $countDealProducts) {
                        //Products for deal status
                        $productSelected = Mage::helper('dailydeals')->getRandomArrayValue($productsArray, $countDealProducts-$count);
                    } else {
                        $productSelected = $productsArray;
                    }
                    //set deal status on products
                    $this->_setDealCategoryStatusOnProduct($productSelected, $categoryId, $categoryModel->getName());
                } else {
                    Mage::log('There are no requested amount of goods in the Deal Category `' . $categoryModel->getName() . '` id: `' . $categoryId . '`', null, $this->_logFile);
                }
            }
        }
    }

    /**
     * @param array $productsArray - array with random products for category
     * @param $categoryId - id selected category
     */
    protected function _setDealCategoryStatusOnProduct(array $productsArray, $categoryId, $categoryName)
    {
        $currentData = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));
        $startUpdateData = $this->_startDayForCategoryDeals[$categoryId]['date'];
        if($currentData == $startUpdateData) {
            $statuses = 'automatically';
        }else {
            $statuses = null;
        }
        //get next day
        $endUpdateData = $this->_endDayForCategoryDeals[$categoryId]['date'];
        $data_time = implode(',', $this->_dealCategoryAtt[$categoryId]['deal_days']);
        //Set Deal status on random category products
        try {
            $productModel = Mage::getModel('catalog/product');
            foreach ($productsArray as $product) {
                $productModel->load($product['entity_id']);
                //Calculate deal price and qty
                $categoryDealPriceAndQtyArray = $this->_calculatePriceAndQtyByPercent(
                    $productModel->getPrice(),
                    $productModel->getStockItem()->getQty(),
                    $categoryId);
                //Set Deal data in Product Model
                $productModel
                    ->setDealStatuses($statuses)
                    ->setDealStatus(true)
                    ->setDealStartTime($startUpdateData)
                    ->setDealEndTime($endUpdateData)
                    ->setDealUpdateDaysProduct($data_time)
                    ->setDealCategoryId($categoryId)
                    ->setDealQty($categoryDealPriceAndQtyArray['deal_qty'])
                    ->setDealPrice($categoryDealPriceAndQtyArray['deal_price']);
                //Set special data with deal data, and save old special data
                Mage::helper('dailydeals')->changeSpecialData($productModel, 'automatically');
                //Save deal product
                $productModel->save();
            }
            Mage::log('Update of the Deal Category `' . $categoryName . '` completed', null, $this->_logFile);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, $this->_logFile);
        }
    }

    /**
     * @param $productPrice
     * @param $productQty
     * @param $categoryId
     * @return array
     */
    protected function _calculatePriceAndQtyByPercent($productPrice, $productQty, $categoryId)
    {
        $dealQty = (int)$this->_dealCategoryAtt[$categoryId]['deal_qty_product_percent'];
        if ($productQty < $dealQty) {
            $dealQty = $productQty;
        }
        $dealPricePercent = $productPrice * (int)$this->_dealCategoryAtt[$categoryId]['deal_discount_percent'] / 100;
        return array(
            'deal_price' => $productPrice - $dealPricePercent,
            'deal_qty' => (int)$dealQty,
        );

    }

}