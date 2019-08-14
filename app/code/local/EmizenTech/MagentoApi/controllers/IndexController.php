<?php
class EmizenTech_MagentoApi_IndexController extends Mage_Core_Controller_Front_Action{
  
  protected $login_user = "emizentech"; //In the backend create user then put here. :go to Admin System > Web Services > SOAP/XML-RPC - Users
  protected $login_password = "123456"; //Put here API Key of particular user when you will create user above.

  /*
  * Important: While you creating user dont forget to assign the user role.
  * User role: go to Admin System > Web Services > SOAP/XML-RPC - Roles
  * In the role section you have to also assign role users
  */

  /*public function IndexAction() {
    
  $this->loadLayout();   
  $this->getLayout()->getBlock("head")->setTitle($this->__("Magento Api"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
    $breadcrumbs->addCrumb("home", array(
              "label" => $this->__("Home Page"),
              "title" => $this->__("Home Page"),
              "link"  => Mage::getBaseUrl()
	   ));

    $breadcrumbs->addCrumb("magento api", array(
              "label" => $this->__("Magento Api"),
              "title" => $this->__("Magento Api")
	   ));

    $this->renderLayout(); 
  
  }*/

  /*
  * @ For checking we have make test function, you can direct hit: YOUR_MAGENTO_URL/emizenstore/magentoapi/index/test then check output of your created api's
  */
  public function testAction() {

    if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api')) // if not enable extension return false
      return;

    try
      {
        $client = new SoapClient(Mage::getBaseUrl().'api/soap/?wsdl'); // shop api class with magento root URL
        $session = $client->login($this->login_user, $this->login_password); // login api with user and password to get session id

        // Edit and modufy default home cms page
        //$date = $client->call($session, 'magentoapi_api.run');

        // Home Slider Images Api
        //$date = $client->call($session, 'magentoapi_api.homeslides');

        // Home Banner Images Api
        //$date = $client->call($session, 'magentoapi_api.homebanner');

        // Home Page New Products Api
        //$date = $client->call($session, 'magentoapi_api.newproducts');

        // Get Footer Links
        //$date = $client->call($session, 'magentoapi_api.footercmsmenu', array('company'));

        // Get Product According To Category Id
        //$date = $client->call($session, 'magentoapi_api.productsbycategoryid', array('catId'=>'15','sort_order'=>'name','dir'=>'DESC'));

        // Get Product According To Product Id
        //$date = $client->call($session, 'magentoapi_api.productdetailbyid', array('421'));

        /*$collection = Mage::getModel('catalog/product')->load('883');

        // showing configurable products attributes like size,color,shoe size etc.
        if($collection->getData('type_id') == 'configurable') // check if product type is configurable or not
        {
            $productAttributeOptions = $collection->getTypeInstance(true)->getConfigurableAttributesAsArray($collection);
            foreach($productAttributeOptions as $_attribute)
            {
                foreach($_attribute['values'] as $attribute)
                {
                    $product['configurable'][$_attribute['label']][$attribute['value_index']] = $attribute['store_label'];
                    echo "<pre>"; print_r($attribute);
                }     
            }
        }*/

        // Add To Product In Wishlist
        //$date = $client->call($session, 'magentoapi_api.wishlistadd', array('138','399'));

        // Newsletter
        //$date = $client->call($session, 'magentoapi_api.newsletter', array('kk@kk.com'));

        // Contact Form Api
        //$date = $client->call($session, 'magentoapi_api.contact', array('dfsdfs','navneetsharma748@gmail.com','23423','asdasdas'));

        // Create cart
        //$cart_id = $client->call($session, 'cart.create',array('1'));

        // add to cart: array configurable products parameter
        /*$arrProducts = array(
                                array(
                                    "product_id" =>"421",
                                    "qty" => 1,
                                    "super_attribute" => array(         
                                        180 => 79,
                                        92 => 22, // 92 is option id and 22 is option value
                                    )
                                )
                            );*/

        // add to cart: array grouped products parameter
        /*$arrProducts = array(
                                array(
                                    "product_id" =>"555",
                                    "qty" => 1,
                                    "super_group" => array(         
                                        92 => 1, // 92 is product id and 1 is quantity
                                        
                                    )
                                )
                            );*/

        /*$arrProducts = array(
                                array(
                                    "product_id" =>"554",
                                    "qty" => 1,
                                )
                            );*/

        // add product in the cart(quote)
        //$date6 = $client->call($session, 'cart_product.add', array('749',$arrProducts,'1'));

        //Product remove to cart(quote)
        //$date1 = $client->call($session, 'cart_product.remove', array('749',$arrProducts,'1'));

        // cart(quote) number of product lists
        //$date = $client->call($session, 'cart_product.list', array('749','1'));

        // add to wishlist
        //$date = $client->call($session, 'magentoapi_api.wishlistadd', array('140','553'));

        

        echo "<pre>"; print_r($date); die;
        //echo $date;
        //echo $cart_id;

      }
    catch(Exception $e)
    {
      echo '<pre>'; print_r($e);
    }  

  }
}
