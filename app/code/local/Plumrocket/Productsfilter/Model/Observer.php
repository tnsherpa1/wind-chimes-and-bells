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

class Plumrocket_Productsfilter_Model_Observer
{
	protected $_navigationBlocks = array(
		'catalog_category_view'			=> 'catalog.leftnav',
		'catalogsearch_result_index'	=> 'catalogsearch.leftnav'
	);
	protected $_listBlocks = array(
		'catalog_category_view'			=> 'product_list',
		'catalogsearch_result_index'	=> 'search_result_list'
	);
	protected $_viewBlocks = array(
		'catalog_category_view'			=> 'category.products',
		'catalogsearch_result_index'	=> 'search.result',
		//'catalogsearch_advanced_result' => 'catalogsearch_advanced_result'
	);
	protected $_action = '';
	protected $_layout = null;

	protected $_alreadyLoadedCollection = false;


	protected function _isAllow($allowNoAjax = false)
	{
		return Mage::helper('productsfilter')->moduleEnabled()
			&& ($this->getListBlockName() != '')
			&& ($allowNoAjax || Mage::app()->getRequest()->getParam('ajax'));
	}

	public function generateBlocksAfter($observer)
	{
		$this->_action = $observer->getEvent()->getAction()->getFullActionName();
		$this->_layout = $observer->getEvent()->getLayout();

		if ($this->_isAllow(true)) {
			$this->_initFilterBlockInLeftNavigation();
		}

		if ($this->_isAllow()) {
			// Check if block head exists and if true then emulate layout handle actions.
			if ($this->_layout->getBlock('head')) {
				$this->_layout->getBlock('root')
					->setIsHandle(1)
					->setTemplate('productsfilter/response.phtml')
					->unsetChild('left')
					->unsetChild('right')
					->unsetChild('head')
					->setObserver($this);
			}
		}
		return $observer;
	}
	/*
	public function layoutRenderBefore($observer, $isSearchPage = false)
	{
		$action = ($isSearchPage)? 'catalogsearch_result_index': 'catalog_category_view';
		if ($this->_isAllow()) {
			Mage::getSingleton('core/layout')
				->getBlock('root')
				->setTemplate($this->_handles[$action]);
		}
		return $observer;
	}

	public function layoutRenderBeforeSearch($observer)
	{
		$this->layoutRenderBefore($observer, true);
	}*/

	public function initCollection($observer)
	{
		if (Mage::helper('productsfilter')->moduleEnabled()) {
			$collection = $observer->getEvent()->getCollection();
			$this->_initCollection($collection);
		}
		return $observer;
	}

	protected function _initCollection($collection)
	{
		if ($this->_alreadyLoadedCollection) {
			return ;
		}
		$this->_alreadyLoadedCollection = true;
		$attributes = Mage::getSingleton('productsfilter/attributes')->getEnabledItems();

		$options = Mage::getSingleton('productsfilter/options');
		if ($this->_action == 'catalogsearch_result_index') {
			$options->weOnSearchPage();
		}

		$options->setCollection($collection)
			->setAttributes($attributes)
			->filterCollection();
	}

	public function controllerFrontInitBefore($observer)
	{
		$front = $observer->getEvent()->getFront();
		$routerCode = 'productsfilter';

		$router = Mage::getSingleton('productsfilter/router');
		$router->collectRoutes('frontend', $routerCode);
		$front->addRouter($routerCode, $router);
	}

	protected function _initFilterBlockInLeftNavigation()
	{
		if ($this->isDifferentViews()) {
			$list = $this->getListBlock();
			$toolbar = $list->getToolbarBlock();
			$toolbar->enableDifferentViews();

			// called prepare sortable parameters
			$collection = $list->getLoadedProductCollection();

			// use sortable parameters
			if ($orders = $list->getAvailableOrders()) {
				$toolbar->setAvailableOrders($orders);
			}
			if ($sort = $list->getSortBy()) {
				$toolbar->setDefaultOrder($sort);
			}
			if ($dir = $list->getDefaultDirection()) {
				$toolbar->setDefaultDirection($dir);
			}
			if ($modes = $list->getModes()) {
				$toolbar->setModes($modes);
			}

			// set collection to toolbar and apply sort
			$toolbar->setCollection($collection);
			$this->_initCollection($collection);

			$t2 = clone $toolbar;
			$t2->setAsFilterContainer();

			$this->_layout->setBlock($this->getNavigationBlockName(), $t2);
		}
	}

	// service functions

	public function isDifferentViews()
	{
		$placement = Mage::getStoreConfig('productsfilter/attributes/placement');
		return ($placement == Plumrocket_Productsfilter_Model_Placements::SIDEBAR)
			&& $this->getNavigationBlock();
	}

	public function getNavigationBlock()
	{
		return $this->_layout->getBlock( $this->getNavigationBlockName() );
	}

	public function getNavigationBlockName()
	{
		$name = isset($this->_navigationBlocks[ $this->_action ])? $this->_navigationBlocks[ $this->_action ]: '';

		if ($this->_action == 'catalog_category_view') {
			if (!$this->_layout->getBlock($name)) {
				$name = 'enterprisecatalog.leftnav';
			}
		}

		if ($this->_action == 'catalogsearch_result_index') {
			if (!$this->_layout->getBlock($name)) {
				$name = 'enterprisesearch.leftnav';
			}
		}
		return $name;
	}

	public function getListBlock()
	{
		return $this->_layout->getBlock( $this->getListBlockName() );
	}

	public function getListBlockName()
	{
		return isset($this->_listBlocks[ $this->_action ])? $this->_listBlocks[ $this->_action ]: '';
	}

	public function getViewBlock()
	{
		return $this->_layout->getBlock( $this->getViewBlockName() );
	}

	public function getViewBlockName()
	{
		return isset($this->_viewBlocks[ $this->_action ])? $this->_viewBlocks[ $this->_action ]: '';
	}

	public function clearCache($observer)
	{
		Mage::app()->getCacheInstance()->clean(array('productsfilter'));

		return $observer;
	}
}