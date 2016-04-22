<?php

/*

Plumrocket Inc.

NOTICE OF LICENSE

This source file is subject to the End-user License Agreement
that is available through the world-wide-web at this URL:
http://wiki.plumrocket.net/wiki/EULA
If you are unable to obtain it through the world-wide-web, please
send an email to support@plumrocket.com so we can send you a copy immediately.

DISCLAIMER

Do not edit or add to this file

@package	Plumrocket_Product_Filter-v1.2.x
@copyright	Copyright (c) 2013 Plumrocket Inc. (http://www.plumrocket.com)
@license	http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 
*/

class Plumrocket_Productsfilter_Model_Attributes
{
	protected $_enabledCodes = null;
	protected $_enabledItems = null;
	protected $_enabledItemsById = null;

	protected $_customAttributes = array();

	const CATEGORY_CODE 	= 'category_id';
	const CATEGORY_ID 		= 'ct';

	const FINAL_PRICE_CODE	= 'final_price';
	const FINAL_PRICE_ID	= 'fp';

	/*
	$this->_relations = {
		attribute code => {
			'parent' => parent attribute code or false
			'label' => group label if it is label
			'children' => [
				children code,
				...
			]
		},
		...
	}
	*/
	protected $_relations = null;

	public function toOptionArray()
    {
    	$result = array();
    	$attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
		
		foreach ($attributes as $attribute) {
			if (in_array($attribute->getData('frontend_input'), array('text', 'date', 'boolean', 'multiselect', 'select', 'price'))
				&& ($attribute->getData('frontend_label') != '')
			) {
				$result[] = array(
					'value' => $attribute->getData('attribute_code'),
					'label' => $attribute->getData('frontend_label')
				);
			}
		}
		$result = $this->_appendEmulatedAttributeCodes($result);
		uasort($result, array($this, 'sort'));
        return $result;
    }

    private function _appendEmulatedAttributeCodes($result)
    {
    	// fix for Final Price
		$result[] = array(
			'value' => self::FINAL_PRICE_CODE,
			'label' => Mage::helper('productsfilter')->__('Final Price')
		);
		// add category list
		$result[] = array(
			'value' => self::CATEGORY_CODE,
			'label' => Mage::helper('productsfilter')->__('Categories')
		);
		return $result;
    }

	public function sort($a, $b)
	{
		return strnatcasecmp($a['label'], $b['label']);
	}

    public function getEnabledCodes()
	{
		if (is_null($this->_enabledCodes)) {
			$this->_enabledCodes = array();

			if (Mage::helper('productsfilter')->moduleEnabled()) {
				$source = explode(',', Mage::getStoreConfig('productsfilter/attributes/enable'));

				foreach ($source as $item) {
					if ($item) {
						$this->_enabledCodes[] = $item;
					}
				}
			}
		}
		return $this->_enabledCodes;
	}

	public function getEnabledItems()
	{
		if (is_null($this->_enabledItems)) {
			$this->_enabledItems = array();
			$this->_enabledItemsById = array();

			if (Mage::helper('productsfilter')->moduleEnabled()) {
				$enabledCodes = $this->getEnabledCodes();

				$source = Mage::getResourceModel('catalog/product_attribute_collection')
					->addFieldToFilter('attribute_code', array('in' => $enabledCodes))
					->getItems();
				$source = $this->_appendEmulatedAttributes($source, $enabledCodes);

				$sourceByCodes = array();
				foreach ($source as $item) {
					$sourceByCodes[ $item->getData('attribute_code') ] = $item;
				}

				foreach ($enabledCodes as $code) {
					if (array_key_exists($code, $sourceByCodes)) {
						$item = $sourceByCodes[$code];
						$this->_enabledItems[ $code ] = $item;
						$this->_enabledItemsById[ $item->getId() ] = $item;
					}
				}
			}
		}

		return $this->_enabledItems;
	}

	protected function _appendEmulatedAttributes($source, $enabledCodes)
	{
		// Fix for final price
		if (in_array(self::FINAL_PRICE_CODE, $enabledCodes)) {
			$source[] = $this->_createEmulatedAttribute(array(
				'attribute_code'	=> self::FINAL_PRICE_CODE,
				'frontend_input' 	=> 'price',
				'store_label'		=> Mage::helper('productsfilter')->__('Price'),
				'frontend_label'	=> Mage::helper('productsfilter')->__('Price'),
				'backend_table'		=> Mage::getSingleton('core/resource')->getTableName('catalog_product_index_price'),
				'backend_type'		=> 'static',
				'id'				=> self::FINAL_PRICE_ID,
			));
		}

		// Fix for categories
		if (in_array(self::CATEGORY_CODE, $enabledCodes)) {
			$source[] = $this->_createEmulatedAttribute(array(
				'attribute_code'	=> self::CATEGORY_CODE,
				'frontend_input' 	=> 'varchar',
				'store_label'		=> Mage::helper('productsfilter')->__('Categories'),
				'frontend_label'	=> Mage::helper('productsfilter')->__('Categories'),
				'backend_table'		=> Mage::getSingleton('core/resource')->getTableName('catalog_category_product_index'),
				'backend_type'		=> 'static',
				'id'				=> self::CATEGORY_ID,
			));
		}
		return $source;
	}

	protected function _createEmulatedAttribute($data)
	{
		$keys = array('attribute_code', 'frontend_input', 'store_label', 'frontend_label', 'backend_table', 'backend_type', 'id');
		$values = array();
		foreach ($keys as $key) {
			$values[$key] = (array_key_exists($key, $data))? $data[$key]: '';
		}
		return new Varien_Object($values);
	}

	public function getById($id)
	{
		// if still not init
		$this->getEnabledItems();

		if (array_key_exists($id, $this->_enabledItemsById)) {
			return $this->_enabledItemsById[$id];
		}
		return false;
	}

	public function getByIdOrCustomAttribute($id, $label)
	{
		$attribute = $this->getById($id);
		if ($attribute === false) {
			if (!array_key_exists($id, $this->_customAttributes)) {
				$this->_customAttributes[$id] = $this->_createEmulatedAttribute(array(
					'attribute_code'	=> $id,
					'frontend_input' 	=> 'varchar',
					'store_label'		=> $label,
					'frontend_label'	=> $label,
					'backend_table'		=> '',
					'backend_type'		=> 'static',
					'id'				=> $id,
				));
			}
			$attribute = $this->_customAttributes[$id];
		}
		return $attribute;
	}

	public function getByCode($code)
	{
		if (array_key_exists($code, $this->getEnabledItems())) {
			return $this->_enabledItems[$code];
		}
		return false;
	}

	public function getRelations()
	{
		if (is_null($this->_relations)) {
			$this->_relations = array();

			if (Mage::helper('productsfilter')->moduleEnabled()) {

				$codes = array_keys($this->getEnabledItems());
				foreach ($codes as $code) {
					$this->_relations[$code] = array(
						'label'		=> '',
						'parent' 	=> false,
						'children' 	=> array()
					);
				}

				$groups = Mage::getModel('productsfilter/backend_group')
					->setPath('productsfilter/attributes/group')
					->setValue( Mage::getStoreConfig('productsfilter/attributes/group') )
					->parse()
					->getValue();

				foreach ($groups as $parent => $parentItem) {
					if (! array_key_exists($parent, $this->_relations)) {
						continue;
					}
					$this->_relations[$parent]['label'] = $parentItem['label'];
					foreach ($parentItem['children'] as $child) {
						if (array_key_exists($child, $this->_relations)) {
							$this->_relations[$parent]['children'][] = $child;
							$this->_relations[$child]['parent'] = $parent;
						}
					}
				}
			}
		}
		return $this->_relations;
	}
}
