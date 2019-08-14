<?php
class EmizenTech_MagentoApi_Model_Checkout_Cart_Customer_Api extends Mage_Checkout_Model_Cart_Customer_Api
{
	/**
     * Set customer for shopping cart
     *
     * @param int $quoteId
     * @param array|object $customerData
     * @param int | string $store
     * @return int
     */
    public function set($quoteId, $customerData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);

        $customerData = $this->_prepareCustomerData($customerData);
        if (!isset($customerData['mode'])) {
            $this->_fault('customer_mode_is_unknown');
        }

        switch($customerData['mode']) {
        case self::MODE_CUSTOMER:
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getCustomer($customerData['entity_id']);
            $customer->setMode(self::MODE_CUSTOMER);
            break;

        case self::MODE_REGISTER:
        case self::MODE_GUEST:
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')
                ->setData($customerData);

            if ($customer->getMode() == self::MODE_GUEST) {
                $password = $customer->generatePassword();

                $customer
                    ->setPassword($password)
                    ->setConfirmation($password);
            }

            $isCustomerValid = $customer->validate();
            if ($isCustomerValid !== true && is_array($isCustomerValid)) {
                $this->_fault('customer_data_invalid', implode(PHP_EOL, $isCustomerValid));
            }
            break;
        }

        try {
            $quote
                ->setCustomer($customer)
                ->setCheckoutMethod($customer->getMode())
                ->setPasswordHash($customer->encryptPassword($customer->getPassword()))
                ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('customer_not_set', $e->getMessage());
        }

        return true;
    }

    /**
     * @param  int $quoteId
     * @param  array of array|object $customerAddressData
     * @param  int|string $store
     * @return int
     */
    public function setAddresses($quoteId, $customerAddressData, $store = null)
    {
		Mage::log(var_export($quoteId , true), null , "api_address.log");
		Mage::log(var_export($customerAddressData , true), null , "api_address.log");
        $quote = $this->_getQuote($quoteId, $store);

        $customerAddressData = $this->_prepareCustomerAddressData($customerAddressData);
        if (is_null($customerAddressData)) {
            $this->_fault('customer_address_data_empty');
        }
        
        foreach ($customerAddressData as $addressItem) {
//            switch($addressItem['mode']) {
//            case self::ADDRESS_BILLING:
                /** @var $address Mage_Sales_Model_Quote_Address */
                $address = Mage::getModel("sales/quote_address");
//                break;
//            case self::ADDRESS_SHIPPING:
//                /** @var $address Mage_Sales_Model_Quote_Address */
//                $address = Mage::getModel("sales/quote_address");
//                break;
//            }
            $addressMode = $addressItem['mode'];
            unset($addressItem['mode']);

            if (!empty($addressItem['entity_id'])) {
                $customerAddress = $this->_getCustomerAddress($addressItem['entity_id']);
                if ($customerAddress->getCustomerId() != $quote->getCustomerId()) {
                    $this->_fault('address_not_belong_customer');
                }
                $address->importCustomerAddress($customerAddress);

            } else {
                $address->setData($addressItem);
            }
            
			if(!empty($addressItem['region_id'])){
				$address->setRegionId($addressItem['region_id']);
			}

			Mage::log(var_export($addressItem , true), null , "api_address.log");
			Mage::log(var_export($address->debug() , true), null , "api_address.log");
            $address->implodeStreetAddress();

            if (($validateRes = $address->validate())!==true) {
                $this->_fault('customer_address_invalid', implode(PHP_EOL, $validateRes));
            }

            switch($addressMode) {
            case self::ADDRESS_BILLING:
                $address->setEmail($quote->getCustomer()->getEmail());

                if (!$quote->isVirtual()) {
                    $usingCase = isset($addressItem['use_for_shipping']) ? (int)$addressItem['use_for_shipping'] : 0;
                    switch($usingCase) {
                    case 0:
                        $shippingAddress = $quote->getShippingAddress();
                        $shippingAddress->setSameAsBilling(0);
                        break;
                    case 1:
                        $billingAddress = clone $address;
                        $billingAddress->unsAddressId()->unsAddressType();

                        $shippingAddress = $quote->getShippingAddress();
                        $shippingMethod = $shippingAddress->getShippingMethod();
                        $shippingAddress->addData($billingAddress->getData())
                            ->setSameAsBilling(1)
                            ->setShippingMethod($shippingMethod)
                            ->setCollectShippingRates(true);
                        break;
                    }
                }
                $quote->setBillingAddress($address);
                break;

            case self::ADDRESS_SHIPPING:
                $address->setCollectShippingRates(true)
                        ->setSameAsBilling(0);
                $quote->setShippingAddress($address);
                break;
            }

        }
        
        try {
            $quote
                ->collectTotals()
                ->save();
        } catch (Exception $e) {
            $this->_fault('address_is_not_set', $e->getMessage());
        }

        return true;
    }

    /**
     * Prepare customer entered data for implementing
     *
     * @param  array $customerData
     * @return array
     */
    protected function _prepareCustomerData($data)
    {
        foreach ($this->_attributesMap['quote_customer'] as $attributeAlias=>$attributeCode) {
             if(isset($data[$attributeAlias]))
             {
                 $data[$attributeCode] = $data[$attributeAlias];
                 unset($data[$attributeAlias]);
             }
         }
        return $data;
    }

    /**
     * Prepare customer entered data for implementing
     *
     * @param  array $data
     * @return array
     */
    protected function _prepareCustomerAddressData($data)
    {
        if (!is_array($data) || !is_array($data[0])) {
            return null;
        }
        
        $dataAddresses = array();
        foreach($data as $addressItem) {
            foreach ($this->_attributesMap['quote_address'] as $attributeAlias=>$attributeCode) {
                 if(isset($addressItem[$attributeAlias]))
                 {
                     $addressItem[$attributeCode] = $addressItem[$attributeAlias];
                     unset($addressItem[$attributeAlias]);
                 }
            }
            $dataAddresses[] = $addressItem;
        }
        return $dataAddresses;
    }
}
?>