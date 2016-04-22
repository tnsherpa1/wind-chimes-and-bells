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

class Plumrocket_Productsfilter_Model_Abstract extends Mage_Core_Model_Abstract
{
	protected $_collection = null;
	protected $_attributes = null;

	public function setCollection($collection)
	{
		$this->_collection = $collection;
		return $this;
	}

	public function setAttributes($attributes)
	{
		$this->_attributes = $attributes;
		return $this;
	}

	public function getCollection()
	{
		return $this->_collection;
	}

	public function getAttributes()
	{
		return $this->_attributes;
	}	
}