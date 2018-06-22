<?php
/**
 * Copyright 2017 Cristian Tala <yomismo@cristiantala.cl>.
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
 * @author Cristian Tala <yomismo@cristiantala.cl>
 */


class PagoFacil16CallbackModuleFrontController extends ModuleFrontController
{
    public $HTTPHelper;
    public $token_secret;
    public $token_service;

    public function initContent()
    {
        $json_params = Tools::file_get_contents("php://input");
        $json = json_decode($json_params, true);


        $this->HTTPHelper = new \ctala\HTTPHelper\HTTPHelper();
        $config = Configuration::getMultiple(array('PAGOFACIL16_TOKEN_SERVICE', 'PAGOFACIL16_TOKEN_SECRET'));
        $this->token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $this->token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//            error_log("ES POST");
            $this->procesarCallback($json);
            $this->HTTPHelper->my_http_response_code(200);
        } else {
            error_log("NO SE INGRESA POR POST (405)");
            $this->HTTPHelper->my_http_response_code(405);
        }
    }

    protected function procesarCallback($json)
    {
        $order_id = $json["pf_order_id"];
        // error_log("Order ID $order_id");
        $cart = new Cart((int)$order_id);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->HTTPHelper->my_http_response_code(404);
        }

        // Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->HTTPHelper->my_http_response_code(412);
        }

        //Obtenemos la orden
        $order = new Order($order_id);

        //Si la orden está completada no hago nada.
        $PS_OS_PAYMENT = Configuration::get('PS_OS_PAYMENT');
        if ($PS_OS_PAYMENT == $order->getCurrentState()) {
            $this->HTTPHelper->my_http_response_code(400);
        }

        //Obtengo y firmo el arreglo

        $pfs = array();
        //Se obtienen solo los datos que inician en pf_
        foreach ($json as $key => $value) {
            if (!substr_compare("pf_", $key, 0, 3)) {
                $pfs[$key] = $value;
            }
        }

        /*
 * El payload recibido debe de ser verificado contra el mismo firmado.
 */
        $pf_signature = $pfs["pf_signature"];
        unset($pfs["pf_signature"]);
        ksort($pfs);

        $mensaje = $this->concatPayload($pfs);
        $mensajeFirmado = hash_hmac('sha256', $mensaje, $this->token_secret);


        if ($pf_signature == $mensajeFirmado) {
            error_log("FIRMAS CORRESPONDEN");
            $ct_estado = $pfs["pf_status"];
            //Verifico el estado de la orden.
            if ($ct_estado == "completed") {
                //El pedido fue completado exitosamente.
                //Corroboramos los montos.
                if (round($order->total_paid) != $pfs["pf_amount"]) {
                    $this->HTTPHelper->my_http_response_code(400);
                }
                //Completamos el pedido
                self::payment_completed($order);
//                $this->payment_completed($order);
                $this->HTTPHelper->my_http_response_code(200);
            } else {
                //TODO Si el pago no está completo marco como fallida
                $this->HTTPHelper->my_http_response_code(200);
            }
        } else {
            error_log("FIRMAS NO CORRESPONDEN");
            $this->HTTPHelper->my_http_response_code(400);
        }
        $this->HTTPHelper->my_http_response_code(200);
    }


    public static function payment_completed($order)
    {
        $PS_OS_PAYMENT = Configuration::get('PS_OS_PAYMENT');
        if ($PS_OS_PAYMENT != $order->getCurrentState()) {
            $order->setCurrentState($PS_OS_PAYMENT);
            $order->save();
        }
    }


    public function concatPayload($fields)
    {
        $resultado = "";

        foreach ($fields as $field => $value) {
            $resultado .= $field . $value;
        }

        return $resultado;
    }
}
