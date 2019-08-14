<?php
class EmizenTech_MagentoApi_Model_Api extends Mage_Api_Model_Resource_Abstract
{        
	/*
	* @ Get Home Slides Images
	* @ When You will use this api, you Get All Home Slider Images
	* @ Call Method Like This: magentoapi_api.homeslides'
	* @ If not showing images OR home slider on home page please check you have images in promo-cards folder or not which locate is /media/wysiwyg/home-slide/ , If you have images in this folder after that you have to select folder path in the static block in backend, locate is: CMS > Pages, edit home-slider identifer and modify.
	*/      
	public function HomeSlides(){

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

		preg_match_all('/<img[^>]+>/i',Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('home-slider')->toHtml(),$imgsrc);

		foreach ($imgsrc[0] as $value) {
			$doc = new DOMDocument();
			$doc->loadHTML($value);
			$xpath = new DOMXPath($doc);
			$src['slides'][] = $xpath->evaluate("string(//img/@src)");
		}

		return $src;
	}

	/*
	* @ Get Home Banner Images
	* @ When You will use this api you Get All Home Banner
	* @ Call Method Like This: magentoapi_api.homebanner'
	* @ If not showing images OR home banner on home page please check you have images in promo-cards folder or not which locate is /media/wysiwyg/promo-cards/ , If you have images in this folder after that you have to select folder path in the static block in backend, locate is: CMS > Static Blocks, edit home-banner identifer and modify.
	*/
	public function HomeBanner(){

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

		preg_match_all('/<img[^>]+>/i',Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('home-banner')->toHtml(),$imgsrc);

		foreach ($imgsrc[0] as $value) {
			$doc = new DOMDocument();
			$doc->loadHTML($value);
			$xpath = new DOMXPath($doc);
			$src['banners'][] = $xpath->evaluate("string(//img/@src)");
		}

		return $src;
	}

	/*
	* @ Get New Products
	* @ When You will use this api you Get All New Products Which all you save according to date in the backend.
	* @ Call Method Like This: magentoapi_api.newproducts'
	*/
	public function NewProducts(){

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

		$todayStartOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('00:00:00')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $todayEndOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('23:59:59')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
        $collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

        $collection->addStoreFilter(1)
            ->addAttributeToFilter('news_from_date', array('or'=> array(
                0 => array('date' => true, 'to' => $todayEndOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                0 => array('date' => true, 'from' => $todayStartOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                    array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                    )
              )
            ->addAttributeToSort('news_from_date', 'desc')
            ->setPageSize(6)
            ->setCurPage(1)
        ;
        
        $options = array();
        foreach ($collection->getData() as $key=>$value) {
        	$product = Mage::getModel('catalog/product')->load($value['entity_id']);
            $cat_ids = implode(",", $product->getCategoryIds());
            $cropped_image_url = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(150);
            $options[$key]['name'] = $product->getData('name');
            $options[$key]['image_url'] = $cropped_image_url;
            $options[$key]['price'] = $product->getData('price');
            $options[$key]['sku'] = $value['sku'];
            $options[$key]['is_in_stock'] = $product->stock_item->is_in_stock;
            $options[$key]['category_ids'] = $cat_ids;
            $options[$key]['product_id'] = $product->getId();
        }

        return $options;
	}

	/*
	* @ Get Product Detail By Product Id 
	* @ When You will use this api you must have pass productId. Call Method Like This: magentoapi_api.productdetailbyid', array('productId')
	*/
	public function ProductDetailById($productId = null){

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

    	$product = array(); // Making array of products collection custom which all items we need to use

    	$collection = Mage::getModel('catalog/product')->load($productId); // Load product collection using product id

    	$resized_image_url = (string)Mage::helper('catalog/image')->init($collection, 'image')->resize(250); // Resized image according to iphone app

    	$product['name'] = $collection->getData('name');
    	$product['sku'] = $collection->getData('sku');
    	$product['is_in_stock'] = $collection->stock_item->is_in_stock;
    	$product['type_id'] = $collection->getData('type_id');
    	$product['meta_description'] = $collection->getData('meta_description');
    	$product['price'] = $collection->getData('price');
    	$product['description'] = $collection->getData('description');
    	$product['short_description'] = $collection->getData('short_description');
    	$product['meta_keyword'] = $collection->getData('meta_keyword');
    	$product['image_url'] = $resized_image_url;
    	$product['product_id'] = $collection->getId();

    	if($collection->getData('type_id') == 'bundle') // check if product type is bundle or not
    	{
			$selectionCollection = $collection->getTypeInstance(true)->getSelectionsCollection(
        		$collection->getTypeInstance(true)->getOptionsIds($collection), $collection
      		);
 			
 			$i = 0;
			foreach($selectionCollection as $option) 
			{
				$formattedPrice = Mage::helper('core')->currency($option->getSelectionPriceValue(), true, false);

	            if($option->getSelectionPriceType() == 0)
	            {
	              $price_type = "fixed";
	            }
	            else
	            {
	              $price_type = "percent";
	            }
	            
	            $product['bundle_options']['items_'.$i]['title'] = $option->getName();
	            $product['bundle_options']['items_'.$i]['selection_id'] = $option->getSelectionId();
	            $product['bundle_options']['items_'.$i]['price'] = $formattedPrice;
                $product['bundle_options']['items_'.$i]['option_id'] = $option->getOptionId();
	            $product['bundle_options']['items_'.$i]['price_type'] = $price_type;
	            $product['bundle_options']['items_'.$i]['sku'] = $option->getSku();
	            $i++;
			}
		}

		if($collection->getData('type_id') == 'configurable') // check if product type is configurable or not
        {
	        $productAttributeOptions = $collection->getTypeInstance(true)->getConfigurableAttributesAsArray($collection);
	        foreach($productAttributeOptions as $_attribute)
	        {
	        	foreach($_attribute['values'] as $attribute)
	            {
                    $product['configurable'][$_attribute['label']]['attribute_id'] = $_attribute['attribute_id'];
	            	$product['configurable'][$_attribute['label']][$attribute['value_index']] = $attribute['store_label'];
	            }     
	        }
	    }

		foreach($collection->getTypeInstance(true)->getEditableAttributes($collection) as $attribute)
		{
            $product[$attribute->getAttributeCode()] = $collection->getData($attribute->getAttributeCode());
        }

    	return $product;
    }

    /*
	* @ Get Products Detail By Category Id.
	* @ When You will use this api to Get Category Products, you must have pass categoryId inside the parameter
	* @ Call Method Like This: magentoapi_api.productsbycategoryid', array('categoryId','sort order', 'direction like desc OR asc');
	*/
    public function ProductsByCategoryId($categoryId, $sortOrder, $direction){

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

	    $options = array();
	     
        $_category = Mage::getModel('catalog/category')->load($categoryId);
        $collection = $_category
                ->getProductCollection()
                ->addAttributeToSelect('*');
        $collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);


        // If you need to use showing products using pagination use this commented code

        //$before_coll = count($_category->getProductCollection()->addAttributeToSelect('*'));

        /*if(isset($_GET['page']) && $_GET['page_size']){
          $collection->setPage($_GET['page'],$_GET['page_size']);
          $collection->setPageSize($_GET['page_size']);
        }*/
        
        //$totl_rocrd = round($before_coll/$_GET['page_size']);                  
        //$options['total_record'] = $totl_rocrd;   
        
        $options['sort_order'] = $_category->getAvailableSortByOptions();

        /*if you should sort order list then use this code */
        if(in_array($sortOrder, array_keys($options['sort_order']))){
            if($direction == 'DESC')
              $collection->setOrder($sortOrder , 'DESC');
            else
              $collection->setOrder($sortOrder , 'ASC');
        }

      	$i = 0;
	      foreach ($collection as $product) {

	          $load = Mage::getModel('catalog/product')->load($product->getId());

	          $media_catalog_URL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."catalog/product";

	          $cropped_image_url = (string)Mage::helper('catalog/image')->init($load, 'image')->resize(150);

	          $options['product_'.$i]['entity_id'] = $product->getData('entity_id');
	          $options['product_'.$i]['type_id'] = $product->getData('type_id');
	          $options['product_'.$i]['sku'] = $product->getData('sku');
	          $options['product_'.$i]['is_in_stock'] = $load->stock_item->is_in_stock;
	          $options['product_'.$i]['meta_keyword'] = $product->getData('meta_keyword');
	          $options['product_'.$i]['description'] = $product->getData('description');
	          $options['product_'.$i]['short_description'] = $product->getData('short_description');
	          $options['product_'.$i]['name'] = $product->getData('name');
	          $options['product_'.$i]['meta_description'] = $product->getData('meta_description');
	          $options['product_'.$i]['price'] = $product->getData('price');
	          //$options['product_'.$i]['is_saleable'] = $product->isSaleable();
	          $options['product_'.$i]['image_url'] = $cropped_image_url;
	          $options['product_'.$i]['add_cart_url'] = Mage::helper('checkout/cart')->getAddUrl($product);
	          $options['product_'.$i]['product_id'] = $product->getId();
	          $i++;
	      }

	      return $options;
    }

    /*
	* @ Get Customer Detail with particular parameter.
	* @ When You will use this api to Get Customer Details, you must have pass useremail, pass inside the parameter
	* @ Call Method Like This: magentoapi_api.getcustomerid', array('useremail','pass');
	*/
    public function GetCustomerId($useremail = null, $pass = null, $store = 1)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

    	$websiteId = Mage::getModel('core/store')->load($store)->getWebsiteId(); // load store to get website id
        $result = array();
        $result["useremail"] = $useremail;
        $result["pass"] = $pass;
        $status = 1; // for checking customer email and password is valid or not so we set the status
        try{
	     	$customer_result = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->authenticate($useremail,$pass); // authenticate customer with email and pass
            $customers = Mage::getModel('customer/customer')->setWebsiteId($websiteId);
            $customers->loadByEmail($useremail);             
            $result["firstname"] = $customers->firstname;
            $result["lastname"] = $customers->lastname;
            $result["customerId"] = $customers->getId(); 
            $result["website"] = $websiteId; 
            //$result["password"]
            //$result["password"]
        }
        catch( Exception $e ){
            $result["status"] = 0;
        	$result['error_msg'] = "Invalid login or password";
        }
        
        return $result;
    }

    /*
	* @ Get Related Products Using Product Id.
	* @ When You will use this api to Get Related Products According To Product Id, you must have pass ProductId
	* @ Call Method Like This: magentoapi_api.relatedproducts', array('ProductId');
	*/
    public function RelatedProducts($productId = null)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

    	$result = array();
    	$model = Mage::getModel('catalog/product');
		$product = $model->load($productId);

		// Get all related product ids of $product.
		$allRelatedProductIds = $product->getRelatedProductIds();

		$j = 0;
		foreach ($allRelatedProductIds as $id)
		{
			$relatedProduct = $model->load($id);
			$resized_image_url = (string)Mage::helper('catalog/image')->init($relatedProduct, 'image')->resize(200);
			$formattedPrice = Mage::helper('core')->currency($relatedProduct->getData('price'), true, false);
        	$result['productItems_'.$j]['name'] = $relatedProduct->getData('name');
        	$result['productItems_'.$j]['sku'] = $relatedProduct->getData('sku');
        	$result['productItems_'.$j]['is_in_stock'] = $relatedProduct->stock_item->is_in_stock;
        	$result['productItems_'.$j]['type_id'] = $relatedProduct->getData('type_id');
        	$result['productItems_'.$j]['meta_description'] = $relatedProduct->getData('meta_description');
        	$result['productItems_'.$j]['price'] = $formattedPrice;
        	$result['productItems_'.$j]['description'] = $relatedProduct->getData('description');
        	$result['productItems_'.$j]['short_description'] = $relatedProduct->getData('short_description');
        	$result['productItems_'.$j]['meta_keyword'] = $relatedProduct->getData('meta_keyword');
        	$result['productItems_'.$j]['image_url'] = $resized_image_url;
        	$result['productItems_'.$j]['product_id'] = $relatedProduct->getId();
        	$j++;
		}
		return $result;
    }

    
    /*
	* @ Get CMS Page Content Using this Api.
	* @ When You will use this api to Get CMS Page Content, you must have pass Page Identifier From The End Of The URL like this: Suppose you have a URL: YOUR_MAGENTO_PATH/about-us, you need to pass only this about-us to get content.
	* @ Call Method Like This: magentoapi_api.getcmspagecontent', array('Page Identifier');
	*/
    public function GetCmsPageContent($page = null)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

    	if($page)
        {
          $PageData = Mage::getModel('cms/page')->load($page,'identifier');
          $html = array();

          $helper = Mage::helper('cms');
          $processor = $helper->getPageTemplateProcessor();

          $html['title'] = $PageData->getTitle();
          $html['html'] = $processor->filter($PageData->getContent());
        }
        else
        {
          die('Error: Must be pass page identifier to get page data.');
        }

        return $html;
    }

    /*
    * @ Newsletter Api.
    * @ When You will use this api to Send Newsletter, you must have pass email,CustomerId(if exists : optional)
    * @ Call Method Like This: magentoapi_api.newsletter', array('email','CustomerId:optional');
    */
    public function Newsletter($email, $customerId = null, $store = 1)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

        try
        {
            $websiteId = Mage::getModel('core/store')->load($store)->getWebsiteId(); // load store to get website id

            if(Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 && !$customerId)
            {
                return json_encode(array('error' => 'Sorry, but administrator denied subscription for guests. Please register first.'));
            }

            $Id = Mage::getModel('customer/customer')
                        ->setWebsiteId($websiteId)
                        ->loadByEmail($email)
                        ->getId(); // get id according to website id and email

            if ($Id !== null && $Id != $customerId) // check if customer logged in to another system
            {
                return json_encode(array('error' => "This email address is already assigned to another user."));
            }

            //$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            
            $status = Mage::getModel('newsletter/subscriber')->subscribe($email);

            if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE)
            {
                return json_encode(array('success' => "Confirmation request has been sent."));
            }
            else
            {
                return json_encode(array('success' => "Thank you for your subscription."));
            }
        }
        catch(Exception $e)
        {
            return json_encode(array('There was a problem with the subscription: %s', $e->getMessage())); // return error using exception
        }
    }

    /*
    * @ Contact Us Api.
    * @ When You will use this api to Contact Us, you must have pass name,email,telephone,comment
    * @ Call Method Like This: magentoapi_api.contact', array('name','email','telephone','comment');
    */
    public function Contact($name, $email, $telephone, $comment, $blank = null)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

        // get data in array format
        $postarr = array('name' => $name, 'email' => $email, 'telephone' => $telephone, 'comment' => $comment, 'hideit' => $blank);
        
        if ($postarr)
        {
            $translateIn = Mage::getSingleton('core/translate');
            $translateIn->setTranslateInline(false); // set inline translation
            try
            {
                $postObj = new Varien_Object(); // set data to object
                $postObj->setData($postarr);

                $emailTem = Mage::getModel('core/email_template'); // template email model
                
                $emailTem->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($postarr['email'])
                    ->sendTransactional(
                                            Mage::getStoreConfig('contacts/email/email_template'),
                                            Mage::getStoreConfig('contacts/email/sender_email_identity'),
                                            Mage::getStoreConfig('contacts/email/recipient_email'),
                                            null,
                                            array('data' => $postObj)
                                        );

                if (!$emailTem->getSentSuccess()) {
                    return json_encode(array('Error' => "Email not sent")); // return error using exception
                }

                $translateIn->setTranslateInline(true);

                // return in json format success message if mail is sent
                return json_encode(array('Success' => "Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.")); // return error using exception

            }
            catch (Exception $e)
            {
                $translateIn->setTranslateInline(true);
                return json_encode(array('Success' => 'Unable to submit your request. Please, try again later'));
            }
        }
    }

    /*
    * @ Add To Products Wishlist Api.
    * @ When You will use this api to Add Product In Wishlist, you must have pass CustomerId,ProductId,
    * @ Call Method Like This: magentoapi_api.wishlistadd', array('CustomerId','ProductId');
    */
    public function WishlistAdd($customerId , $productId , $store = 1)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

        //Mage::log("cust:".$customerId . " ~ prod:".$productId ." store:".$store, null , 'myapi.log'); // create log for check responce

        $websiteId = Mage::getModel('core/store')->load($store)->getWebsiteId();

        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId);
         
        $wishlist = Mage::getModel('wishlist/wishlist'); // load wishlist model

        //$product  = Mage::getModel('catalog/product')->load($productId);
        $product  = Mage::getModel('catalog/product')->setStoreId($store)->load($productId);

        if(!$product)
                return array('error' => "Product Not Found");
        $customer->load($customerId); // load customer according to customer id

        $wishlist->loadByCustomer($customer);   
        
        // using try and catch method
        try
        {
            $res = $wishlist->addNewItem($product);
            $res->setStore($store);
            $res->save();
             $wishlist->save();
              Mage::dispatchEvent(
                    'wishlist_add_product',
                    array(
                        'wishlist' => $wishlist,
                        'product' => $product,
                        'item' => $res
                    )
                );
            
            if($res)
                return json_encode(array('success' => "Added"));
            else
                return json_encode(array('error' => "Error in adding product to wishlist"));
        }
        catch(Exception $e )
        {
            return json_encode(array('error' => "Error:".$e->getMessage())); // return error using exception
        }
    }   

    /*
    * @ Delete Items In Customer Wishlist
    * @ When You will use this api to Delete Product In Wishlist, you must have pass CustomerId,ItemId,
    * @ Call Method Like This: magentoapi_api.wishlistdelete', array('CustomerId','itemId');
    */
    public function WishlistDelete($customerId , $itemId)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

        $item = Mage::getModel('wishlist/item')->load($itemId); // load wishlist model using item id
        if (!$item->getId()) {
            return array('error' => "Error:Item Not Found");
        }
        $wishlist = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());
        if (!$wishlist) {
            return array('error' => "Error:Wishlist Not Found");
        }
        try {
            $item->delete(); // delete items in wishlist
            $wishlist->save(); // save wishlist model after delete items
            Mage::helper('wishlist')->calculate();
            return array('success' => "Item:".$itemId." Deleted");
        }
        catch(Exception $e )
        {
            return array('error' => "Error:".$e->getMessage());
        }
    }   

    /*
    * @ Get All Items From Customer Wishlist.
    * @ When You will use this api to Get All Items From Customer Wishlist, you must have pass CustomerId
    * @ Call Method Like This: magentoapi_api.wishlistview', array('CustomerId');
    */
    public function WishlistView($customerId)
    {

        if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
            return;

        try
        {
        $itemCollection = Mage::getResourceModel('wishlist/item_collection'); // Items Collection
        $itemCollection->addCustomerIdFilter($customerId); // filter according to customer id which is logged in

            $data = array();
            foreach($itemCollection as $item) {
                $product = $item->getProduct();
                $data[] = array('item_id' => $item->getId(),
                                'product_id' => $item->getProductId(),
                                'name' => $product->getName(),
                                'price' => $product->getPrice(),
                                'small_image' => "".Mage::helper('catalog/image')->init($product, 'small_image')->resize(113, 113)
                                );
            }
        }
        catch(Exception $e )
        {
            return array('error' => "Error:".$e->getMessage());
        }
        return $data; // return product items fields with product attributes in json format
    }


    
}
