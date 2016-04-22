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

class Plumrocket_Productsfilter_Block_List extends Mage_Catalog_Block_Product_List
{ 
	protected function _beforeToHtml()
	{
		parent::_beforeToHtml();

		if (! $this->getChild('toolbar')->getCollection()->getSize()) {
			$this->setTemplate('productsfilter/no_items.phtml');
		}
	}
}