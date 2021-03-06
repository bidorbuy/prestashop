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

if (!defined('_PS_VERSION_')) {
    exit;
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

/**
 * Class BidorbuyStoreIntegratorDownloadModuleFrontController.
 */
class BidorbuyStoreIntegratorDownloadModuleFrontController extends ModuleFrontController {

    /**
     * Init Content
     *
     * @return void
     */
    public function initContent() {
        parent::initContent();

        $bidorbuyStoreIntegrator = new BidorbuyStoreIntegrator();
        $bidorbuyStoreIntegrator->download(Tools::getValue(bobsi\Settings::paramToken));
    }
}
