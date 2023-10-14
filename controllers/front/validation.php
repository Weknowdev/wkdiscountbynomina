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

/**
 * This Controller receive customer after approval on bank payment page
 */
class WkdiscountbynominaValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @var Wkdiscountbynomina
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            (int) $this->getOrderState(),
            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->getOptionName(),
            null,
            [
                'transaction_id' => Tools::passwdGen(), // Should be retrieved from your Payment response
            ],
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            (int) $this->context->language->id,
            [
                'id_cart' => (int) $this->context->cart->id,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $this->module->currentOrder,
                'key' => $customer->secure_key,
            ]
        ));
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get OrderState identifier
     *
     * @return int
     */
    private function getOrderState()
    {
        $option = Tools::getValue('option');
        $orderStateId = (int) Configuration::get('PS_OS_ERROR');

        switch ($option) {
            case 'discount':
                $orderStateId = (int) Configuration::get(Wkdiscountbynomina::PAYMENT_DISCOUNT_STATUS_ORDER);
                break;
        }

        return $orderStateId;
    }

    /**
     * Get translated Payment Option name
     *
     * @return string
     */
    private function getOptionName()
    {
        $option = Tools::getValue('option');
        $name = $this->module->displayName;

        switch ($option) {
            case 'discount':
                $name = $this->l('discount');
                break;
        }

        return $name;
    }
}
