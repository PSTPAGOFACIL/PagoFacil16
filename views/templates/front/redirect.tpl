{* * 2007-2018 PrestaShop * * NOTICE OF LICENSE * * This source file is subject to the Academic Free License (AFL 3.0) * that is bundled with this package in the file LICENSE.txt. * It is also available through the world-wide-web at this URL: * http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to * obtain it through the world-wide-web, please send an email * to license@prestashop.com so we can send you a copy immediately. * * DISCLAIMER * * Do not edit or add to this file if you
wish to upgrade PrestaShop to newer * versions in the future. If you wish to customize PrestaShop for your * needs please refer to http://www.prestashop.com for more information. * * @author PrestaShop SA
<contact@prestashop.com>
	* @copyright 2007-2018 PrestaShop SA * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0) * International Registered Trademark & Property of PrestaShop SA *} {capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, " step=3 ")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='pagofacil16'}">{l s='Checkout' mod='pagofacil16'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Tarjetas
	de crédito y débito' mod='pagofacil16'} {/capture}

	<div class="row">
		<div class="panel panel-default" style="margin-top:20px">
			<div class="panel-body">
				<div class="col-md-6">
					{if $nbProducts
					<=0 } <p class="warning">{l s='Your shopping cart is empty.' mod='pagofacil16'}</p>
						{else}

						<form action="{$link->getModuleLink('pagofacil', 'validation', [], true)|escape:'html'}" method="post">
							<p>
								<img src="{$this_path_bw}views/img/pf_logo.png" alt="{l s='Pago con Tarjetas' mod='pagofacil'}" width="150" style="float:left; margin: 0px 10px 5px 0px;" /> Haz elegido pagar con los medios de pago asociados a Pago Fácil.
								<br/><br /> Aquí un resumen de tu orden :
							</p>
							<br>
							<div class="panel panel-default" style="margin-top:20px">
								<div class="panel-body"> Monto total :
									<span id="amount" class="price">{displayPrice price=$total}</span> {if $use_taxes == 1} {l s='(tax incl.)' mod='bankwire'} {/if}</div>
							</div>
							<p style="margin-top:20px;">

							</p>

							<p>
								Selecciona un método de pago para continuar.
							</p>
						</form>
						{/if}

				</div>

				<div class="col-md-6">

					{foreach from=$servicios item=servicio}

					<form id='{$servicio["code"]}'>
						<div class="col-xs-12">
							<p class="payment_module">
								<input type="text" name="transaction" value='{$transactionId}'>
								<button onclick='submitPF({$servicio["endpoint"]},{$servicio["code"]})' class="btn btn-default btn-lg alert alert-success" style="width:100%">{$servicio["name"]} <span class="label" style="font-size: smaller;">({$servicio["description"]})</span></button>
							</p>
						</div>
					</form>
					{/foreach}

				</div>
			</div>
		</div>
	</div>
