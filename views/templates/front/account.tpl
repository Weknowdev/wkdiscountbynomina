{**
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
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
  <h1 class="h1">{$moduleDisplayName} - {l s='Transactions' mod='wkdiscountbynomina'}</h1>
{/block}

{block name='page_content'}
  {if $orderPayments}
    <table class="table table-striped table-bordered hidden-sm-down">
      <thead class="thead-default">
      <tr>
        <th>{l s='Order reference' mod='wkdiscountbynomina'}</th>
        <th>{l s='Payment method' mod='wkdiscountbynomina'}</th>
        <th>{l s='Transaction reference' mod='wkdiscountbynomina'}</th>
        <th>{l s='Amount' mod='wkdiscountbynomina'}</th>
        <th>{l s='Date' mod='wkdiscountbynomina'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$orderPayments item=orderPayment}
        <tr>
          <td>{$orderPayment.order_reference}</td>
          <td>{$orderPayment.payment_method}</td>
          <td>{$orderPayment.transaction_id}</td>
          <td>{$orderPayment.amount_formatted}</td>
          <td>{$orderPayment.date_formatted}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {else}
    <div class="alert alert-info">{l s='No transaction' mod='wkdiscountbynomina'}</div>
  {/if}
{/block}
