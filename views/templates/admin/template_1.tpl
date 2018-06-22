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
            <h4>{l s='Servicio de ventas y recaudación online.' mod='pagofacil16'}</h4>
            <h4>{l s='Rápido - Seguro - Confiable' mod='pagofacil16'}</h4>
        </div>
        <div class="col-xs-12 col-md-4 text-center">
            <a href="https://dashboard.pagofacil.org" target="_blank" class="btn btn-primary"
               id="create-account-btn">{l s='Crea tu cuenta ahora!' mod='pagofacil16'}</a><br/>

        </div>
    </div>

    <hr/>

    <div class="PagoFacil16-content">
        <div class="row">
            <div class="col-md-12">
                <h5>{l s='Ofrecemos los siguientes beneficios : ' mod='pagofacil16'}</h5>
                <dl>
                    <dt>&middot; {l s='Incrementa las opciones de pago' mod='pagofacil16'}</dt>
                    <dd>{l s='Visa®, Mastercard®, Diners Club®, American Express®, Khipu, MultiCaja Transferencias, MultiCaja Efectivo, y muchas más.' mod='pagofacil16'}</dd>

                    <dt>&middot; {l s='Ayudamos a mejorar el flujo de caja' mod='pagofacil16'}</dt>
                    <dd>{l s='Recibe el dinero rápidamente en el banco de tu elección.' mod='pagofacil16'}</dd>

                    <dt>&middot; {l s='Seguridad avanzada' mod='pagofacil16'}</dt>
                    <dd>{l s='Comunicación encriptada y herramientas anti fraudes.' mod='pagofacil16'}</dd>

                    <dt>&middot; {l s='Solución única en pagos.' mod='pagofacil16'}</dt>
                    <dd>{l s='Plataforma de reportes 24/7.' mod='pagofacil16'}</dd>
                </dl>
                <em class="text-muted small">
                    * {l s='Los nuevos usuarios deben de ser aprobados.' mod='pagofacil16'}
                    {l s='Nos reservamos el derecho de dar el servicio a los clientes que no sigan los valores de nuestra empresa.' mod='pagofacil16'}
                    {l s='Existe una comisión asociada a los pagos dependiendo del nivel de ventas' mod='pagofacil16'}
                </em>
            </div>


        </div>

        <hr/>

        <div class="row">
            <div class="col-md-12">
                <h4>{l s='Acepta pagos con los medios más populares del país' mod='pagofacil16'}</h4>

                <div class="row">
                    <img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_1_cards.png" class="col-md-6"
                         id="payment-logo"/>
                    <div class="col-md-6">
                        <h6 class="text-branded">{l s='Para transacciones en pesos chilenos (CLP).' mod='pagofacil16'}</h6>
                        <p class="text-branded">{l s='¿Dudas o consultas? : ' mod='pagofacil16'} <a target="_blank" href="mailto:info@pagofacil.cl">info@pagofacil.cl</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
