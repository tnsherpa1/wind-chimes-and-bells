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

class Plumrocket_Productsfilter_Model_Modes
{
	const SINGLE = 'one';
    const PACKAGE = 'package';
	
    public function toOptionArray()
    {
        return array(
            array('value' => self::SINGLE, 'label' => Mage::helper('productsfilter')->__('Auto Refresh')),
            array('value' => self::PACKAGE, 'label' => Mage::helper('productsfilter')->__('Manual Refresh')),
        );
    }
}
