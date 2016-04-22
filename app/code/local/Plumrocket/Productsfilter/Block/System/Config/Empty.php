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

@package    Plumrocket_Product_Filter-v1.2.x
@copyright  Copyright (c) 2013 Plumrocket Inc. (http://www.plumrocket.com)
@license    http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 
*/

class Plumrocket_Productsfilter_Block_System_Config_Empty extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
    	$json = json_encode($element->getValue());
    	if ($json == '[]') {
    		$json = '{}';
    	}
        return '<script type="text/javascript">
    		pjQuery_1_9("#productsfilter_attributes_enable").attrItemPlugin('. $json . ');
    	</script>';
    }
}
