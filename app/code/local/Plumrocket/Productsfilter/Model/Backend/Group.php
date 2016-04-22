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

class Plumrocket_Productsfilter_Model_Backend_Group extends Mage_Core_Model_Config_Data
{
    protected function _afterLoad()
    {
        $this->parse();   
		parent::_afterLoad();
    }

    public function parse()
    {
        $value = $this->getValue();
        if (!$value) {
            $value = '{}';
        }
        $this->setValue(json_decode($value, true));
        return $this;
    }
 
    protected function _beforeSave()
    {
        $groups = $this->getData('fieldset_data/group');
        $names = $this->getData('fieldset_data/name');

        $result = array();

        if ($groups && $names) {
		    foreach ($groups as $child => $parent) {
		        if (empty($parent)) {
		            continue;
		        }
		        if (!isset($result[$parent])) {
		            $result[$parent] = array(
		                'children' => array(),
		                'label' => '',
		            );
		        }
		        $result[$parent]['children'][] = $child;
		    }

		    foreach ($names as $parent => $label) {
                $label = str_replace(' (Group)', '', $label);
		        if (isset($result[$parent])) {
		            $result[$parent]['label'] = $label;
		        }
		    }
		}

        $this->setValue( json_encode($result) );
        return parent::_beforeSave();
    }

    protected function _afterSave()
    {
        Mage::getSingleton('productsfilter/observer')->clearCache(true);
        return parent::_afterSave();
    }
}