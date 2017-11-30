<?php
/**
 * Copyright (c) 2014, 2015, 2016 Bidorbuy http://www.bidorbuy.co.za
 * This software is the proprietary information of Bidorbuy.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without
 * modification are not permitted without prior written approval by the copyright
 * holder.
 *
 * Vendor: EXTREME IDEA LLC http://www.extreme-idea.com
 */

require_once(dirname(__FILE__) . '/../factory.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

/**
 * Update version 2.0.5
 *
 * @param object $object module cless
 *
 * @return bool
 */
function upgrade_module_2_0_10($object) {
    $showTableInfoSql = "SHOW TABLE STATUS WHERE name='" . _DB_PREFIX_ . "%s'";
    $alterTableSql = "ALTER TABLE " . _DB_PREFIX_ . "%s CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $tableNames = [
        bobsi\Queries::TABLE_BOBSI_TRADEFEED_AUDIT,
        bobsi\Queries::TABLE_BOBSI_TRADEFEED,
        bobsi\Queries::TABLE_BOBSI_TRADEFEED_TEXT
    ];
    foreach ($tableNames as $tableName) {
        $showTableInfoQuery = sprintf($showTableInfoSql, $tableName);
        $result = Db::getInstance()->executeS($showTableInfoQuery, TRUE, FALSE);
        $result = array_shift($result);
        if ($result['Collation'] !== 'utf8_unicode_ci') {
            $alterTableQuery = sprintf($alterTableSql, $tableName);
            Db::getInstance()->execute($alterTableQuery);
        }
    }
    return TRUE;
}
