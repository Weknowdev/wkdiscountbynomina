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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * An example of module upgrade file
 *
 * @param wkdiscountbynomina $module
 *
 * @return bool
 */
function upgrade_module_2_0_0($module)
{
    // Warning when multiple upgrade available on a shop, all upgrade files will be included and called
    // Keep in mind if you call a custom function here it must have a unique name to avoid a fatal error "Cannot redeclare function"
    // When this will be called, you will have in parameter a module instance of previous version before new files loaded, so you cannot call a function introduced in your new version

    return true;
}
