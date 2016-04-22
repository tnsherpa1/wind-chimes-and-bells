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

class Plumrocket_Productsfilter_Model_Router extends Mage_Core_Controller_Varien_Router_Standard
{
	protected $_inputFilterString 	= null;
	protected $_originalUrl 		= '';
	protected $_getParamsPart		= '';
	protected $_sourseUrl			= '';
	/*
	$this->_selectedParams = [
		filtered array Plumrocket_Productsfilter_Model_Options::_optionsList
	]
	*/
	protected $_selectedParams 		= null;

	// Generate URLs
	protected $_filterNodes			= array();
	protected $_filterStrings 		= array();
	protected $_filterString 		= '';

	protected $_catalogUrlUseSuffix = false;
	protected $_preparedCleanUrl 	= '';

	const ATTRIBUTE_DELIMITER		= '--';
	const OPTION_DELIMITER			= '-';


	public function match(Zend_Controller_Request_Http $request)
	{
		$path = $request->getRequestUri();
		$adminhtmlFrontName = (string)Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
		if (strpos($path, '/' . $adminhtmlFrontName . '/')) {
			return false;
		}
		// get ? part
		$this->_setGetParamsPart($path);
		$this->_sourseUrl = $path;

		// parse
		if (preg_match('/\/filter\/(.*)(\/|$)/U', $path, $nodes)
			&& $nodes
		) {
			$this->_inputFilterString = $nodes[1];
			$path = str_replace($nodes[0], '/', $path);
			$this->_originalUrl = $path;

			$request->setRequestUri($path);
			$request->setPathInfo()->setDispatched(false);

			$this->_getRequestRewriteController()->rewrite();
		} else {
			$useStoreCode = (bool)Mage::getStoreConfig('web/url/use_store');
			$code = ($useStoreCode)? '/' . Mage::app()->getStore()->getCode() : '';
			$url = $request->getBaseUrl() . $code . $request->getOriginalPathInfo();
			$this->_originalUrl = $url;
			if ((strrpos($path, 'category/view/id/') === false)) {
				$this->_originalUrl = $path;
			}
		}

		if ($this->_originalUrl
			&& $this->_getParamsPart
			&& (strpos($this->_originalUrl, $this->_getParamsPart) !== false)
		) {
			$this->_originalUrl = str_replace($this->_getParamsPart, '', $this->_originalUrl);
		}

		return false;
	}

	protected function _getRequestRewriteController()
    {
        $className = (string)Mage::getConfig()->getNode('global/request_rewrite/model');

        return Mage::getSingleton('core/factory')->getModel($className, array(
            'routers' => $this->getFront()->getRouters(),
        ));
    }

    // -------------- Parse Url ----------------------

	public function getSelectedParams()
	{
		if (is_null($this->_selectedParams)) {
			$this->_selectedParams = array();

			if (Mage::helper('productsfilter')->moduleEnabled()) {
				// /filter/aa-bbb/
				if ($this->_inputFilterString) {
					$this->_parseFilterString( $this->_inputFilterString );
				} else {
					// ?filter=aa-bbb
					$filterString = Mage::app()->getRequest()->getParam('filter');
					if ($filterString) {
						$this->_parseFilterString( $filterString );
					}
				}
			}
		}
		return $this->_selectedParams;
	}

	protected function _parseFilterString($parseString)
	{
		$options = Mage::getSingleton('productsfilter/options')->getItems();

		$byAttrs = explode(self::ATTRIBUTE_DELIMITER, $parseString);
		foreach ($byAttrs as $attrString) {
			$byOptions = explode(self::OPTION_DELIMITER, $attrString);

			$code = array_shift($byOptions);
			if ($code && array_key_exists($code, $options)) {
				
				$this->_selectedParams[ $code ] = array(
					'label' => $options[$code]['label'],
					'items' => array(),
				);

				foreach ($byOptions as $option) {
					if (array_key_exists($option, $options[$code]['items'])) {
						$this->_selectedParams[$code]['items'][ $option ] = $options[$code]['items'][ $option ];
					}
				}
			}
		}
	}

	protected function _setGetParamsPart($url)
	{
		$getPos = strpos($url, '?');
		if ($getPos !== false) {
			$part = substr($url, $getPos);
			preg_match('/(\&|\?)filter\=(.*)(\&|$)/U', $part, $nodes);
			
			if ($nodes && isset($nodes[0])) {
				$part = $nodes[0];

				//$part = preg_replace('/(\&|\?)filter\=(.*)(\&|$)/U', '$1', $part);
				if ($part == '?') {
					$part = '';
				}
				if ($part && ($part[ strlen($part) - 1 ] == '&')) {
					$part = substr($part, 0, -1);
				}
				$this->_getParamsPart = $part;
			}
		}
	}

	public function getOriginalUrl()
	{
		return $this->_originalUrl;
	}

	public function getSourceUrl()
	{
		return $this->_sourseUrl;
	}

	// -------------- Generate Url ------------------ //

	public function prepareGenerateUrls()
	{
		$selectedParams = $this->getSelectedParams();
		
		foreach ($selectedParams as $code => $attrItem) {
			$this->_filterNodes[ $code ] = array_keys($attrItem['items']);
			$this->_filterStrings[ $code ] = $code . self::OPTION_DELIMITER . implode(self::OPTION_DELIMITER, $this->_filterNodes[ $code ]);
		}		
		$this->_filterString = implode(self::ATTRIBUTE_DELIMITER, $this->_filterStrings);

		// check catalog url suffix
		$suffix = Mage::getStoreConfig('catalog/seo/category_url_suffix');
		$this->_catalogUrlUseSuffix = ! empty($suffix);

		$url = $this->getOriginalUrl();
		if ($this->_catalogUrlUseSuffix) {
			$url .= (strpos($url, '?'))? '&' : '?';
			$this->_getParamsPart = '';
		} elseif ($url[ strlen($url) - 1 ] != '/') {
			$url .= '/';
		}
		$this->_preparedCleanUrl = $url;
	}

	public function getOptionLink($code, $option)
	{
		$link = '';
		// al least one option of curr attribute is selected
		if (array_key_exists($code, $this->_filterNodes)) {
				// if this option is not selected.. (but this attibute has at least 1 selected option)
			if (! in_array($option, $this->_filterNodes[$code])
				// .. or curr attribute has more then 1 selected option (not only this option)
				// therefore we should left curr attribute filter string in filter string
				|| (count($this->_filterNodes[$code]) > 1)
			) {
				// save filter string of curr attribute to temp variable
				$oldAttrStr = $this->_filterStrings[ $code ];

				$this->_filterStrings[ $code ] = (! in_array($option, $this->_filterNodes[$code])) 
					// simply append this option to filter string of curr attribute
					? $this->_filterStrings[ $code ] . self::OPTION_DELIMITER . $option
					// simply remove this option from filter string of curr attribute
					: str_replace(self::OPTION_DELIMITER . $option, '', $this->_filterStrings[ $code ]);

				$link = implode(self::ATTRIBUTE_DELIMITER, $this->_filterStrings);
				// restore filter string of this attribute from temp variable
				$this->_filterStrings[ $code ] = $oldAttrStr;
			// curr attribute has only 1, curr, option
			} else {
				// if selected more than 1 attributes
				if (count($this->_filterNodes) > 1) {
					$link = str_replace(self::ATTRIBUTE_DELIMITER . $this->_filterStrings[ $code ], '', $this->_filterString);
					$link = str_replace($this->_filterStrings[ $code ], '', $link);
				}
				// else we left $link empty so delete one selected attribute from filter string
				// no one attribute will be selected
			}
		// curr attribute has not selected options
		} else {
			$link = (($this->_filterString)? $this->_filterString . self::ATTRIBUTE_DELIMITER : '') . $code . self::OPTION_DELIMITER  . $option;
		}
		if ($link) {
			$link = 'filter' . (($this->_catalogUrlUseSuffix)? '=' . $link : '/' . $link . '/');
		}
		return $this->_preparedCleanUrl . $link . $this->_getParamsPart;
	}
}
