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

class Plumrocket_Productsfilter_Model_Options extends Plumrocket_Productsfilter_Model_Abstract
{
	/*
	$this->_optionsList = {
		attribute code => {
			'label' => attribute label,
			'items' => {
				normalized option value => {
					'label' => label of option,
					'ids'  => [
						product id,
						...
					],
					'opt_id' => option id from sql result
					parent: for category
				},
				...
			}
		},
		...
	} */
	protected $_optionsList = null;

	protected $_ids = null;
	protected $_idsByGroups = array();
	protected $_idsExludeGroups = array();

	protected $_categoriesRelations = array();
	protected $_categoriesUrlKeys = array();

	/*
	$this->_sourceValues = {
		attribute_code => {
			value => lavel,
			...
		},
		...
	}
	*/
	protected $_sourceValues = array();
	protected $_weOnSearchPage = false;

	public function getFilteredProductIds($filterParams = null)
	{
		$currentFilterParams = is_null($filterParams);

		if ($currentFilterParams && !is_null($this->_ids)) {
			return array_keys($this->_ids);
		}

		$called = false;
		$selectedIds = array();
		$byGroups = array();

		if (Mage::helper('productsfilter')->moduleEnabled()) {
			Varien_Profiler::start("Product Filter get Ids");

			if ($currentFilterParams) {
				$filterParams = Mage::getSingleton('productsfilter/router')->getSelectedParams();
			}
			if (sizeof($filterParams) > 0) {	
				$optionsList = $this->getItems();

				// by groups
				$relations = Mage::getSingleton('productsfilter/attributes')->getRelations();
				foreach ($filterParams as $code => $filterItem) {
					if (isset($optionsList[$code])) {
						$toCode = (isset($relations[$code]) && $relations[$code]['parent'])? $relations[$code]['parent']: $code;

						if (!isset($byGroups[$toCode])) {
							$byGroups[$toCode] = array();
						}

						foreach ($filterItem['items'] as $search_option => $_) {
							if (array_key_exists($search_option, $optionsList[$code]['items'])) {
								// get ids together
								//$byGroups[$toCode] = array_merge($byGroups[$toCode], $optionsList[$code]['items'][$search_option]['ids']);
								$byGroups[$toCode] += array_flip($optionsList[$code]['items'][$search_option]['ids']);
								$called = true;
							}
						}
					}
				}

				// summary
				$first = true;
				//$allIds = array();
				foreach ($byGroups as $code => $ids) {
					if ($first) {
						$selectedIds = $ids;
						$first = false;
					} else {
						// get common items
						//$selectedIds = array_intersect($selectedIds, $ids);
						$selectedIds = array_intersect_key($selectedIds, $ids);
					}

					foreach ($byGroups as $_code => $_) {
						if ($code == $_code) {
							continue;
						}

						if (array_key_exists($_code, $this->_idsExludeGroups)) {
							$this->_idsExludeGroups[ $_code ] = array_intersect_key($this->_idsExludeGroups[ $_code ], $ids);
						} else {
							$this->_idsExludeGroups[ $_code ] = $ids;
						}
					}

					//$allIds = array_merge($allIds, $ids);
					//$allIds += $ids;
				}

				/*
				foreach ($byGroups as $code => $ids) {
					//$diff = array_diff($allIds, $ids);
					$diff = array_diff_key($allIds, $ids);
					//$this->_idsExludeGroups[$code] = ($diff)? array_unique(array_merge($diff, $selectedIds)): array();
					$this->_idsExludeGroups[$code] = ($diff)? $diff + $selectedIds: array();
				}*/
			}
			Varien_Profiler::stop("Product Filter get Ids");
		}
		$result = ($called)? $selectedIds: false;
		if ($currentFilterParams) {
			$this->_ids = $result;
			$this->_idsByGroups = $byGroups;
		}
		return $result? array_keys($result): false;
	}

	public function filterCollection($filterParams = null)
	{
		if (Mage::helper('productsfilter')->moduleEnabled()
			&& $this->getCollection() 
		) {
			$ids = $this->getFilteredProductIds($filterParams);

			if ($ids !== false) {
				if ($ids) {
					$this->getCollection()->addFieldToFilter('entity_id', array('in' => $ids));
				} else {
					$this->getCollection()->addFieldToFilter('entity_id', '0');
				}
			}
		}
		return $this;
	}

	public function getItems()
	{
		if (is_null($this->_optionsList)) {
			$this->_optionsList = array();
			Varien_Profiler::start("Product Filter getItems");
			
			if (Mage::helper('productsfilter')->moduleEnabled()) {
				$cache = $this->_getCache();
				if ($cache) {
					$this->_optionsList = $cache;
					return $this->_optionsList;
				}

				// load sql for fetch attributes list
				$sqlModel = Mage::getModel('productsfilter/sql')
					->setCollection($this->getCollection())
					->setAttributes($this->getAttributes());
				$sql = $sqlModel->getPlain();

				if ($sql) {
					$attrModel = Mage::getSingleton('productsfilter/attributes');

					Mage::getSingleton('core/resource')->getConnection('productsfilter_read')->query("SET SESSION group_concat_max_len = 1000000;");
					// attribute_id => {colb => '', values => {v_value => []}
					$optionsData = $this->_compactItems(
						Mage::getSingleton('core/resource')->getConnection('productsfilter_read')->fetchAll($sql)
					);

					foreach ($optionsData as $attrId => $attrData) {
						$attribute = $attrModel->getByIdOrCustomAttribute($attrId, $attrData['colb']);
						$code = $attribute->getData('attribute_code');
						$isPriceAttribute = $attribute->getFrontendInput() == 'price';
						$sourceValues = $this->_getAttributeSource($attribute, $attrData);

						$label = $attribute->getStoreLabel();
						if (empty($label)) {
							$label = $attribute->getFrontendLabel();
						}

						$this->_optionsList[ $code ] = array(
							'label' => $label,
							'items' => array()
						);

						foreach ($attrData['values'] as $v_value => $ids) {
							$opt_id = $v_value;

							$ids = $sqlModel->getIds($ids, $isPriceAttribute);
							if (!$ids) {
								continue;
							}

							$norm_option_key = $this->getNormalOptionKey($isPriceAttribute, $v_value);
							if (!$norm_option_key) {
								continue;
							}

							if ($isPriceAttribute) {
								// price must me int, not float
								$v_value = $norm_option_key;
							} elseif (array_key_exists($v_value, $sourceValues)) {
								$v_value = $sourceValues[$v_value];
								// generate key again for changed key value
								if ($code != Plumrocket_Productsfilter_Model_Attributes::CATEGORY_CODE) {
									$norm_option_key = $this->getNormalOptionKey($isPriceAttribute, $v_value);
								}
							}
							$this->_processOptionItem($code, $norm_option_key, $v_value, $opt_id, $ids);
						}

						if ($isPriceAttribute) {
							$this->_processPriceAttribute($code);
						} else {
							// sort options
							uasort($this->_optionsList[ $code ]['items'], array($attrModel, 'sort'));

							if ($code == Plumrocket_Productsfilter_Model_Attributes::CATEGORY_CODE) {
								$this->_sortCategoryItems($code);
								$this->_changeCategoryKeys($code);
							}
						}
					}

					$this->_setCache($this->_optionsList);
				}
			}
			Varien_Profiler::stop("Product Filter getItems");
		}
		return $this->_optionsList;
	}

	protected function _compactItems($sourceItems)
	{
		$items = array();
		$attrModel = Mage::getSingleton('productsfilter/attributes');

		foreach ($sourceItems as $data) {
			if (! array_key_exists($data['attribute_id'], $items)) {
				$items[ $data['attribute_id'] ] = array(
					'colb' 		=> $data['colb'],
					'values' 	=> array(),
				);
			}

			$attribute = $attrModel->getById($data['attribute_id']);
			if (($attribute !== false) && ($attribute->getData('frontend_input') == 'multiselect')) {
				$values = explode(',', $data['v_value']);
				foreach ($values as $val) {
					$items[ $data['attribute_id'] ]['values'][ $val ]  = explode(',', $data['entity_ids']);
				}
			} else {
				$items[ $data['attribute_id'] ]['values'][ $data['v_value'] ]  = explode(',', $data['entity_ids']);
			}
		}
		return $items;
	}

	public function getNormalOptionKey($isPriceAttribute, $key)
	{
		return $isPriceAttribute ?
			(int)$key : strtolower( preg_replace("/[^0-9A-Za-z.]/", '_', (string)$key) );
	}

	protected function _processOptionItem($code, $key, $label, $opt_id, $ids = array())
	{
		if (! array_key_exists($key, $this->_optionsList[ $code ]['items'])) {
			$this->_optionsList[ $code ]['items'][ $key ] = array(
				'label' 	=> $label,
				'ids' 		=> $ids,
				'opt_id'	=> $opt_id,
			);
		} elseif ($ids) {
			$this->_optionsList[ $code ]['items'][ $key ]['ids'] = array_merge(
				$this->_optionsList[ $code ]['items'][ $key ]['ids'], 
				$ids
			);
		}
	}

	protected function _getAttributeSource($attribute, $attrData)
	{
		$code = $attribute->getData('attribute_code');

		if (! isset($this->_sourceValues[$code])) {
			$this->_sourceValues[$code] = array();

			if (in_array($attribute->getData('frontend_input'), array('select', 'multiselect')) && $attribute->usesSource()) {
				$options = $attribute->getSource()->getAllOptions(false);
				foreach ($options as $item) {
					$value = $item['value'];
					if (is_string($value) || is_numeric($value)) {
						$this->_sourceValues[$code][(string)$value] = $item['label'];
					}
				}
			}

			// form values for categories list
			if ($attribute->getId() == Plumrocket_Productsfilter_Model_Attributes::CATEGORY_ID) {
				$cids = array_keys($attrData['values']);
				$categories = Mage::getModel('catalog/category')
					->getCollection()
					->addIsActiveFilter()
					->addAttributeToSelect('name')
					->addAttributeToSelect('url_key')
					->addIdFilter($cids);

				foreach ($categories as $cat) {
					$this->_sourceValues[$code][ $cat->getId() ] = $cat->getName();
					$this->_categoriesUrlKeys[ $cat->getId() ] = $cat->getUrlKey();
				}

				foreach ($categories as $cat) {
					if (array_key_exists($cat->getParentId(), $this->_sourceValues[$code])) {
						$this->_categoriesRelations[ $cat->getParentId() ][] = $cat->getId();
					} else {
						// single item should be present as parent item
						$this->_categoriesRelations[ $cat->getId() ] = array();
					}
				}
			}
		}
		return $this->_sourceValues[$code];
	}

	protected function _sortCategoryItems($code)
	{
		$oldItems = $this->_optionsList[ $code ]['items'];
		$this->_optionsList[ $code ]['items'] = array();

		foreach ($oldItems as $key => $item) {
			if (array_key_exists($key, $this->_categoriesRelations)) {
				$this->_optionsList[ $code ]['items'][ $key ] = $item;
				foreach ($this->_categoriesRelations[$key] as $ckey) {
					$this->_optionsList[ $code ]['items'][ $ckey ] = $oldItems[ $ckey ];
					$this->_optionsList[ $code ]['items'][ $ckey ]['parent'] = $key;
				}
			}
		}
	}

	protected function _changeCategoryKeys($code)
	{
		$oldItems = $this->_optionsList[ $code ]['items'];
		$this->_optionsList[ $code ]['items'] = array();

		foreach ($oldItems as $key => $item) {
			$norm_option_key = str_replace('-', '_', $this->_categoriesUrlKeys[$key]);
			if (array_key_exists($norm_option_key, $this->_optionsList[ $code ]['items'])) {
				$norm_option_key .= '_' . $key;
			}
			$this->_optionsList[ $code ]['items'][ $norm_option_key ] = $item;
		}
	}

	protected function _processPriceAttribute($code)
	{
		$data = $this->_optionsList[ $code ]['items'];
		$this->_optionsList[ $code ]['items'] = array();
		ksort($data);

		$priceCounts = sizeof($data);
		$segmentsCount = 4;
		$segmentsPercent = 80; // 10 | 20 | 20 | 20 | 10

		if ($priceCounts < 10) {
			$segmentsCount = 1;
			$segmentsPercent = 40; // 30 | 40 | 30
		} elseif ($priceCounts < 20) {
			$segmentsCount = 2;
			$segmentsPercent = 60; // 20 | 30 | 30 | 20
		} elseif ($priceCounts < 30) {
			$segmentsCount = 3;
			$segmentsPercent = 66; // 17 | 22 | 22 | 22 | 17
		}

		// get start and end dates
		$prices = array_keys($data);
		$startPrice = reset($prices);
		$endPrice = end($prices);

		// get left and segment price range
		$rangePrice = $endPrice - $startPrice;
		$rangeLeft = round($rangePrice * (100 - $segmentsPercent) / 2 / 100);
		$rangeSegment = round($rangePrice * $segmentsPercent / $segmentsCount / 100);

		$result = array();
		// left block
		$result[0] = array('from' => $startPrice, 'to' => $startPrice + $rangeLeft, 'ids' => array());
		// segments
		for ($i = 1; $i <= $segmentsCount; $i++) {
			$result[$i] = array('from' => $result[$i - 1]['to'] + 1, 'to' => $result[$i - 1]['to'] + $rangeSegment, 'ids' => array());
		}
		// right block
		$result[$i] = array('from' => $result[$i - 1]['to'] + 1, 'to' => $endPrice + 1, 'ids' => array());
		$endIndex = $i;

		// explode ids to segments
		$active = 0;
		foreach ($data as $price => $item) {
			while ($price > $result[$active]['to']) {
				$active++;
			}

			$result[$active]['ids'] = array_merge($result[$active]['ids'], $item['ids']);
		}

		$_helper = Mage::helper('checkout');
		foreach ($result as $i => $item) {

			$label = Mage::helper('productsfilter')->__('%s to %s', $_helper->formatPrice($item['from']), $_helper->formatPrice($item['to']));
			if ($i == 0) {
				$label = Mage::helper('productsfilter')->__('Under %s', $_helper->formatPrice($item['to'] + 1));
			} elseif ($i == $endIndex) {
				$label = Mage::helper('productsfilter')->__('%s & Above', $_helper->formatPrice($item['from']));
			}

			$ids = array_unique($result[$i]['ids']);
			if ($ids) {
				$this->_optionsList[ $code ]['items'][ $item['from'] . '_' . $item['to'] ] = array(
					'label' => $label,
					'ids' => $ids
				);
			}
		}
	}

	public function getOptionProductCountForCurrentFilterState($code, $option)
	{
		$result = array();
		if (Mage::helper('productsfilter')->moduleEnabled()) {
			$options = $this->getItems();
			$relations = Mage::getSingleton('productsfilter/attributes')->getRelations();

			if (isset($options[$code])
				&& array_key_exists($option, $options[$code]['items'])
			) {
				$exludeCode = (isset($relations[$code]) && $relations[$code]['parent'])? $relations[$code]['parent']: $code;
				$ids = $options[$code]['items'][$option]['ids'];
				// if no one option selected
				if ($this->_ids === false) {
					$result = $ids;
				} else {
					// if this group has selected options
					// and selected option(s) in at least one another group
					if (array_key_exists($exludeCode, $this->_idsExludeGroups)) {
						$result = $this->_intersect($ids, $this->_idsExludeGroups[ $exludeCode ]);
					} else {
						// if one selected current group
						if (isset($this->_idsByGroups[$exludeCode])) {
							$result = $ids;
						} else {
							$result = $this->_intersect($ids, $this->_ids);
						}
					}
				}
			}
		}
		return count($result);
	}

	protected function _intersect($arr1, $keys)
	{
		$result = array();
		foreach ($arr1 as $id) {
			if (array_key_exists($id, $keys)) {
				$result[ $id ] = true;
			}
		}
		return $result;
	}

	private function _getTextCacheKey()
	{
		//$sqlHash = md5( $this->getCollection()->getSelect()->assemble() );
		$id = 0;
		$_current_category = Mage::registry('current_category');
		if ($_current_category) {
			$id = (int)$_current_category->getId();
		}
		return 'product_filter_category_' . $id;// . '_' . $sqlHash;
	}

	private function _getCache()
	{
		if ($this->_weOnSearchPage) {
			return '';
		}
		return json_decode(Mage::app()->getCacheInstance()->load(
			$this->_getTextCacheKey()
		), true);
	}

	private function _setCache($arr)
	{
		Mage::app()->getCacheInstance()->save(
			json_encode($arr), 
			$this->_getTextCacheKey(), 
			array('productsfilter'), 
			1800
		);
	}

	public function weOnSearchPage()
	{
		$this->_weOnSearchPage = true;
	}
}
