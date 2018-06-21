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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class PagoFacil16RedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        // Disable left and right column
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();

        //Verificamos que la orden exista y que esté llenada de manera adecuada.
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
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
         * Si el módulo no permite el pago con el currency actual detenemos el proceso.
         */
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        /*
         * No deberíamos haber llegado acá sin un cliente. Sólo por precaución.
         */
        $customer = new Customer($cart->id_customer);
               
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        


        // Set datas
        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $extra_vars = array();



        /*
         *  Validate order
         *  El estado de esta orden es el nuevo estado pendiente.
         */
        $this->module->validateOrder($cart->id, Configuration::get('PS_OS_PAGOFACIL_PENDING_PAYMENT'), $total, $this->module->displayName, null, $extra_vars, (int) $currency->id, false, $customer->secure_key);


        /*
         * Obtenemos los datos a ocupar para la transacción con Pago Fácil
         */
        $config = Configuration::getMultiple(array('PAGOFACIL16_TOKEN_SERVICE', 'PAGOFACIL16_TOKEN_SECRET','PAGOFACIL16_ES_DEVEL'));
        $token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];
        $esDevel = $config['PAGOFACIL16_ES_DEVEL'];
        $secure_key = $customer->secure_key;
        $cart_id = $cart->id;

        $total = $cart->getOrderTotal(true, Cart::BOTH);

        /*
         * Order existe desde después del validateOrder
         */
        $order = Order::getOrderByCartId((int) ($cart->id));

        /*
         * Datos para la transacción.
         * TODO : Generar token random.
         */

        $callbackUrl = $this->context->link->getModuleLink('PagoFacil16', 'callback');
        $returnUrl = $this->context->link->getModuleLink('PagoFacil16', 'confirmation')."&secure_key=$secure_key&cart_id=$cart_id";
        $cancelUrl = __PS_BASE_URI__;

        $pago_args = [
          "ct_order_id" => $order,
          "ct_token_tienda" => md5($order.$token_secret),
          "ct_monto" => round($total),
          "ct_token_service" => $token_service,
          "ct_email" => $customer->email,
          "ct_currency" =>  $currency->iso_code,
          "ct_url_callback" => $callbackUrl,
          "ct_url_complete" => $returnUrl,
          "ct_url_cancel" => $cancelUrl
        ];

        $pago_args["ct_signature"] = $this->firmarArreglo($pago_args, $token_secret);




        error_log(print_r($pago_args));

        $curl = new Curl\Curl();
        $curl->post('https://t.pagofacil.xyz/v1', $pago_args);

        if ($curl->error) {
            die($curl->error_code);
        } else {
            // echo $curl->response;
            $result = json_decode($curl->response, true);
        }


        // var_dump($curl->request_headers);
        // var_dump($curl->response_headers);
        // echo "<pre>";
        // print_r($result);
        // echo "</pre>";
        // $curl->close();


        // die();
        Tools::redirect($result["redirect"]);

        //Obtener los Endpoints
        // echo "<br>";
        // $curl = new Curl\Curl();
        // $curl->setHeader('X-TRANSACTION', $result["transactionId"]);
        // $curl->setHeader('X-CURRENCY', "CLP");
        // $curl->setHeader('X-SERVICE', $token_service);
        //
        // $curl->get("https://t.pagofacil.xyz/v1/services");
        // if ($curl->error) {
        //     die ($curl->error_code);
        // } else {
        //     // echo $curl->response;
        //     $extServices = json_decode($curl->response, true);
        // }

        // var_dump($curl->request_headers);
        // var_dump($curl->response_headers);
        // echo "<pre>";
        // print_r($result);
        // echo "</pre>";

        /* @var $smarty Smarty */
        // $smarty = $this->context->smarty;
        // $datos = array(
        //     'servicios' => $extServices["externalServices"],
        //     'transactionId' => $result["transactionId"],
        //     'nbProducts' => $cart->nbProducts(),
        //     'cust_currency' => $cart->id_currency,
        //     'currencies' => $this->module->getCurrency((int) $cart->id_currency),
        //     'total' => $total,
        //     'this_path' => $this->module->getPathUri(),
        //     'this_path_bw' => $this->module->getPathUri(),
        //     'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
        // );
        // $smarty->assign($datos);
        //
        //
        //
        // return $this->setTemplate('redirect.tpl');
        // die();
    }

    public function firmarArreglo($arreglo, $secret)
    {

        //Ordeno Arreglo
        ksort($arreglo);
        //Concateno Arreglo
        $mensaje = $this->concatenarArreglo($arreglo);

        //Firmo Mensaje
        $mensajeFirmado = $this->firmarMensaje($mensaje, $secret);

        //Guardo y retorno el mensaje firmado
        $this->ct_firma = $mensajeFirmado;
        return $mensajeFirmado;
    }

    public function firmarMensaje($mensaje, $claveCifrado)
    {
        $mensajeFirmado = hash_hmac('sha256', $mensaje, $claveCifrado);
        return $mensajeFirmado;
    }

    public function concatenarArreglo($arreglo)
    {
        $resultado = "";

        foreach ($arreglo as $field => $value) {
            $resultado .= $field . $value;
        }

        return $resultado;
    }
    //
}
