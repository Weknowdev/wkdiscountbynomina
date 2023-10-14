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
class AdminConfigureWkdiscountbynominaController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $this->fields_options = [
            $this->module->name => [
                'fields' => [
                    Wkdiscountbynomina::PAYMENT_DISCOUNT_ENABLE => [
                        'type' => 'bool',
                        'title' => $this->l('Allow to pay with discount method'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }
}
