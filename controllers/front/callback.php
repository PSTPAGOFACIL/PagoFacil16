<?php
/**
 * Copyright 2018 Stephanie Piñero <stephanie@pagofacil.cl>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Description of callback
 *
 * @author Stephanie Piñero <stephanie@pagofacil.cl>
 * @copyright 2007-2018 Pago Fácil SpA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PagoFacil\lib\Transaction;

class PagoFacilCallbackModuleFrontController extends ModuleFrontController
{
    public $token_secret;
    public $token_service;

    public function initContent()
    {
        $response = Tools::file_get_contents("php://input");

        $config = Configuration::getMultiple(array('PAGOFACIL16_TOKEN_SERVICE', 'PAGOFACIL16_TOKEN_SECRET'));
        $this->token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $this->token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // $this->procesarCallback($response);
            $this->procesarCallback($_POST);
            // $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            // $header = $protocol  . ' 200 OK';
            // header($header);
        } else {
            error_log("NO SE INGRESA POR POST (405)");
        }
    }

    protected function procesarCallback($response)
    {
        $config = Configuration::getMultiple(array('PAGOFACIL16_TOKEN_SERVICE', 'PAGOFACIL16_TOKEN_SECRET'));
        $this->token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $this->token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];

        $order_id = $response["x_reference"];
        $cart = new Cart((int)$order_id);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
          $cart->id_address_invoice == 0 || !$this->module->active) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            $header = $protocol  . ' 404 No encontrado';
            header($header);
        }

        // Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            $header = $protocol  . ' 412 Precondition Failed';
            header($header);
        }

        //Get the order
        $order = new Order($order_id);

        //If order complete do nothing
        $PS_OS_PAYMENT = Configuration::get('PS_OS_PAYMENT');
        if ($PS_OS_PAYMENT == $order->getCurrentState()) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            $header = $protocol  . ' 400 Bad Request';
            header($header);
        }

        $transaction = new Transaction();
        $transaction->setToken($this->token_secret);
        //Validate Signed message
        if ($transaction->validate($response)) {
            error_log("FIRMAS CORRESPONDEN");
            //Validate order state
            if ($response['x_result'] == "completed") {
                //Validate amount of order
                if (round($order->total_paid) != $response["x_amount"]) {
                    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                    $header = $protocol  . ' 400 Bad Request';
                    header($header);
                }
                self::paymentCompleted($order);
                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                $header = $protocol  . ' 200 OK';
                header($header);
            } else {
                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                $header = $protocol  . ' 200 OK';
                header($header);
            }
        } else {
            error_log("FIRMAS NO CORRESPONDEN");
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            $header = $protocol  . ' 400 Bad Request';
            header($header);
        }
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        $header = $protocol  . ' 200 OK';
        header($header);
    }

    public static function paymentCompleted($order)
    {
        $PS_OS_PAYMENT = Configuration::get('PS_OS_PAYMENT');
        if ($PS_OS_PAYMENT != $order->getCurrentState()) {
            $order->setCurrentState($PS_OS_PAYMENT);
            $order->save();
        }
    }
}
