<?php
class EmizenTech_MagentoApi_Model_Observer
{

	public function RunWizard(Varien_Event_Observer $observer)
	{
		if(!Mage::getStoreConfig('emizen_api/emizen_api/emizen_api'))
			return;

		$cmsModelBanner = Mage::getModel('cms/block')->load('home-banner'); 

		if(!$cmsModelBanner->getId()) //Returns true if does not exist.
		{
			$block = Mage::getModel('cms/block');
		    $block->setTitle('Home Banner');
		    $block->setIdentifier('home-banner');
		    $block->setStores(0);
		    $block->setIsActive(1);
		    $block->setContent('<ul class="promos"><li><a href="{{config path="web/secure/base_url"}}home-decor.html"><img src="{{media url="wysiwyg/homepage-three-column-promo-01B.png"}}" alt="Physical &amp; Virtual Gift Cards" /></a></li><li><a href="{{config path="web/secure/base_url"}}vip.html"><img src="{{media url="wysiwyg/homepage-three-column-promo-02.png"}}" alt="Shop Private Sales - Members Only" /></a></li><li><a href="{{config path="web/secure/base_url"}}accessories/bags-luggage.html"><img src="{{media url="wysiwyg/homepage-three-column-promo-03.png"}}" alt="Travel Gear for Every Occasion" /></a></li></ul>');
		    $block->save();

		    // check and create directory
		    /*$folder_obj = new Varien_Io_File();
			$check_exits = Mage::getBaseDir('media').'/wysiwyg/promo-cards';
			if(!is_dir($check_exits))
		    {
		    	$folder_obj->mkdir(Mage::getBaseDir('media').'/wysiwyg/promo-cards');
		    }
		    else
		    {
		    	die('Error: Directory already exists');
		    }*/
		}

		$cmsModelSlider = Mage::getModel('cms/block')->load('home-slider'); 

		if(!$cmsModelSlider->getId()) //Returns true if does not exist.
		{
			// check and create static block
			$block_slider = Mage::getModel('cms/block');
		    $block_slider->setTitle('Home Slider');
		    $block_slider->setIdentifier('home-slider');
		    $block_slider->setStores(0);
		    $block_slider->setIsActive(1);
		    $block_slider->setContent('<ul class="slideshow"><li><a href="{{config path="web/secure/base_url"}}accessories/eyewear.html"><img src="{{media url="wysiwyg/slide-1.jpg"}}" alt="An eye for detail - Click to Shop Eye Wear" /></a></li><li><a href="{{config path="web/secure/base_url"}}women.html"><img src="{{media url="wysiwyg/slide-2.jpg"}}" alt="Style solutions - covet-worthy styles in travel-friendly fabrics - Click to Shop Woman" /></a></li><li><a href="{{config path="web/secure/base_url"}}men.html"><img src="{{media url="wysiwyg/slide-3.jpg"}}" alt="Wing man - hit the runway in stylish separates and casuals - Click to Shop Man" /></a></li></ul>');
		    $block_slider->save();

		    // check and create directory
		    /*$folder_obj = new Varien_Io_File();
			$check_exits = Mage::getBaseDir('media').'/wysiwyg/home-slide';
			if(!is_dir($check_exits))
		    {
		    	$folder_obj->mkdir(Mage::getBaseDir('media').'/wysiwyg/home-slide');
		    }
		    else
		    {
		    	die('Error: Directory already exists');
		    }*/

		    // check if home cms page exists then will be modify from this content because above creating static block and directory
		    $cms_page = Mage::getModel('cms/page');
	    	$cms_page->load('home', 'identifier');
	    	$cms_page->setContent('<div class="slideshow-container">{{block type="cms/block" block_id="home-slider"}}<div class="slideshow-pager">&nbsp;</div><span class="slideshow-prev">&nbsp;</span> <span class="slideshow-next">&nbsp;</span></div> {{block type="cms/block" block_id="home-banner"}}');
	    	$cms_page->save();
		}
	}
		
}
