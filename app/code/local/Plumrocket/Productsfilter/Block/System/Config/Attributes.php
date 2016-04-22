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

class Plumrocket_Productsfilter_Block_System_Config_Attributes extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$element->setHint($element->getScopeLabel());

		//$html = sprintf('<button class="append_item">Append</button><button class="remove_item">Remove</button><ul id="%s" class="field-100"></ul>%s', str_replace('_enable', '_group', $element->getHtmlId()),$element->getAfterElementHtml());
        $html = sprintf('<span class="label">Filter Attributes</span><div><ul id="%s" class="field-100 list_enabled"></ul></div>%s', str_replace('_enable', '_group', $element->getHtmlId()),$element->getAfterElementHtml());

        $element->setScopeLabel($html);
		return parent::render($element);
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //return $this->_getMainElementHtml($element) . $html;
        return $this->_getMainElementHtml($element);
    }

    protected function _getMainElementHtml($element)
    {
    	$html = '<span class="label">All Attributes</span><div class="attributes_enable"><ul id="' . $element->getHtmlId() . '" class="field-100">';
    	$values = $element->getValues();
    	$actives = explode(',', $element->getValue());

    	foreach ($values as $item) {
    		$html .= sprintf('<li class="attr_item%s" data-value="%s">
                <label>%s</label>
                <input type="hidden" name="groups[attributes][fields][group][value][%s]" class="attr_group" value="" />
                <input type="hidden" name="groups[attributes][fields][enable][value][]" class="attr_enable" value="" />
                <ul class="sortable list_enabled"></ul>
            </li>',
                (in_array($item['value'], $actives)? ' active': ''),
    			$item['value'],
    			$item['label'],
                $item['value'],
                $item['value']
    		);
    	}
        $html .= $this->_getJs($actives);
        
    	return $html;
    }

    protected function _getJs($list)
    {
        $result = array();
        foreach ($list as $val) {
            if ($val) {
                $result[] = $val;
            }
        }

        $json = json_encode($result);
        if ($json == '[]') {
            $json = '{}';
        }

        return '</ul></div><script type="text/javascript">
            enabledAttributesCodes = '. $json . ';
        </script>';
    }
}
