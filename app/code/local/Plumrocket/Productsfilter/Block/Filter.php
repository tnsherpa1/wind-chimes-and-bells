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

class Plumrocket_Productsfilter_Block_Filter extends Mage_Catalog_Block_Product_List_Toolbar
{ 
	protected $_defaultTemplate = null;

	protected $_swatchEnabled		= false;
	protected $_swatchInnerWidth 	= 0;
	protected $_swatchInnerHeight 	= 0;
	protected $_swatchColorItems	= array();

	protected $_optionsBaseTag 		= '>';
	protected $_infoBaseTag 		= '<';
	protected $_enabled 			= false;

	/* 
	$this->_groupOptions = [
		0 => {
			'label' => group label,
			'multiple' => true if two and more attributes and false if one,
			'children' => [
				attribute code => item from productOptions,
				...
			]
		},
		...
	*/
	protected $_groupOptions 		= null;

	// sidebar
	protected $_differentViews 		= false;
	protected $_asFilterContainer	= false;

	protected function _toHtml()
	{
		if (is_null($this->_defaultTemplate)) {
			$this->_defaultTemplate = $this->getTemplate();
		}
		Varien_Profiler::start("Product Filter _toHtml");

		$html = parent::_toHtml();
		if (Mage::helper('productsfilter')->moduleEnabled() && $this->_enabled) {

			if (! $this->_differentViews || $this->_asFilterContainer) {
				// init swath for rwd theme (Magento 1.9.1)
				$this->_initSwatch();

				// render options block
				$this->setTemplate('productsfilter/options.phtml');
				$optionsHtml = parent::_toHtml();
				// render block with selected options
				$this->setTemplate('productsfilter/info.phtml');
				$infoHtml = parent::_toHtml();
				// return default template
				$this->setTemplate($this->_defaultTemplate);
			}

			if ($this->_differentViews) {
				if ($this->_asFilterContainer) {
					$html = '<div class="productsfilter-main-container">' .$infoHtml . $optionsHtml . '</div>';	
				}
			} else {
				$firstTagPosition = strpos($html, $this->_optionsBaseTag);
				if ($firstTagPosition) {
					$html = substr_replace($html, $optionsHtml, $firstTagPosition + strlen($this->_optionsBaseTag), 0);
				} else {
					$html = $optionsHtml . $html;
				}

				if ($firstTagPosition && ($lastTagPosition = strrpos($html, $this->_infoBaseTag))) {
					$html = substr_replace($html, $infoHtml, $lastTagPosition, 0);
				} else {
					$html .= $infoHtml;
				}
			}
		}
		Varien_Profiler::stop("Product Filter _toHtml");

		return $html;
	}

	protected function _initSwatch()
	{
		$this->_swatchEnabled = Mage::getConfig()->getNode('modules/Mage_ConfigurableSwatches') 
			&& Mage::helper('configurableswatches')->isEnabled();

		if ($this->_swatchEnabled) {
			$_dimHelper = Mage::helper('configurableswatches/swatchdimensions');
			$this->_swatchInnerWidth = $_dimHelper->getInnerWidth(Mage_ConfigurableSwatches_Helper_Swatchdimensions::AREA_LISTING);
			$this->_swatchInnerHeight = $_dimHelper->getInnerHeight(Mage_ConfigurableSwatches_Helper_Swatchdimensions::AREA_LISTING);

			$colorAttr = Mage::getSingleton('productsfilter/attributes')->getByCode('color');
			if ($colorAttr) {
				$this->_swatchColorItems = $colorAttr->getSource()->getAllOptions(false, true);
			}
		}
	}

	public function getSelectedParams()
	{
		return Mage::getSingleton('productsfilter/router')->getSelectedParams();
	}

	public function getAdditionalClasses($code, $val, $item)
	{
		$classes = array();
		$selectedParams = $this->getSelectedParams();

		if (isset($selectedParams[$code]) && array_key_exists($val, $selectedParams[$code]['items'])) {
			$classes[] = 'active-list';
		}

		if ($item['ids'] == 0) {
			$classes[] = 'disabled';
		}

		$classes[] = 'pf_' . $code . '_' . $val;

		if (isset($item['parent']) && $item['parent']) {
			$classes[] = 'child-list';
		}

		return ($classes)? ' ' . implode(' ', $classes): '';
	}

	public function getFilterItemUrl($filter, $value)
    {
        return $this->getPagerUrl(array(
            $filter => $value,
            $this->getPageVarName() => null
        ));
    }

	public function getSwatchUrl($attrCode, $option)
	{
		$url = '';
		if ($this->_swatchEnabled) {

			$label = $option['label'];
			if ($attrCode == 'color') {
				$label = Mage_ConfigurableSwatches_Helper_Data::normalizeKey($label);
		        foreach ($this->_swatchColorItems as $c_opt) {
		            if ($c_opt['value'] == $option['opt_id']) {
		                $label = Mage_ConfigurableSwatches_Helper_Data::normalizeKey($c_opt['label']);
		                break;
		            }
		        }
			}

			$url = Mage::helper('configurableswatches/productimg')->getGlobalSwatchUrl(
				null, 
				$label, 
				$this->getSwatchInnerWidth(), 
				$this->getSwatchInnerHeight()
			);
		}
		return $url;
	}

	public function enableFilter()
	{
		$this->_enabled = true;
	}

	public function setOptionsBaseTag($tag)
	{
		$this->_optionsBaseTag = $tag;
	}

	public function enableDifferentViews()
	{
		$this->_differentViews = true;
	}

	public function setAsFilterContainer()
	{
		$this->_asFilterContainer = true;
	}

	public function setInfoBaseTag($tag)
	{
		$this->_infoBaseTag = $tag;
	}

	public function getSwatchInnerWidth()
	{
		return $this->_swatchInnerWidth;
	}

	public function getSwatchInnerHeight()
	{
		return $this->_swatchInnerHeight;
	}

    public function getMode()
    {
    	return Mage::getStoreConfig('productsfilter/attributes/mode');
    }

	public function getFilterProductOptions() 
	{
		if (is_null($this->_groupOptions)) {
			$this->_groupOptions = array();

			if (Mage::helper('productsfilter')->moduleEnabled()) {
				$options = Mage::getSingleton('productsfilter/options')->getItems();
				$options = $this->_loadCounters($options);
				$this->_groupOptions = $this->_loadGroupsInfo($options);
			}

			Mage::getSingleton('productsfilter/router')->prepareGenerateUrls();
		}

		return $this->_groupOptions;
	}

	protected function _loadCounters($options)
	{
		$model = Mage::getSingleton('productsfilter/options');
		foreach ($options as $code => $attrItem) {
			foreach ($attrItem['items'] as $option => $ids) {
				$options[$code]['items'][$option]['ids'] = $model->getOptionProductCountForCurrentFilterState($code, $option);
			}
		}
		return $options;
	}

	protected function _loadGroupsInfo($options)
	{	
		$result = array();
		$childCodes = array();
		$relations = Mage::getSingleton('productsfilter/attributes')->getRelations();

		foreach ($relations as $code => $item) {
			if (isset($options[$code])) {
				if ($item['parent']) {
					$parent = $item['parent'];
					$result = $this->_createGroupIfNotExists($result, $parent, $relations[$parent]['label']);

					$result[$parent]['children'][$code] = $options[$code];
				} else {
					$label = $item['label'];
					if (!$label) {
						$label = $options[$code]['label'];
					}
					$result = $this->_createGroupIfNotExists($result, $code, $label);
					$result[$code]['children'][$code] = $options[$code];
				}
			}
			// else: not found items for this attribute
		}

		foreach ($options as $code => $option) {
			// if custom option attr
			if (!isset($relations[$code])) {
				$result = $this->_createGroupIfNotExists($result, $code, $option['label']);
				$result[$code]['children'][$code] = $option;
			}
		}

		return array_values($result);
	}

	private function _createGroupIfNotExists($result, $code, $label)
	{
		if (isset($result[$code])) {
			$result[$code]['multiple'] = true;
		} else {
			$result[$code] = array(
				'label' 	=> $label,
				'children' 	=> array(),
				'multiple' 	=> false,
				'main_code'	=> $code,
			);
		}
		return $result;
	}

	public function getOptionLink($code, $option)
	{
		return Mage::getSingleton('productsfilter/router')->getOptionLink($code, $option);
	}

	public function getCleanUrl()
	{
		return Mage::getSingleton('productsfilter/router')->getOriginalUrl();
	}
}