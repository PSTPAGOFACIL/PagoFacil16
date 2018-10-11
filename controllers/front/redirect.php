<?php
/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PagoFacil\lib\Request;
use PagoFacil\lib\Transaction;

class PagoFacilRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {

        // Disable left and right column
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();

        //Verify the order
        $cart = $this->context->cart;
        $c = $cart;
        if ($c->id_customer==0 || $c->id_address_delivery==0 || $c->id_address_invoice==0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check if module is enabled
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
            }
        }

        /*
         * If not allowed then stop the proccess
         */
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        /*
         * Verify user
         */
        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Set datas
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $extra_vars = array();
        /*
         *  Validate order
         *  Set new order state
         */
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_PAGOFACIL_PENDING_PAYMENT'),
            $total,
            $this->module->displayName,
            null,
            $extra_vars,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        $config = Configuration::getMultiple(array(
            'PAGOFACIL16_TOKEN_SERVICE',
            'PAGOFACIL16_TOKEN_SECRET',
            'PAGOFACIL16_ES_DEVEL'
        ));
        $token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];
        $esDevel = $config['PAGOFACIL16_ES_DEVEL'];
        $secure_key = $customer->secure_key;
        $cart_id = $cart->id;

        $total = $cart->getOrderTotal(true, Cart::BOTH);

        /*
         * Order exists since validateOrder function
         */
        $order = Order::getOrderByCartId((int)($cart->id));

        /*
         * Transaction data
         */
        $request = new Request();
        $request->account_id = $token_service;
        $request->amount = round($total);
        $request->currency = $currency->iso_code;
        $request->reference = $order;
        $request->customer_email =  $customer->email;
        $request->url_complete = $this->context->link->getModuleLink(
            'pagofacil',
            'confirmation'
        )."&secure_key=$secure_key&cart_id=$cart_id";
        $request->url_cancel = __PS_BASE_URI__;
        $request->url_callback =  $this->context->link->getModuleLink('pagofacil', 'callback');
        $request->shop_country =  Context::getContext()->language->iso_code;
        $request->session_id = date('Ymdhis').rand(0, 9).rand(0, 9).rand(0, 9);
        $transaction = new Transaction($request);
        if ($esDevel) {
            $transaction->environment = 'DESARROLLO';
        } else {
            $transaction->environment = 'PRODUCCION';
        }
        $transaction->setToken($token_secret);
        $transaction->initTransaction($request);
    }
}
