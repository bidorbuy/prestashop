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
require_once ('install-2.0.0.php');
if (!defined('_PS_VERSION_')) {
    exit;
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

function upgrade_module_2_0_5($object) {    
    return
       addAllProductsInTradefeedQueue(true)
        && Db::getInstance()->execute("ALTER TABLE "._DB_PREFIX_.bobsi\Queries::TABLE_BOBSI_TRADEFEED." ADD `images` text AFTER `image_url`");
        
}