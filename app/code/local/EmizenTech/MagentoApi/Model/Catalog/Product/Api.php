<?php
class EmizenTech_MagentoApi_Model_Catalog_Product_Api extends Mage_Catalog_Model_Product_Api
{
	/**
     * Retrieve list of products with basic info (id, sku, type, set, name)
     *
     * @param null|object|array $filters
     * @param string|int $store
     * @return array
     */
    public function items($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSort('name', 'desc')
            ->addAttributeToSelect('name');

        $collection_send = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSort('name', 'desc')
            ->addAttributeToSelect('name');

        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters, $this->_filtersMap);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
                if(count($collection)==0)
                {  
                    $collection_send->addFieldToFilter('sku', $value);
                    $collection = $collection_send;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $result = array();
        foreach ($collection as $product) {
            $load = Mage::getModel('catalog/product')->load($product->getId());
            $formattedPrice = Mage::helper('core')->currency($load->getPrice(), true, false);
            $cropped_image_url = (string)Mage::helper('catalog/image')->init($load, 'image')->resize(100);

            $result[] = array(
                'product_id'    =>  $product->getId(),
                'sku'           =>  $product->getSku(),
                'image'         =>  $cropped_image_url,
                'price'         =>  $formattedPrice,
                'name'          =>  $product->getName(),
                'set'           =>  $product->getAttributeSetId(),
                'type'          =>  $product->getTypeId(),
                'category_ids'  =>  $product->getCategoryIds(),
                'website_ids'   =>  $product->getWebsiteIds()
            );
        }
        return $result;
    }
}
		