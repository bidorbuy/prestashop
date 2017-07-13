<?php

/**
 * Copyright (c) 2014, 2015, 2016 Bidorbuy http://www.bidorbuy.co.za
 * This software is the proprietary information of Bidorbuy.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without modification
 * are not permitted without prior written approval by the copyright holder.
 *
 * Vendor: EXTREME IDEA LLC http://www.extreme-idea.com
 */

require_once(dirname(__FILE__) . '/../factory.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

function upgrade_module_2_0_0($object) {
    return
        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallAuditTableQuery())
        && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallTradefeedTableQuery())
        && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallTradefeedDataTableQuery())
        && addAllProductsInTradefeedQueue()
        && $object->registerHook('actionProductUpdate')
        && $object->registerHook('actionProductAdd')
        && $object->registerHook('actionProductDelete')
        && $object->registerHook('actionCategoryUpdate')
        && $object->registerHook('actionObjectCategoryDeleteBefore')
        && $object->registerHook('actionAttributeSave')
        && $object->registerHook('actionAttributeDelete')
        && $object->registerHook('actionAttributeGroupSave')
        && $object->registerHook('actionAttributeGroupDelete');
}

function addAllProductsInTradefeedQueue($update = false) {
    $productsIds = Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'product` WHERE `active` = 1');
    for ($i = count($productsIds) - 1; $i >= 0; $i--) {
        $productsIds[$i] = $productsIds[$i]['id_product'];
    }
    $productStatus = ($update) ? bobsi\Queries::STATUS_UPDATE : bobsi\Queries::STATUS_NEW;
    $productsIds = array_chunk($productsIds, 500);

    foreach ($productsIds as $page) {
        if (!Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($page, $productStatus))) {

            return false;
        }
    }

    return true;
}