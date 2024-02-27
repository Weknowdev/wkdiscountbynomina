<?php

/**
 * 2022-2023 WeKnow All Rights Reserved.
 *
 * NOTICE:  All information contained herein is, and remains
 * the property of PrestaAdvanced and its suppliers,
 * if any.  The intellectual and technical concepts contained
 * herein are proprietary to PrestaAdvanced
 * and its suppliers and are protected by trade secret or copyright law.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from PrestaAdvanced
 *
 * @author    WeKnow
 * @copyright 2022-2022 WeKnowdev
 * @license  WeKnowdev All Rights Reserved
 *  International Registered Trademark & Property of WeKnow
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wkdiscountbynomina extends PaymentModule
{
    const PAYMENT_DISCOUNT_STATUS_ORDER = 'PAYMENT_DISCOUNT_STATUS_ORDER';
    const PAYMENT_DISCOUNT_ENABLE = 'PAYMENT_DISCOUNT_ENABLE';
    const PAYMENT_LIMIT_MONTH = 'PAYMENT_LIMIT_MONTH';

    const MODULE_ADMIN_CONTROLLER = 'AdminConfigureWkdiscountbynomina';
    const HOOKS = [
        'actionPaymentCCAdd',
        'actionObjectShopAddAfter',
        'paymentOptions',
        'displayAdminOrderLeft',
        'displayAdminOrderMainBottom',
        'displayCustomerAccount',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayPaymentReturn',
        'displayPDFInvoice',
    ];

    public function __construct()
    {
        $this->name = 'wkdiscountbynomina';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Weknowdev S.R.L';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->controllers = [
            'account',
            'cancel',
            'external',
            'validation',
        ];
        $this->displayName = $this->l('Pago por nómina');
        $this->description = $this->l('Pago por nómina');


        parent::__construct();
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (bool)parent::install()
            && (bool)$this->registerHook(static::HOOKS)
            && $this->installConfiguration()
            && $this->installTabs();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (bool)parent::uninstall()
            && $this->uninstallConfiguration()
            && $this->uninstallTabs();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Redirect to our ModuleAdminController when click on Configure button
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER));
    }

    /**
     * This hook is used to save additional information will be displayed on BO Order View, Payment block with "Details" button
     *
     * @param array $params
     */
    public function hookActionPaymentCCAdd(array $params)
    {
        return;
    }

    /**
     * This hook called after a new Shop is created
     *
     * @param array $params
     */
    public function hookActionObjectShopAddAfter(array $params)
    {
        if (empty($params['object'])) {
            return;
        }

        /** @var Shop $shop */
        $shop = $params['object'];

        if (false === Validate::isLoadedObject($shop)) {
            return;
        }

        $this->addCheckboxCarrierRestrictionsForModule([(int)$shop->id]);
        $this->addCheckboxCountryRestrictionsForModule([(int)$shop->id]);

        if ($this->currencies_mode === 'checkbox') {
            $this->addCheckboxCurrencyRestrictionsForModule([(int)$shop->id]);
        } elseif ($this->currencies_mode === 'radio') {
            $this->addRadioCurrencyRestrictionsForModule([(int)$shop->id]);
        }
    }

    /**
     * @param array $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart)) {
            return [];
        }

        $paymentOptions = [];

        //MontoEstablecidoComprames = Config-TopeMaximoCompraMesNomina  or *TopeMaximoCompraMesNominaEmpresa
        $montoEstablecidoComprames = Configuration::get(static::PAYMENT_LIMIT_MONTH);


        //Preguntas si estoy asociado a una empresa
        //Si estoy asociado a la empresa  entonces pregunto si la empresa tiene permitido el pago por nomina
        $companyAsociate = $this->getCompanyAsociate(Context::getContext()->customer->id);

        if ($companyAsociate && $companyAsociate['pago_nomina'] == 1) {
            if (is_numeric($companyAsociate['tope_maximo']) && $companyAsociate['tope_maximo'] > 0)
                $montoEstablecidoComprames = $companyAsociate['tope_maximo'];
        } else {

            //todo tengo que preguntar si el trabajador especificamente tiene permitido el pago por nomina
            //Si tiene permitido el pago por nomina hago lo mismo que arriba en la linea 162, pregunto si hay un tome maximo definido
            // y se lo pongo a monto establecido

            //todo sino tiene permitido el pago por nomina enronces hago esto del return [] para que no muestre la forma de pago
            //sino esta asociado a una empresa o no tiene pago por nomina no se permite esto
            return [];
        }

        //montogastadonominames = campo monto_gastado_nommes de la tabla wkuser
        $montogastadonominames = $this->getMontoGastadoNominaMes(Context::getContext()->customer->id);

        //Montodisponible de compra en mes = MontoEstablecidoComprames - montogastadonominames
        $montodisponibleCompraMes = $montoEstablecidoComprames - $montogastadonominames;

        $importePagado = $params['cart']->getOrderTotal();

        //Si el Montodisponible > que monto total del pedido entonces permite la forma de pago
        if ($montodisponibleCompraMes > $importePagado && Configuration::get(static::PAYMENT_DISCOUNT_ENABLE)) {
            $paymentOptions[] = $this->getDiscountPaymentOption();
        }

        return $paymentOptions;
    }

    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @param array $params
     *
     * @return string
     * @since PrestaShop 1.7.7 This hook is replaced by displayAdminOrderMainBottom on migrated BO Order View
     *
     */
    public function hookDisplayAdminOrderLeft(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int)$params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayAdminOrderLeft.tpl');
    }

    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @param array $params
     *
     * @return string
     * @since PrestaShop 1.7.7 This hook replace displayAdminOrderLeft on migrated BO Order View
     *
     */
    public function hookDisplayAdminOrderMainBottom(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int)$params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayAdminOrderMainBottom.tpl');
    }

    /**
     * This hook is used to display information in customer account
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        $this->context->smarty->assign([
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayCustomerAccount.tpl');
    }

    /**
     * This hook is used to display additional information on order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayOrderConfirmation.tpl');
    }

    /**
     * This hook is used to display additional information on FO (Guest Tracking and Account Orders)
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayOrderDetail(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayOrderDetail.tpl');
    }

    /**
     * This hook is used to display additional information on bottom of order confirmation page
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayPaymentReturn.tpl');
    }

    /**
     * This hook is used to display additional information on Invoice PDF
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPDFInvoice(array $params)
    {
        if (empty($params['object'])) {
            return '';
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = $params['object'];

        if (false === Validate::isLoadedObject($orderInvoice)) {
            return '';
        }

        $order = $orderInvoice->getOrder();

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/hook/displayPDFInvoice.tpl');
    }


    /**
     * Factory of PaymentOption for PAYMENT_DISCOUNT
     *
     * @return PaymentOption
     */
    private function getDiscountPaymentOption()
    {
        $offlineOption = new PaymentOption();
        $offlineOption->setModuleName($this->name);
        $offlineOption->setCallToActionText($this->l('Pago por nómina'));
        $offlineOption->setAction($this->context->link->getModuleLink($this->name, 'validation', ['option' => 'discount'], true));
        $offlineOption->setAdditionalInformation($this->context->smarty->fetch('module:wkdiscountbynomina/views/templates/front/paymentOptionDiscount.tpl'));
        return $offlineOption;
    }

    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return (bool)Configuration::updateGlobalValue(static::PAYMENT_DISCOUNT_ENABLE, '0')
            && Configuration::updateGlobalValue(static::PAYMENT_DISCOUNT_STATUS_ORDER, '0');
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return (bool)Configuration::deleteByName(static::PAYMENT_DISCOUNT_ENABLE);
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool)$tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int)Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool)$tab->delete();
        }

        return true;
    }

    private function getMontoGastadoNominaMes($iduser)
    {
        $result = 0;

        $sql = 'SELECT monto_gastado_nommes  FROM '._DB_PREFIX_.'wkdsusers WHERE id_user = ' . $iduser;

        $monto = Db::getInstance()->executeS($sql);

        if (isset($monto[0]['monto_gastado_nommes'])) {
            $result = $monto[0]['monto_gastado_nommes'];
        }

        return $result;
    }

    private function getCompanyAsociate($iduser)
    {
        $result = null;

        $sql = 'SELECT c.pago_nomina,c.tope_maximo  FROM '._DB_PREFIX_.'wkdscompany_worker AS cw INNER JOIN '._DB_PREFIX_.'wkdscompany AS c 
                ON cw.idcompany = c.id_wkdscompany WHERE iduser = ' . $iduser;

        $company = Db::getInstance()->executeS($sql);

        if (isset($company[0])) {
            $result = $company[0];
        }

        return $result;
    }
}
