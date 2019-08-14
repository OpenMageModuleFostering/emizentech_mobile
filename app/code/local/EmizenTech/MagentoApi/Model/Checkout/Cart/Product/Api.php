<?php
class EmizenTech_MagentoApi_Model_Checkout_Cart_Product_Api extends Mage_Checkout_Model_Cart_Product_Api
{
	/**
     * @param  $quoteId
     * @param  $store
     * @return array
     */
    public function items($quoteId, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        if (!$quote->getItemsCount()) {
            return array();
        }

        $productsResult = array();

        foreach ($quote->getAllItems() as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            //return get_class_methods($item); die;
            $product = $item->getProduct();

            $load = Mage::getModel('catalog/product')->load($product->getId());

            $cropped_image_url = (string)Mage::helper('catalog/image')->init($load, 'image')->resize(110);
            
            if($product->getTypeId() == 'simple')
            {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

                if($parentIds)
                {
                    continue;
                }
            }

            if($product->getTypeId() == 'bundle')
            {
                $BundleProductsResult = array();
                $selectionCollection = $load->getTypeInstance(true)->getSelectionsCollection(
                    $load->getTypeInstance(true)->getOptionsIds($load), $load
                );
                
                foreach($selectionCollection as $option) 
                {
                    //$product['bundle'][] = $option->product_id;
                    $formattedPrice = Mage::helper('core')->currency($option->getSelectionPriceValue(), true, false);
                    //$product['bundle'][$option->getSelectionId()] = $option->getName()." +".$formattedPrice;

                    if($option->getSelectionPriceType() == 0)
                    {
                      $price_type = "fixed";
                    }
                    else
                    {
                      $price_type = "percent";
                    }
                    $BundleProductsResult[] = array(
                        'title' => $option->getName(),
                        'selection_id' => $option->getSelectionId(),
                        'price' => $formattedPrice,
                        'option_id' => $option->getOptionId(),
                        'price_type' => $price_type,
                        'sku' => $option->getSku()
                    );
                }
                $productsResult[] = array( // Basic product data
                    'product_id'   => $product->getId(),
                    'sku'          => $product->getSku(),
                    'name'         => $product->getName(),
                    'set'          => $product->getAttributeSetId(),
                    'type'         => $product->getTypeId(),
                    'buldle'        => $BundleProductsResult,
                    'category_ids' => $product->getCategoryIds(),
                    'website_ids'  => $product->getWebsiteIds(),
                    'image_url'    => $cropped_image_url,
                    'price'         => Mage::helper('core')->currency($product->getPrice()*$item->getQty(), true, false),
                    'orginal_price' => Mage::helper('core')->currency($product->getPrice(), true, false),
                    'qty'          => $item->getQty(),
                    'parent_id'     => $parentIds
                );
            }
            elseif($product->getTypeId() == 'configurable')
            {
                //$ConfigurableProductsResult = array();

               /* $productAttributeOptions = $load->getTypeInstance(true)->getConfigurableAttributesAsArray($load);
                foreach($productAttributeOptions as $_attribute)
                {
                    foreach($_attribute['values'] as $attribute)
                    {
                        $ConfigurableProductsResult[$_attribute['label']]['attribute_id'] = $_attribute['attribute_id'];
                        $ConfigurableProductsResult[$_attribute['label']][$attribute['value_index']] = $attribute['store_label'];
                    }     
                }*/

                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                $productsResult[] = array( // Basic product data
                    'item_id'           => $item->getId(),
                    'product_id'        => $product->getId(),
                    'sku'               => $product->getSku(),
                    'name'              => $product->getName(),
                    'set'               => $product->getAttributeSetId(),
                    'type'              => $product->getTypeId(),
                    'configurable'      => $options['attributes_info'],
                    'super_attribute'   => $options['info_buyRequest']['super_attribute'],
                    'category_ids'      => $product->getCategoryIds(),
                    'website_ids'       => $product->getWebsiteIds(),
                    'image_url'         => $cropped_image_url,
                    'price'             => Mage::helper('core')->currency($product->getPrice()*$item->getQty(), true, false),
                    'orginal_price'     => Mage::helper('core')->currency($product->getPrice(), true, false),
                    'qty'               => $item->getQty(),
                    'parent_id'     => $parentIds
                );
            }
            elseif($product->getTypeId() == 'grouped')
            {
                $GroupedProductsResult = array();
                $simpleProducts = $load->getTypeInstance(true)->getAssociatedProducts($load);
                foreach ($simpleProducts as $item)
                {
                    $GroupedProductsResult['grouped'][$item->getId()] = $item->getQty();
                }
                $productsResult[] = array( // Basic product data
                    'product_id'   => $product->getId(),
                    'sku'          => $product->getSku(),
                    'name'         => $product->getName(),
                    'set'          => $product->getAttributeSetId(),
                    'type'         => $product->getTypeId(),
                    'grouped'       => $GroupedProductsResult,
                    'category_ids' => $product->getCategoryIds(),
                    'website_ids'  => $product->getWebsiteIds(),
                    'image_url'    => $cropped_image_url,
                    'price'         => Mage::helper('core')->currency($product->getPrice()*$item->getQty(), true, false),
                    'orginal_price' => Mage::helper('core')->currency($product->getPrice(), true, false),
                    'qty'          => $item->getQty(),
                    'parent_id'     => $parentIds
                );
            }
            else
            {
                $productsResult[] = array( // Basic product data
                    'product_id'   => $product->getId(),
                    'sku'          => $product->getSku(),
                    'name'         => $product->getName(),
                    'set'          => $product->getAttributeSetId(),
                    'type'         => $product->getTypeId(),
                    'category_ids' => $product->getCategoryIds(),
                    'website_ids'  => $product->getWebsiteIds(),
                    'image_url'    => $cropped_image_url,
                    'price'         => Mage::helper('core')->currency($product->getPrice()*$item->getQty(), true, false),
                    'orginal_price' => Mage::helper('core')->currency($product->getPrice(), true, false),
                    'qty'          => $item->getQty(),
                    'parent_id'     => $parentIds
                );
            } 
        }

        return $productsResult;
    }
}
		