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

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

require_once(dirname(__FILE__) . '/vendor/autoload.php');

$dbSettings = array(
    bobsi\Db::SETTING_PREFIX => _DB_PREFIX_,
    bobsi\Db::SETTING_SERVER => _DB_SERVER_,
    bobsi\Db::SETTING_USER => _DB_USER_,
    bobsi\Db::SETTING_PASS => _DB_PASSWD_,
    bobsi\Db::SETTING_DBNAME => _DB_NAME_
);

bobsi\StaticHolder::getBidorbuyStoreIntegrator()->init(
    Configuration::get('PS_SHOP_NAME'),
    Configuration::get('PS_SHOP_EMAIL'),
    'Prestashop ' . _PS_VERSION_,
    Configuration::get(bobsi\Settings::name),
    $dbSettings
);