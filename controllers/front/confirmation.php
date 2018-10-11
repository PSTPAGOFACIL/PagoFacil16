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

require_once(_PS_MODULE_DIR_ . 'pagofacil' . DIRECTORY_SEPARATOR .'vendor/autoload.php');
use PagoFacil\lib\Transaction;

class PagoFacilConfirmationModuleFrontController extends ModuleFrontController
{
    public $token_secret;
    public $token_service;

    public function initContent()
    {
       
        //$response = Tools::file_get_contents("php://input");

        $config = Configuration::getMultiple(array('PAGOFACIL16_TOKEN_SERVICE', 'PAGOFACIL16_TOKEN_SECRET'));
        $this->token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        $this->token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarCallback($_POST);
        } else {
            error_log("NO SE INGRESA POR POST (405)");
        }
    }

    public function postProcess()
    {
         parent::initContent();
         $response = $_POST;
         // $response = Tools::file_get_contents("php://input");
         
        $config = Configuration::getMultiple(array(
            'PAGOFACIL16_TOKEN_SERVICE',
            'PAGOFACIL16_TOKEN_SECRET',
            'PAGOFACIL16_ES_DEVEL'
        ));
        
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

        $transaction = new Transaction();
        $transaction->setToken($this->token_secret);

        //Validate Signed message
        if ($transaction->validate($response)) {
            error_log(round($order->total_paid));
            error_log("FIRMAS CORRESPONDEN CONFIRMATION");
            //Validate order state
            
            if ($response['x_result'] == "completed") {
                //Validate amount of order
                if (round($order->total_paid) != round($response["x_amount"])) {
                    error_log('montos iguales');
                    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                    $header = $protocol  . ' 200 OK';
                    header($header);
                    $this->setTemplate('module:pagofacil/views/templates/front/redirect.tpl');
                    $cart_id = Tools::getValue('cart_id');
                    $secure_key = Tools::getValue('secure_key');

                    $cart = new Cart((int)$cart_id);
                    $customer = new Customer((int)$cart->id_customer);

                    /**
                     * If the order has been validated we try to retrieve it
                     */
                    $order_id = Order::getOrderByCartId((int)$cart->id);
                    error_log("here");
                    if ($order_id && ($secure_key == $customer->secure_key)) {
                        /**
                         * The order has been placed so we redirect the customer on the confirmation page.
                         */

                        $module_id = $this->module->id;
                        Tools::redirect('index.php?controller=order-confirmation&id_cart='.
                        $cart_id.'&id_module='
                        .$module_id.'&id_order='
                        .$order_id.'&key='.$secure_key);

                        return $this->setTemplate('module:pagofacil/views/templates/front/redirect.tpl');
                    } else {
                        /**
                         * An error occured and is shown on a new page.
                         */
                        $this->errors[] = $this->module->l('An error occured.');
                        return $this->setTemplate('error.tpl');
                    }
                }

                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                $header = $protocol  . ' 200 OK';
                header($header);

                $cart_id = Tools::getValue('id_cart');
                error_log($cart_id);
                $secure_key = Tools::getValue('key');

                $cart = new Cart((int)$cart_id);
                $customer = new Customer((int)$cart->id_customer);

                /**
                 * If the order has been validated we try to retrieve it
                 */
                
                if ($order_id && ($secure_key == $customer->secure_key)) {
                    /**
                     * The order has been placed so we redirect the customer on the confirmation page.
                     */

                    $module_id = $this->module->id;
                    Tools::redirect('index.php?controller=order-confirmation&id_cart='.
                    $cart_id.'&id_module='
                    .$module_id.'&id_order='
                    .$order_id.'&key='.$secure_key);
                } else {
                    /**
                     * An error occured and is shown on a new page.
                     */
                    $this->errors[] = $this->module->l('An error occured.');
                    return $this->setTemplate('error.tpl');
                }
            } else {
                error_log("error");
                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                $header = $protocol  . ' 200 OK';
                header($header);
                return "";
            }
        } else {
            error_log("FIRMAS NO CORRESPONDEN");
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            $header = $protocol  . ' 400 Bad Request';
            header($header);
            return "";
        }
    }
}
