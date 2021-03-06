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
 * Class BidorbuyStoreIntegratorResetAuditModuleFrontController.
 */
class BidorbuyStoreIntegratorResetAuditModuleFrontController extends ModuleFrontController {

    /**
     * Reset Audit Action
     *
     * @return void
     */
    public function initContent() {
        parent::initContent();

        $bidorbuyStoreIntegrator = new BidorbuyStoreIntegrator();
        $bidorbuyStoreIntegrator->resetAudit(Tools::getValue(bobsi\Settings::paramToken));
    }
}
