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

class Plumrocket_Productsfilter_Model_Sql extends Plumrocket_Productsfilter_Model_Abstract
{
	protected $_resource 	= null;

	protected $_inputIds	= array();
	protected $_allIds 		= array();
	protected $_relations	= array();

	public function getPlain()
	{
		if (Mage::helper('productsfilter')->moduleEnabled() 
			&& $this->getAttributes()
		) {
			$this->_resource = Mage::getSingleton('core/resource');
			$this->_loadProductsIds()->_loadChildIds();

			return sprintf("
				SELECT 
					`p`.`attribute_id`,
					GROUP_CONCAT(`p`.`entity_id` SEPARATOR ',') as entity_ids,
					`p`.v_value,
					`p`.colb
				FROM 
					(%s) as `p`
				GROUP BY `p`.`attribute_id`, `p`.`v_value`",
				$this->_getAttributesSql()
			);
		}
		return '';
	}

	public function getIds($ids, $isPrice = false)
	{
		$result = array();

		foreach ($ids as $key => $id) {
			// if id has parent id
			if (array_key_exists($id, $this->_relations)) {
				if (!$isPrice) {
					$result[ $this->_relations[$id] ] = true;
				}

				if (array_key_exists($id, $this->_inputIds)) {
					$result[ $id ] = true;
				}
			} else {
				$result[ $id ] = true;
			}
		}
		return array_keys($result);
	}

	public function __toString()
	{
		return $this->getPlain();
	}

	protected function _getAttributesSql()
	{
		$ids = $this->_format($this->_allIds);

		/*
		$selectArr = [
			db_table = [
				attr type = [
					attr code => attr id,
					...
				],
				...
			],
			...
		]
		*/
		$selectArr = array();
		foreach ($this->getAttributes() as $code => $attribute) {
			if ($attribute->getBackendTable() && $attribute->getBackendType()) {
				$selectArr[ $attribute->getBackendTable() ][ $attribute->getBackendType() ][ $code ] = $attribute->getId();
			}
		}

		$sqlArr = array();
		foreach ($selectArr as $table => $types) {
			foreach ($types as $type => $items) {
				if ($type == 'static') {
					foreach ($items as $code => $id) {
						if ($id == Plumrocket_Productsfilter_Model_Attributes::CATEGORY_ID) {

							$categoryTable = Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
							$level = $this->_getLayer()->getCurrentCategory()->getLevel();
							$currId = $this->_getLayer()->getCurrentCategory()->getId();

							$sqlArr[] = "(
								SELECT 
									'{$id}' as `attribute_id`, 
									`main_table`.`product_id` as `entity_id`, 
									CAST(`main_table`.`{$code}` as CHAR) as v_value, 
									'' as colb 
								FROM {$table} as `main_table`
									INNER JOIN {$categoryTable} as `cat_table` ON `main_table`.`category_id` = `cat_table`.`entity_id`
								WHERE `main_table`.`product_id` IN ({$ids})
									AND `main_table`.`{$code}` <> ''
									AND `main_table`.`store_id` = '" . Mage::app()->getStore()->getStoreId() ."'
									AND `cat_table`.`level` > {$level} AND `cat_table`.`level` < " . ($level + 3) . "
									AND (`cat_table`.`path` LIKE '{$currId}/%'
										OR `cat_table`.`path` LIKE '%/{$currId}/%'
									)
								)";
						} else {
							$sqlArr[] = "(
							SELECT 
								'{$id}' as `attribute_id`, 
								`entity_id`, 
								CAST(`{$code}` as CHAR) as v_value, 
								'' as colb 
							FROM {$table}
							WHERE `entity_id` IN ({$ids})
								AND `{$code}` <> ''
							)";							
						}
					}

				} else {
					$sqlArr[] = sprintf("(
						SELECT 
							`attribute_id`,
							`entity_id`, 
							CAST(`value` as CHAR) as v_value,
							'' as `colb`
						FROM %s 
						WHERE `entity_id` IN (%s)
							AND NOT ISNULL(`value`)
							AND `value` <> ''
							AND `attribute_id` IN (%s)
							AND `store_id` = 0
						)",
						$table,
						$ids,
						implode(',', array_values($items))
					);
				}
			}
			//$type == 'varchar'
		}

		// Custom options
		if (Mage::helper('productsfilter')->customOptionsEnables()) {
			$sqlArr[] = sprintf("(
				SELECT 
					CONCAT('co_', md5(`t_option_title`.`title`)) as `attribute_id`, 
					`t_option`.`product_id` as `entity_id`, 
					`t_option_type_title`.`title` as `v_value`, 
					`t_option_title`.`title` as `colb`
				FROM 
					%s `t_option` 
					LEFT JOIN `%s` `t_option_type_value`
						ON `t_option_type_value`.`option_id` = `t_option`.`option_id`

					LEFT JOIN %s `t_option_type_title`
						ON `t_option_type_title`.`option_type_id` = `t_option_type_value`.`option_type_id`
							AND `t_option_type_title`.`store_id` = 0

					LEFT JOIN %s `t_option_title` 
						ON `t_option`.`option_id` = `t_option_title`.`option_id`
							AND `t_option_title`.`store_id` = 0
				WHERE `t_option`.`product_id` IN (%s)
					AND `t_option_type_title`.`title` <> ''
				)",
				$this->_resource->getTableName('catalog/product_option'),
				$this->_resource->getTableName('catalog/product_option_type_value'),
				$this->_resource->getTableName('catalog/product_option_type_title'),
				$this->_resource->getTableName('catalog/product_option_title'),
				$ids
			);
		}

		return implode(' UNION ', $sqlArr);
	}

	protected function _format($array)
	{
		if (!$array){
			$array[0] = true;
		}
		return implode(',', array_keys($array));
	}

	protected function _loadProductsIds()
	{
		//$sql = $this->_getBaseProductCollection()->getSelect()
		$coll = clone $this->getCollection()->getSelect();
		$sql = $coll
			->reset(Zend_Db_Select::COLUMNS)
			->reset(Zend_Db_Select::LIMIT_COUNT)
			->reset(Zend_Db_Select::LIMIT_OFFSET)
			->reset(Zend_Db_Select::ORDER)
			->columns('e.entity_id')
			->assemble();
		$array = $this->_resource->getConnection('productsfilter_read')->fetchAll($sql);
		foreach ($array as $item) {
			$this->_inputIds[ $item['entity_id'] ] = true;
			$this->_allIds[ $item['entity_id'] ] = true;
		}
		return $this;
	}

	protected function _loadChildIds()
	{
		// get children product
		$sql = sprintf("SELECT `child_id`, `parent_id` FROM %s WHERE `parent_id` IN (%s)",
			$this->_resource->getTableName('catalog/product_relation'),
			$this->_format($this->_inputIds)
		);
		$array = $this->_resource->getConnection('productsfilter_read')->fetchAll($sql);
		foreach ($array as $item) {
			$this->_allIds[ $item['child_id'] ] = true;
			$this->_relations[ $item['child_id'] ] = $item['parent_id'];
		}

		return $this;
	}

	/*
	protected function _getBaseProductCollection()
	{
		$layer = $this->_getLayer();
		$collection = $layer->getCurrentCategory()->getProductCollection();
		$collection
			->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
			->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addUrlRewrite($layer->getCurrentCategory()->getId());

		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

		return $collection;
	}*/

	protected function _getLayer()
	{
		$layer = Mage::registry('current_layer');
		if ($layer) {
			return $layer;
		}
		return Mage::getSingleton('catalog/layer');
	}
}
