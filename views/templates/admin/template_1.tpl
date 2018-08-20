{*
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <div class="row PagoFacil16-header">
        <a href="https://www.pagofacil.cl" target="_blank">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/pf_logo.png" class="col-xs-6 col-md-4 text-center"
                 id="payment-logo"/>
        </a>
        <div class="col-xs-6 col-md-4 text-center">
            <h4>{l s='Online sales and collection service.' mod='pagofacil'}</h4>
            <h4>{l s='Quick - Safe - Trustworthy' mod='pagofacil'}</h4>
        </div>
        <div class="col-xs-12 col-md-4 text-center">
            <a href="https://dashboard.pagofacil.org" target="_blank" class="btn btn-primary"
               id="create-account-btn">{l s='Create your account now!' mod='pagofacil'}</a><br/>

        </div>
    </div>

    <hr/>

    <div class="PagoFacil16-content">
        <div class="row">
            <div class="col-md-12">
                <h5>{l s='We offer the following benefits: ' mod='pagofacil'}</h5>
                <dl>
                    <dt>&middot; {l s='Increase payment options' mod='pagofacil'}</dt>
                    <dd>{l s='Visa®, Mastercard®, Diners Club®, American Express®, Khipu, MultiCaja Transferencias, MultiCaja Efectivo, and more.' mod='pagofacil'}</dd>

                    <dt>&middot; {l s='We help improve cash flow' mod='pagofacil'}</dt>
                    <dd>{l s='Receive the money quickly in the bank of your choice.' mod='pagofacil'}</dd>

                    <dt>&middot; {l s='Advanced security' mod='pagofacil'}</dt>
                    <dd>{l s='Encrypted communication and anti-fraud tools.' mod='pagofacil'}</dd>

                    <dt>&middot; {l s='Unique solution in payments.' mod='pagofacil'}</dt>
                    <dd>{l s='Platform of reports 24/7.' mod='pagofacil'}</dd>
                </dl>
                <em class="text-muted small">
                    * {l s='New users must be approved.' mod='pagofacil'}
                    {l s='We reserve the right to give service to customers who do not follow the values ​​of our company.' mod='pagofacil'}
                </em>
            </div>
        </div>
        <hr/>
    </div>
</div>
