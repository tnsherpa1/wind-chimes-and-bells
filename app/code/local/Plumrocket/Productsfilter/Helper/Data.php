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

class Plumrocket_Productsfilter_Helper_Data extends Plumrocket_Productsfilter_Helper_Main
{
	public function moduleEnabled($store = null)
	{
		return (bool)Mage::getStoreConfig('productsfilter/general/enable', $store);
	}

	public function disableExtension()
	{
		$resource = Mage::getSingleton('core/resource');
		$connection = $resource->getConnection('core_write');
		$connection->delete($resource->getTableName('core/config_data'), array($connection->quoteInto('path IN (?)', array('productsfilter/general/enable', 'productsfilter/attributes/enable', 'productsfilter/attributes/enable_custom',))));
		$config = Mage::getConfig();
		$config->reinit();
		Mage::app()->reinitStores();
	}

	public function customOptionsEnables($store = null)
	{
		return (bool)Mage::getStoreConfig('productsfilter/attributes/enable_custom', $store);
	}
}