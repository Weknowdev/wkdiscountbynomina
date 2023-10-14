{**
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
 *}

{if !empty($transaction)}
  <section id="{$moduleName}-displayOrderDetail" class="box">
    <p>{l s='Your transaction reference is %transaction%.' mod='wkdiscountbynomina' sprintf=['%transaction%' => $transaction]}</p>
  </section>
{/if}
