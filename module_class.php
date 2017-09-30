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

/*
 * It is required to have this class in separate file: Prestasop code analyzer throws "Parse Error" 
 * if main module file contains word "use" like it does the following line.
 */
use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

/**
 * Class BidorbuyStoreIntegrator.
 */
class BidorbuyStoreIntegrator extends Module {
    private $exportUrl;
    private $downloadUrl;
    private $resetAuditUrl;
    private $phpInfo;

    public $bootstrap = TRUE;

    /**
     * BidorbuyStoreIntegrator constructor.
     *
     * @return mixed
     */
    public function __construct() {
        $this->name = bobsi\Version::$id;
        $this->tab = 'export';
        $this->version = bobsi\Version::getVersionFromString('2.0.9');
        $this->author = bobsi\Version::$author;
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6.9');
        $this->displayName = $this->l(bobsi\Version::$name);
        $this->description = $this->l(bobsi\Version::$description);

        $this->bobsi_plugin_check_update();
        parent::__construct();

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $warnings = array_merge(
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getWarnings(),
            bobsi\StaticHolder::getWarnings()->getBusinessWarnings()
        );

        $this->warning = join('. ', $warnings);

        $this->exportUrl = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?fc=module&module='
            . bobsi\Version::$id . '&controller=export&' . bobsi\Settings::paramToken
            . '=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport();

        $this->downloadUrl = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?fc=module&module='
            . bobsi\Version::$id . '&controller=download&' . bobsi\Settings::paramToken
            . '=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload();

        $this->resetAuditUrl = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?fc=module&module='
            . bobsi\Version::$id . '&controller=resetaudit&' . bobsi\Settings::paramToken
            . '=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload();

        $this->phpInfo = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?fc=module&module='
            . bobsi\Version::$id . '&controller=version&' . bobsi\Settings::paramToken
            . '=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload()
            . '&phpinfo=y';

//        TODO: features is not implemented
//        $this->features = Feature::getFeatures($this->getDefaultLanguage());
    }

    /**
     * Plugin Install Action
     *
     * @return bool
     */
    public function install() {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return
            //1.0.0
            parent::install()
            && $this->updateConfigurationSettings()
            && $this->registerHook('displayBackOfficeHeader')
            //2.0.0
            && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getInstallAuditTableQuery())
            && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getInstallTradefeedTableQuery())
            && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getInstallTradefeedDataTableQuery())
            && $this->addAllProductsInTradefeedQueue()
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductDelete')
            && $this->registerHook('actionCategoryUpdate')
            && $this->registerHook('actionObjectCategoryDeleteBefore')
            && $this->registerHook('actionAttributeSave')
            && $this->registerHook('actionAttributeDelete')
            && $this->registerHook('actionAttributeGroupSave')
            && $this->registerHook('actionAttributeGroupDelete');
    }

    /**
     * Uninstall Plugin Action
     *
     * @return bool
     */
    public function uninstall() {
        parent::uninstall();
        return $this->deleteConfigurationSettings()
        && Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getDropTablesQuery())
        && $this->bobsi_delete_update_settings();
    }

    /**
     * Add css & js to admin panel
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader() {
        $this->context->controller->addCSS($this->_path . 'assets/css/admin.css', 'all');
        $this->context->controller->addJquery(); //Do not delete, even if jquery already added (for older versions)
        $this->context->controller->addJS($this->_path . 'assets/js/admin.js');
        $this->context->controller->addJS($this->_path
            . 'vendor/com.extremeidea.bidorbuy/storeintegrator-core/assets/js/admin.js'
        );
    }

    /**
     * Update products
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionProductUpdate($params) {
        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($params['product']->id, bobsi\Queries::STATUS_UPDATE
        ));
    }

    /**
     * Add product
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionProductAdd($params) {
        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($params['product']->id, bobsi\Queries::STATUS_NEW));
    }

    /**
     * Delete product
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionProductDelete($params) {
        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($params['product']->id, bobsi\Queries::STATUS_DELETE));
    }

    /**
     * Category Update
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionCategoryUpdate($params) {
        $this->hookActionCategoryUpdateDelete($params['category']);
    }

    /**
     * Category Delete
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionObjectCategoryDeleteBefore($params) {
        $this->hookActionCategoryUpdateDelete($params['object']);
    }

    /**
     * Category Update Delete
     *
     * @param object $object params
     *
     * @return void
     */
    private function hookActionCategoryUpdateDelete($object) {
        if (is_a($object, 'Category')) {

            //If the category has been disabled - add it to ExcludedCategories
            if (FALSE == $object->active) {
                $currentSettings = unserialize(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                    ->getSettings()->serialize());
                $currentSettings[bobsi\Settings::nameExcludeCategories][] = $object->id_category;
                $this->updateConfigurationSettings($currentSettings);
            }

            $productsIds = $object->getProductsWs();
            $productStatus = (isset($_POST['deleteMode']) && $_POST['deleteMode'] == 'delete') ?
                bobsi\Queries::STATUS_DELETE : bobsi\Queries::STATUS_UPDATE;

            foreach ($productsIds as $p) {
                if (isset($p['id'])) {
                    Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                        ->getQueries()->getAddJobQueries((int)$p['id'], $productStatus));
                }
            }
        }
    }

    /**
     * Attribute Save
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionAttributeSave($params) {
        $this->actionAttribute($params);
    }

    /**
     * Attribute Delete
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionAttributeDelete($params) {
        $this->actionAttribute($params);
    }

    /**
     * Attribute Group Save
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionAttributeGroupSave($params) {
        $this->actionAttributeGroup($params);
    }

    /**
     * Attribute Group Delete
     *
     * @param array $params params
     *
     * @return void
     */
    public function hookActionAttributeGroupDelete($params) {
        $this->actionAttributeGroup($params);
    }

    /**
     * Attribute Action
     *
     * @param array $params params
     *
     * @return void
     */
    private function actionAttribute($params) {
        $productsIds = $this->getProductsByAttributes($params['id_attribute']);

        foreach ($productsIds as $productsId) {
            Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getAddJobQueries($productsId['id'], bobsi\Queries::STATUS_UPDATE));
        }
    }

    /**
     * Attribute Group Action
     *
     * @param array $params params
     *
     * @return void
     */
    private function actionAttributeGroup($params) {
        $ag = new AttributeGroup($params['id_attribute_group']);
        $attrs = $ag->getWsProductOptionValues();
        $attrsIds = array();
        foreach ($attrs as $a) {
            $attrsIds[] = $a['id'];
        }

        $productsIds = $this->getProductsByAttributes($attrsIds);

        foreach ($productsIds as $productsId) {
            Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getAddJobQueries($productsId['id'], bobsi\Queries::STATUS_UPDATE));
        }
    }

    /**
     * Display Form
     *
     * @return string
     */
    public function displayForm() {
        // Get default Language
        $default_lang = $this->getDefaultLanguage();

        $compress_libs_options = array();
        foreach (bobsi\Settings::getCompressLibraryOptions() as $level => $opts) {
            $compress_libs_options[] = array(
                'id_option' => $level,
                'name' => $level
            );
        }

        $logging_level_options = array();
        foreach (bobsi\Settings::getLoggingLevelOptions() as $level) {
            $logging_level_options[] = array(
                'id_option' => $level,
                'name' => ucfirst($level)
            );
        }

        $wordings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultWordings();

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l(
                    $wordings[bobsi\Settings::nameExportConfiguration][bobsi\Settings::nameWordingsTitle]
                ),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l($wordings[bobsi\Settings::nameFilename][bobsi\Settings::nameWordingsTitle]),
                    'name' => bobsi\Settings::nameFilename,
                    'size' => 40,
                    'class' => 'fixed-width-xxl',
                    'required' => TRUE,
                    'desc' => $wordings[bobsi\Settings::nameFilename][bobsi\Settings::nameWordingsDescription]
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameCompressLibrary][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameCompressLibrary,
                    'class' => 'fixed-width-xxl',
                    'required' => TRUE,
                    'desc' => $this->l(
                        $wordings[bobsi\Settings::nameCompressLibrary][bobsi\Settings::nameWordingsDescription]
                    ),
                    'options' => array(
                        'query' => $compress_libs_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameDefaultStockQuantity][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameDefaultStockQuantity,
                    'size' => 40,
                    'class' => 'fixed-width-xxl',
                    'required' => TRUE,
                    'desc' => $this->l(
                        $wordings[bobsi\Settings::nameDefaultStockQuantity][bobsi\Settings::nameWordingsDescription]
                    ),
                )
            )
        );

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l($wordings[bobsi\Settings::nameExportCriteria][bobsi\Settings::nameWordingsTitle]),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameExportQuantityMoreThan][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameExportQuantityMoreThan,
                    'size' => 40,
                    'class' => 'fixed-width-xxl',
                    'required' => TRUE,
                    'hint' => $this->l(
                        $wordings[bobsi\Settings::nameExportQuantityMoreThan][bobsi\Settings::nameWordingsDescription]
                    )
                ),
                /*
                 * Feature 3750
                 */
                array(
                    'type' => 'radio',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameExportProductSummary][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameExportProductSummary,
                    'hint' => $this->l(
                        $wordings[bobsi\Settings::nameExportProductSummary][bobsi\Settings::nameWordingsDescription]
                    ),
                    'is_bool' => TRUE,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => TRUE,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => FALSE,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameExportProductDescription][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameExportProductDescription,
                    'hint' => $this->l(
                        $wordings[bobsi\Settings::nameExportProductDescription][bobsi\Settings::nameWordingsDescription]
                    ),
                    'is_bool' => TRUE,
                    'class' => 't',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => TRUE,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => FALSE,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                /*
                 * End of Feature
                 */
                array(
                    'type' => 'categories_select',
                    'name' => bobsi\Settings::nameExcludeCategories,
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameExcludeCategories][bobsi\Settings::nameWordingsTitle]
                    ),
                    'category_tree' => $this->initCategoriesAssociation(
                        $this->getExportCategoriesIds(
                            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExcludeCategories()
                        )
                    )
                ),
            )
        );

        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l($wordings[bobsi\Settings::nameExportLinks][bobsi\Settings::nameWordingsTitle]),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l($wordings[bobsi\Settings::nameExportUrl][bobsi\Settings::nameWordingsTitle]),
                    'name' => bobsi\Settings::nameTokenExport,
                    'size' => 120,
                    'required' => FALSE,
                    'readonly' => TRUE
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameDownloadUrl][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameTokenDownload,
                    'size' => 120,
                    'required' => FALSE,
                    'readonly' => TRUE
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameActionResetExportTables][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameActionResetExportTables,
                    'size' => 120,
                    'required' => FALSE,
                    'readonly' => TRUE,
                    'desc' => $this->l(
                        $wordings[bobsi\Settings::nameActionResetExportTables][bobsi\Settings::nameWordingsDescription]
                    )
                )

            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                'export-products' => array(
                    'href' => $this->exportUrl,
                    'title' => $this->l(
                        $wordings[bobsi\Settings::nameButtonExport][bobsi\Settings::nameWordingsTitle]
                    ),
                    'target' => TRUE,
                    'icon' => 'process-icon-export'
                ),
                'download' => array(
                    'href' => $this->downloadUrl,
                    'title' => $this->l(
                        $wordings[bobsi\Settings::nameButtonDownload][bobsi\Settings::nameWordingsTitle]
                    ),
                    'target' => TRUE,
                    'icon' => 'process-icon-download'
                ),
                'launch-reset-export-tables' => array(
                    'href' => $this->resetAuditUrl,
                    'title' => $this->l(
                        $wordings[bobsi\Settings::nameActionResetExportTables][bobsi\Settings::nameWordingsTitle]
                    ),
                    'target' => TRUE,
                    'icon' => 'process-icon-eraser',
                    'id' => 'launch-reset-audit-button'
                ),
                'reset-tokens' => array(
                    'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules') .
                        '&configure=' . bobsi\Version::$id . '&do=' . bobsi\Settings::nameActionReset,
                    'title' => $this->l($wordings[bobsi\Settings::nameButtonReset][bobsi\Settings::nameWordingsTitle]),
                    'icon' => 'process-icon-reset'
                )
            )
        );

        $fields_form[3]['form'] = array(
            'legend' => array(
                'title' => 'Debug'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l($wordings[bobsi\Settings::nameUsername][bobsi\Settings::nameWordingsTitle]),
                    'name' => bobsi\Settings::nameUsername,
                    'class' => 'fixed-width-xxl',
                    'size' => 40,
                    'required' => FALSE,
                    'desc' => $wordings[bobsi\Settings::nameUsername][bobsi\Settings::nameWordingsDescription]
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l($wordings[bobsi\Settings::namePassword][bobsi\Settings::nameWordingsTitle]),
                    'name' => bobsi\Settings::namePassword,
                    'size' => 40,
                    'class' => 'fixed-width-xxl',
                    'required' => FALSE,
                    'desc' => $wordings[bobsi\Settings::namePassword][bobsi\Settings::nameWordingsDescription]
                )
            )
        );


        $fields_form[4]['form'] = array(
            'legend' => array(
                'title' => 'Debug'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameEmailNotificationAddresses][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameEmailNotificationAddresses,
                    'size' => 40,
                    'class' => 'fixed-width-xxl',
                    'desc' => $this->l(
                    $wordings[bobsi\Settings::nameEmailNotificationAddresses][bobsi\Settings::nameWordingsDescription]
                    )
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameEnableEmailNotifications][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameEnableEmailNotifications,
                    'required' => TRUE,
                    'class' => 't',
                    'is_bool' => TRUE,
                    'values' => array(
                        array(
                            'id' => 1,
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 0,
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l(
                        $wordings[bobsi\Settings::nameLoggingLevel][bobsi\Settings::nameWordingsTitle]
                    ),
                    'name' => bobsi\Settings::nameLoggingLevel,
                    'class' => 'fixed-width-xxl',
                    'required' => TRUE,
                    'desc' => $this->l(
                        $wordings[bobsi\Settings::nameLoggingLevel][bobsi\Settings::nameWordingsDescription]
                    ),
                    'options' => array(
                        'query' => $logging_level_options,
                        'id' => 'id_option',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'hiddenPass'
                )

            )
        );

        $hidden_fields[0]['form'] = array(
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => bobsi\Settings::nameUsername
                ),
                array(
                    'type' => 'hidden',
                    'name' => bobsi\Settings::namePassword,
                )
            )
        );

        /* @var $helper HelperFormCore */
        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = TRUE;
        $helper->toolbar_scroll = TRUE;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'export-products' => array(
                'href' => $this->exportUrl,
                'desc' => $this->l($wordings[bobsi\Settings::nameButtonExport][bobsi\Settings::nameWordingsTitle]),
                'target' => '_blank'
            ),
            'download' => array(
                'href' => $this->downloadUrl,
                'desc' => $this->l($wordings[bobsi\Settings::nameButtonDownload][bobsi\Settings::nameWordingsTitle]),
                'target' => '_blank'
            ),
            'reset-tokens' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules') .
                    '&configure=' . bobsi\Version::$id . '&do=' . bobsi\Settings::nameActionReset,
                'desc' => $this->l($wordings[bobsi\Settings::nameButtonReset][bobsi\Settings::nameWordingsTitle])
            ),
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value[bobsi\Settings::nameUsername] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getUsername();
        $helper->fields_value[bobsi\Settings::namePassword] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getPassword();
        $helper->fields_value['hiddenPass'] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
            ->getPassword();
        $helper->fields_value[bobsi\Settings::nameFilename] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getFilename();
        $helper->fields_value[bobsi\Settings::nameCompressLibrary] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getCompressLibrary();

        $helper->fields_value[bobsi\Settings::nameDefaultStockQuantity] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultStockQuantity();
        $helper->fields_value[bobsi\Settings::nameEmailNotificationAddresses] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getEmailNotificationAddresses();
        $helper->fields_value[bobsi\Settings::nameEnableEmailNotifications] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getEnableEmailNotifications();
        $helper->fields_value[bobsi\Settings::nameLoggingLevel] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getLoggingLevel();
        $helper->fields_value[bobsi\Settings::nameExportQuantityMoreThan] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportQuantityMoreThan();
        /*
        * Feature #3750     
        */
        $helper->fields_value[bobsi\Settings::nameExportProductSummary] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportProductSummary();
        $helper->fields_value[bobsi\Settings::nameExportProductDescription] =
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportProductDescription();

        /*
         * End Feature Block
         */
        $helper->fields_value[bobsi\Settings::nameTokenExport] = $this->exportUrl;
        $helper->fields_value[bobsi\Settings::nameTokenDownload] = $this->downloadUrl;
        $helper->fields_value[bobsi\Settings::nameActionResetExportTables] = $this->resetAuditUrl;

        $html = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getLogsHtml();
        $versionHtml = "<div class=\"bob-version\">
            <a href=\"$this->phpInfo\" target='_blank'>@See PHP information</a><br>"
            . bobsi\Version::getLivePluginVersion() . "</div>";

        /* Feature 3910 */
        $baa = Tools::getValue('baa');
        if ($baa != 1) {
            $fields_form[3]['form'] = $hidden_fields[0]['form'];
        }
        /* End feature*/
        return $this->displayError($this->warning) . $helper->generateForm($fields_form) . $html . $versionHtml;
    }

    /**
     * Get Content
     *
     * @return string
     */
    public function getContent() {
        $output = $this->getLogoHtml();
        $wordings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultWordings();

        $messages = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->processAction(Tools::getValue(bobsi\Settings::nameLoggingFormAction),
            array(bobsi\Settings::nameLoggingFormFilename => Tools::getValue(
                bobsi\Settings::nameLoggingFormFilename)
            ));
        if (count($messages) > 0) {
            $output .= $this->displayConfirmation(implode(' ', $messages));
        }

        if (Tools::getValue('do') == bobsi\Settings::nameActionReset) {
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->processAction(bobsi\Settings::nameActionReset);
            $this->updateConfigurationSettings();

            Tools::redirectAdmin(AdminController::$currentIndex . '&token='
                . Tools::getAdminTokenLite('AdminModules') . '&configure=' . bobsi\Version::$id);
        }

        if (Tools::isSubmit('submit' . $this->name) and Tools::getValue(bobsi\Settings::nameTokenExport)) {
            $presaved_settings = array();
            $prevent_saving = FALSE;

            $settings_checklist = array(
                bobsi\Settings::nameUsername => 'strval',
                bobsi\Settings::namePassword => 'strval',
                bobsi\Settings::nameFilename => 'strval',
                bobsi\Settings::nameCompressLibrary => 'strval',
                bobsi\Settings::nameDefaultStockQuantity => 'intval',
                bobsi\Settings::nameEmailNotificationAddresses => 'strval',
                bobsi\Settings::nameEnableEmailNotifications => 'bool',
                bobsi\Settings::nameLoggingLevel => 'strval',
                bobsi\Settings::nameExportQuantityMoreThan => 'intval',
                bobsi\Settings::nameExcludeCategories => 'categories',
                /*
                 *  Feature #3750
                 */
                bobsi\Settings::nameExportProductSummary => 'bool',
                bobsi\Settings::nameExportProductDescription => 'bool',

                /*
                 * End Feature Block
                 */
//                bobsi\Settings::nameExportActiveProducts => 'bool'
            );

            foreach ($settings_checklist as $setting => $prevalidation) {
                switch ($prevalidation) {
                    case ('strval'):
                        $presaved_settings[$setting] = strval(Tools::getValue($setting));
                        break;
                    case ('intval'):
                        $presaved_settings[$setting] = Tools::getValue($setting);
                        break;
                    case ('bool'):
                        $presaved_settings[$setting] = (bool)(Tools::getValue($setting));
                        break;
                    case ('categories'):
                        $presaved_settings[$setting] = $this->getExportCategoriesIds(Tools::getValue('categoryBox'));
                }

                if (!call_user_func(
                    $wordings[$setting][bobsi\Settings::nameWordingsValidator], $presaved_settings[$setting])
                ) {
                    $output .= $this->displayError($this->l('Invalid value: '
                        . $wordings[$setting][bobsi\Settings::nameWordingsTitle]));
                    $prevent_saving = TRUE;
                }
            }

            $presaved_settings[bobsi\Settings::nameTokenDownload] = strval(
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload()
            );
            $presaved_settings[bobsi\Settings::nameTokenExport] = strval(
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport()
            );

            if (!$prevent_saving) {
                $this->updateConfigurationSettings($presaved_settings);

                $previousSettings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE);
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->unserialize(
                    Configuration::get(bobsi\Settings::name), TRUE
                );

                $newSettings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE);

                if (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->checkIfExportCriteriaSettingsChanged(
                    $previousSettings, $newSettings, TRUE)
                ) {
                    Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                        ->getQueries()->getTruncateJobsQuery());
                    Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                        ->getQueries()->getTruncateProductQuery());
                    $this->addAllProductsInTradefeedQueue(TRUE);
                }

                $output .= $this->displayConfirmation($this->l('Settings updated successfully'));
            }
        }

        return $output . $this->displayForm();
    }

    /**
     * Logo HTML
     *
     * @return string
     */
    public function getLogoHtml() {
        return '<div class="bob-header" style="background: #ffffff url('
        . $this->_path
        . '/assets/images/bidorbuy.png) no-repeat;">
            <div class="bob-ad">
                <!-- BEGIN ADVERTPRO CODE BLOCK -->
                <script type="text/javascript">
                    document.write(\'<scr\' + \'ipt 
                    src="http://nope.bidorbuy.co.za/servlet/view/banner/javascript/zone?zid=153&pid=0
                    &random=\' + Math.floor(89999999 * Math.random() + 10000000) + \'&millis=\' + new Date().getTime() 
                    + \'&referrer=\' + encodeURIComponent(document.location) 
                    + \'" type="text/javascript"></scr\' + \'ipt>\');
                </script>
                <!-- END ADVERTPRO CODE BLOCK -->
            </div>
        </div>';
    }


    /**
     * Categories assoc
     *
     * @param mixed $selected_cat selected categories
     * @param string $input_name name
     *
     * @return string
     */
    private function initCategoriesAssociation($selected_cat = NULL, $input_name = 'categoryBox') {
        $selected_cat = empty($selected_cat) ? array(0) : $selected_cat;
        $disabled_cats = $this->getDisabledCategories();

        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === TRUE) {
            $tree_categories_helper = new HelperTreeCategories('categories-treeview');
            $tree_categories_helper->setRootCategory((Shop::getContext() == Shop::CONTEXT_SHOP ?
                Category::getRootCategory()->id_category : 0))->setUseCheckBox(TRUE);
            $tree_categories_helper->setSelectedCategories($selected_cat);
            $tree_categories_helper->setDisabledCategories($disabled_cats);
            return $tree_categories_helper->render();
        } else {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $root_category = Category::getRootCategory();
                $root_category = array('id_category' => $root_category->id_category, 'name' => $root_category->name);
            } else {
                $root_category = array('id_category' => '0', 'name' => $this->l('Root'));
            }
            $tree_categories_helper = new Helper();
            //Passing disabled_categories to this function has no effect. So the crutch for PS 1.5 was added:
            return $tree_categories_helper->renderCategoryTree($root_category, $selected_cat, $input_name)
            . '<script> var disabledCats = "' . implode(',', $disabled_cats) . '" </script>';
        }
    }

    /**
     * Get Breadcrumb
     *
     * @param integer $categoryId id
     *
     * @return string
     */
    public function getBreadcrumb($categoryId) {
        $category = new Category($categoryId);
        $parents = $category->getParentsCategories();

        $names = array();
        foreach ($parents as $c) {
            array_unshift($names, $c['name']);
        }

        return implode(' > ', $names);
    }

    /**
     * Export Products
     *
     * @param int $product product
     *
     * @return array
     */
    public function exportProducts($product) {
        $languageId = $this->getDefaultLanguage();

        $exportQuantityMoreThan = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getExportQuantityMoreThan();
        $defaultStockQuantity = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getDefaultStockQuantity();
        $allowedCategories = $this->getExportCategoriesIds(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getExcludeCategories());
        $exportProducts = array();

        $product = new Product($product, TRUE, $languageId);

        if (!$product->id) {
            return $exportProducts;
        }

        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logInfo('Processing product id: ' . $product->id);
        $productCategories = $product->getCategories();
        $categoriesMatching = array_intersect($allowedCategories, $productCategories);
        if (!$product->active or empty($categoriesMatching)) {
            return $exportProducts;
        }

        $variations = array();

        if ($product->hasAttributes()) {

            $combinations = $product->getAttributeCombinations($languageId);

            $ids = array();
            foreach ($combinations as $combination) {
                $ids[$combination['id_product_attribute']][] = $combination;
            }

            foreach ($ids as $vid => $combinations) {
                $variations[$vid]['vid'] = $vid;
                foreach ($combinations as $combination) {
                    $variations[$vid]['attributes'][$combination['group_name']] = $combination['attribute_name'];
                    $variations[$vid]['reference'] = $combination['reference'];
                    $variations[$vid]['quantity'] = $combination['quantity'];
                    $variations[$vid]['weight'] = $combination['weight'];
                }
            }
        } else {
            $variations[] = array();
        }

        foreach ($variations as $variation) {
            if (!empty($variation)) {
                $product->quantity = $variation['quantity'];
            }

            if ($this->calcProductQuantity($product, $defaultStockQuantity) > $exportQuantityMoreThan) {
                $tempProduct = $this->buildExportProduct($product, $variation);

                if (intval($tempProduct[bobsi\Tradefeed::nameProductPrice]) <= 0) {
                    bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                        ->logInfo('Product price <= 0, skipping, product id: ' . $product->id);
                    continue;
                }

                $exportProducts[bobsi\Tradefeed::nameProductSummary] = $product->description_short;
                $exportProducts[bobsi\Tradefeed::nameProductDescription] = $product->description;
                $categories = array();
                $categoriesIds = array();

                foreach ($categoriesMatching as $categoryId) {
                    $categories[] = $this->getBreadcrumb($categoryId);
                    $categoriesIds[] = $categoryId;
                }
                $tempProduct[bobsi\Settings::paramCategoryId] = bobsi\Tradefeed::categoryIdDelimiter
                    . join(bobsi\Tradefeed::categoryIdDelimiter, $categoriesIds)
                    . bobsi\Tradefeed::categoryIdDelimiter;

                $tempProduct[bobsi\Tradefeed::nameProductCategory] = join(
                    bobsi\Tradefeed::categoryNameDelimiter, $categories
                );

                $exportProducts[] = $tempProduct;
            } else {
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                    ->logInfo('QTY is not enough to export product id: ' . $product->id);
            }
        }

        return $exportProducts;
    }

    /**
     * Exported Categories
     *
     * @param array $ids ids
     *
     * @return array
     */
    private function getExportCategoriesIds($ids = array()) {
        $sql = '';

        if (!empty($ids)) {
            $sql = ' and c.id_category not in (' . implode(',', $ids) . ') ';
        }

        return array_map(array($this, 'mapCategories2Ids'), Category::getCategories(FALSE, TRUE, FALSE, $sql));
    }

    /**
     * Map Categories to Ids
     *
     * @param array $data data
     *
     * @return int
     */
    private function mapCategories2Ids($data) {
        return intval($data['id_category']);
    }

    /**
     * Get Disabled Categories
     *
     * @return array
     */
    private function getDisabledCategories() {
        $allCats = Category::getCategories(FALSE, FALSE, FALSE);

        $disabledCats = array();
        foreach ($allCats as $cat) {
            if ($cat['active'] == 0) {
                $disabledCats[] = intval($cat['id_category']);
            }
        }

        return $disabledCats;
    }

    /**
     * Build Product
     *
     * @param Product $product product
     * @param array $variations variations
     *
     * @return array
     */
    private function &buildExportProduct(Product &$product, &$variations = array()) {
        $exportedProduct = array();

        $exportedProduct[bobsi\Tradefeed::nameProductId] = $product->id;
        $exportedProduct[bobsi\Tradefeed::nameProductName] = $product->name;
        $exportedProduct[bobsi\Tradefeed::nameProductCode] = $product->id;
        if (!empty($variations)) {
            $exportedProduct[bobsi\Settings::paramVariationId] = $variations['vid'];
            $exportedProduct[bobsi\Tradefeed::nameProductCode] .= '-' . $variations['vid'];
        }
        if (!empty($variations['reference'])) {
            $exportedProduct[bobsi\Tradefeed::nameProductCode] .= '-' . $variations['reference'];
        } else {
            if (!empty($product->reference)) {
                $exportedProduct[bobsi\Tradefeed::nameProductCode] .= '-' . $product->reference;
            }
        }

        $priceWithoutReduct = $product->getPriceWithoutReduct(
            FALSE, isset($variations['vid']) ? $variations['vid'] : FALSE
        );

        $priceFinal = $product->getPrice(TRUE, isset($variations['vid']) ? $variations['vid'] : NULL);

        if ($priceFinal < $priceWithoutReduct) {
            $exportedProduct[bobsi\Tradefeed::nameProductPrice] = $priceFinal;
            $exportedProduct[bobsi\Tradefeed::nameProductMarketPrice] = $priceWithoutReduct;
        } else {
            $exportedProduct[bobsi\Tradefeed::nameProductPrice] = $priceFinal;
            $exportedProduct[bobsi\Tradefeed::nameProductMarketPrice] = '';
        }
        
        $exportedProduct[bobsi\Tradefeed::nameProductCondition] = $this->setProductCondition($product);

        $carriers = $product->getCarriers();
        if (empty($carriers)) {
            $carriers = Carrier::getCarriers($this->getDefaultLanguage());
        }
        $names = array();
        foreach ($carriers as $carrier) {
            $carrier = new Carrier((int)($carrier["id_carrier"]), $this->getDefaultLanguage());
            if ($carrier->active) $names[] = $carrier->name;
        }
        $exportedProduct[bobsi\Tradefeed::nameProductShippingClass] = implode(', ', $names);

        $exportedProduct[bobsi\Tradefeed::nameProductAttributes] = array(
            'Brand' => $product->manufacturer_name
        );

        if ($product->height > 0) {
            $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrHeight] =
                $product->height;
        }

        if ($product->width > 0) {
            $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrWidth] =
                $product->width;
        }

        if ($product->depth > 0) {
            $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrLength] =
                $product->depth;
        }

        $weight = (float)($product->weight + (isset($variations['weight']) ? $variations['weight'] : 0));
        if ($weight > 0) {
            $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrShippingWeight] =
                $weight . Configuration::get('PS_WEIGHT_UNIT');
        }

        if (isset($variations['attributes'])) {
            foreach ($variations['attributes'] as $key => $value) {
                $exportedProduct[bobsi\Tradefeed::nameProductAttributes][$key] = $value;
            }
        }

        $product->quantity = (isset($variations['quantity'])) ? (int)$variations['quantity'] : $product->quantity;
        $exportedProduct[bobsi\Tradefeed::nameProductAvailableQty] = $this->calcProductQuantity(
            $product, bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultStockQuantity()
        );


        /**
         * Get title image. Tag <imageURL> ... </imageURL>
         */
        $imageURL = Product::getCover($product->id);
        if (isset($imageURL) && $imageURL) {
            $exportedProduct[bobsi\Tradefeed::nameProductImageURL] = $this->context->link->getImageLink(
                $product->link_rewrite, strval($product->id . '-' . $imageURL['id_image']));
        }

        $images = $this->getProductImages($product, $variations);
        
        if (!empty($images) && isset($images[0])) {
            //At this stage we have array with images ids. Let's fetch URLs!
            foreach ($images as $img_id) {
                $exportedProduct[bobsi\Tradefeed::nameProductImages][] = $this->context->link->getImageLink(
                    $product->link_rewrite, strval($product->id . '-' . $img_id)
                );
            }
            $exportedProduct[bobsi\Tradefeed::nameProductImageURL] =
                $exportedProduct[bobsi\Tradefeed::nameProductImages][0];
        }


        return $exportedProduct;
    }

    /**
     * Set Product Condition 
     * 
     * @param Product $product product 
     *
     * @return int
     */
    private function setProductCondition($product) {
        $productCondition = bobsi\Tradefeed::conditionSecondhand;
        
        if ($product->condition == 'new') {
            $productCondition = bobsi\Tradefeed::conditionNew;
        } else if ($product->condition == 'refurbished') {
            $productCondition = bobsi\Tradefeed::conditionRefurbished;
        } else if ($product->condition == 'used') {
            $productCondition = bobsi\Tradefeed::conditionSecondhand;
        }
        
        return $productCondition;
    }

    /**
     * Get All Product Images
     * 
     * @param Product $product product
     * @param array $variations variations
     *
     * @return array
     */
    private function getProductImages($product, $variations) {
        $images = array();
        if (isset($variations['vid'])) {
            $combination_images = $product->getCombinationImages($this->getDefaultLanguage());
            if ($combination_images
                && is_array($combination_images)
                && isset($combination_images[$variations['vid']])
            ) {
                foreach ($combination_images[$variations['vid']] as $value) {
                    $images[] = $value['id_image'];
                }
            }
        }

        if (empty($images)) {
            $cover = Product::getCover($product->id);
            $images[] = $cover['id_image'];
        }

        //Add all images except already added ones
        $all_product_images = $product->getWsImages();
        foreach ($all_product_images as $img) {
            if (!empty($images) && in_array($img['id'], $images)) {
                continue;
            }
            $images[] = $img['id'];
            unset($img);
        }
        
        return $images;
    }

    /**
     * Calc Product Qty
     *
     * @param Product $product product
     * @param int $default default
     *
     * @return int
     */
    private function calcProductQuantity($product, $default = 0) {
//        return Product::isAvailableWhenOutOfStock($product->out_of_stock) ? $default : $product->quantity;
        return $product->quantity > 0 ? $product->quantity :
            (Product::isAvailableWhenOutOfStock($product->out_of_stock) ? $default : 0);
//        Product::isAvailableWhenOutOfStock($product->out_of_stock) // <--- if always available, return true
    }

    /**
     * Export products
     *
     * @param string $token token
     * @param bool $productsIds products ids
     * @param string $productStatus status
     *
     * @return void
     */
    public function export($token, $productsIds = FALSE, $productStatus = bobsi\Queries::STATUS_UPDATE) {
        $exportConfiguration = array(
            bobsi\Settings::paramIds => $productsIds,
            bobsi\Settings::paramProductStatus => $productStatus,
            bobsi\Tradefeed::settingsNameExcludedAttributes => array('Width', 'Height', 'Length'),

            bobsi\Settings::paramCallbackGetProducts => array($this, 'getProducts'),
            bobsi\Settings::paramCallbackGetBreadcrumb => array($this, 'getBreadcrumb'),
            bobsi\Settings::paramCallbackExportProducts => array($this, 'exportProducts'),
            bobsi\Settings::paramCategories => $this->getExportCategoriesIds(
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExcludeCategories()
            ),

            bobsi\Settings::paramExtensions => array()
        );

        if (Tools::getValue(bobsi\Settings::paramTimeStart)) {
            $exportConfiguration[bobsi\Settings::paramTimeStart] = Tools::getValue(bobsi\Settings::paramTimeStart);
        }

        $modules = self::getModulesInstalled();
        $extensions = &$exportConfiguration[bobsi\Settings::paramExtensions];
        foreach ($modules as $module) {
            $extensions[$module['name']] = $module['name'] . '/' . $module['active'] . ' ' . $module['version'];
        }

        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->export($token, $exportConfiguration);
    }

    /**
     * Download Action
     *
     * @param string $token token
     *
     * @return void
     */
    public function download($token) {
        $exportConfiguration = array(
            bobsi\Settings::paramCategories => $this->getExportCategoriesIds(
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExcludeCategories()
            ),
        );
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->download($token, $exportConfiguration);
    }

    /**
     * Download logs
     *
     * @param string $token token
     *
     * @return void
     */
    public function downloadl($token) {
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->downloadl($token);
    }

    /**
     * Show php info
     *
     * @param string $token token
     * @param bool $phpinfo flag
     *
     * @return void
     */
    public function showVersion($token, $phpinfo = FALSE) {
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->showVersion($token, $phpinfo);
    }

    /**
     * Reset Audit Action
     *
     * @param string $token token
     *
     * @return void
     */
    public function resetAudit($token) {
        if (!bobsi\StaticHolder::getBidorbuyStoreIntegrator()->canTokenDownload($token)) {
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->show403Token($token);
        }

        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getTruncateJobsQuery());
        Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getTruncateProductQuery());
        $this->addAllProductsInTradefeedQueue(TRUE);
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->resetaudit();
    }

    /**
     * Get Default Language
     *
     * @return int
     */
    private function getDefaultLanguage() {
        return (int)$this->context->language->id;
    }

    /**
     * Update Configuration Settings
     *
     * @param array $settings settings
     *
     * @return mixed
     */
    private function updateConfigurationSettings($settings = array()) {
        if ($settings != NULL && is_array($settings)) {
            return Configuration::updateValue(bobsi\Settings::name, bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getSettings()->serialize2($settings, TRUE));
        } else {
            return Configuration::updateValue(bobsi\Settings::name, bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getSettings()->serialize(TRUE));
        }
    }

    /**
     * Delete config setting
     * 
     * @return mixed
     */
    private function deleteConfigurationSettings() {
        return Configuration::deleteByName(bobsi\Settings::name);
    }

    /**
     * Products By Attribute
     * 
     * @param array $attrIds ids 
     *
     * @return array
     */
    private function getProductsByAttributes($attrIds) {
        if (!is_array($attrIds)) {
            $attrIds = array($attrIds);
        }

        if (empty($attrIds)) {
            return array();
        }

        return Db::getInstance()->executeS(
            'SELECT DISTINCT pa.`id_product` as id FROM `'
            . _DB_PREFIX_
            . 'product_attribute` pa
            LEFT JOIN `'
            . _DB_PREFIX_
            . 'product_attribute_combination` pac ON pa.`id_product_attribute` = pac.`id_product_attribute`
            WHERE pac.`id_attribute` IN (' . implode(',', $attrIds) . ')');
    }

    /**
     * Add All Products In Tradefeed Queue
     *
     * @param bool $update update product flag
     *
     * @return bool
     */
    private function addAllProductsInTradefeedQueue($update = FALSE) {
        $productsIds = $this->getAllProductsIds();
        for ($i = count($productsIds) - 1; $i >= 0; $i--) {
            $productsIds[$i] = $productsIds[$i]['id_product'];
        }

        $productsIds = array_chunk($productsIds, 500);
        $productStatus = ($update) ? bobsi\Queries::STATUS_UPDATE : bobsi\Queries::STATUS_NEW;

        foreach ($productsIds as $page) {
            if (!Db::getInstance()->execute(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getAddJobQueries($page, $productStatus))) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Get All Product ids 
     * 
     * @return mixed
     */
    private function getAllProductsIds() {
        return Db::getInstance()->executeS('SELECT id_product FROM `' . _DB_PREFIX_ . 'product` WHERE `active` = 1');
    }

    /**
     * Update functions
     *
     * @return void
     */
    private function bobsi_plugin_check_update() {
        if (version_compare(_PS_VERSION_, '1.6.0.0', '<')) {
            $database_version = Configuration::get('bobsi_db_version');

            if ($database_version) {
                if (version_compare($database_version, '2.0.5', '<')) {
                    $this->bobsi_update();
                }
            } else {
                $this->bobsi_update();
            }
        }
    }

    /**
     * Update plugin
     *
     * @return void
     */
    private function bobsi_update() {
        if ($this->check_db_table()) {
            if (!$this->check_field_exist()) {
                $this->addAllProductsInTradefeedQueue(TRUE);
                $query = "ALTER TABLE "
                    . _DB_PREFIX_
                    . bobsi\Queries::TABLE_BOBSI_TRADEFEED
                    . " ADD `images` text AFTER `image_url`";

                Db::getInstance()->execute($query);
            } else {
                Configuration::updateValue('bobsi_db_version', $this->version);
            }
        }

    }

    /**
     * Delete setting from DB
     * 
     * @return mixed
     */
    private function bobsi_delete_update_settings() {
        return Configuration::deleteByName('bobsi_db_version');
    }

    /**
     * Check DB table
     *
     * @return bool
     */
    private function check_db_table() {
        $check_audit_table = "SHOW TABLES LIKE '" . _DB_PREFIX_ . bobsi\Queries::TABLE_BOBSI_TRADEFEED_AUDIT . "'";
        $check_product_table = "SHOW TABLES LIKE '" . _DB_PREFIX_ . bobsi\Queries::TABLE_BOBSI_TRADEFEED . "'";
        $result = Db::getInstance()->executeS($check_audit_table) &&
            Db::getInstance()->executeS($check_product_table);

        return $result;
    }

    /**
     * Check DB table column
     *
     * @return mixed
     */
    private function check_field_exist() {
        $check_images_field = "
          SELECT IF(count(*) = 1, true, false) AS result
          FROM
            information_schema.columns
          WHERE
            table_schema = '" . _DB_NAME_ . "'
            AND table_name = '" . _DB_PREFIX_ . bobsi\Queries::TABLE_BOBSI_TRADEFEED . "'
            AND column_name = 'images';";
        $field = Db::getInstance()->getRow($check_images_field);

        return $field['result'];
    }
}
