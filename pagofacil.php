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

if (!defined('_PS_VERSION_')) {
    exit;
}
include_once 'vendor/autoload.php';
use PagoFacil\lib\Request;
use PagoFacil\lib\Transaction;

define("PF_SERVER_DESARROLLO", "https://pagofacil.ngrok.io/initTransaction");
define("PF_SERVER_BETA", "https://gw-beta.pagofacil.cl/initTransaction");
define("PF_SERVER_PRODUCCION", "https://gw.pagofacil.cl/initTransaction");

class PagoFacil extends PaymentModule
{
    protected $config_form = false;

    public $token_service;
    public $esDevel;
    public $token_secret;

    public function __construct()
    {
        $this->name = 'pagofacil';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Stephanie Piñero';
        $this->need_instance = 0;
        $this->module_key = 'ffdce70775ba802ba870a3312e6941a5';
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        $config = Configuration::getMultiple(array(
          'PAGOFACIL16_TOKEN_SERVICE',
          'PAGOFACIL16_TOKEN_SECRET',
          'PAGOFACIL16_ES_DEVEL'
        ));
        if (!empty($config['PAGOFACIL16_TOKEN_SERVICE'])) {
            $this->token_service = $config['PAGOFACIL16_TOKEN_SERVICE'];
        }
        if (!empty($config['PAGOFACIL16_TOKEN_SECRET'])) {
            $this->token_secret = $config['PAGOFACIL16_TOKEN_SECRET'];
        }
        if (!empty($config['PAGOFACIL16_ES_DEVEL'])) {
            $this->esDevel = $config['PAGOFACIL16_ES_DEVEL'];
        }

        parent::__construct();

        $this->displayName = $this->l('Pago Facil SpA');
        $desc ='Sell ​​with different means of payment in your store instantaneously with Pago Facil.';
        $this->description = $this->l($desc);
        $this->confirmUninstall = $this->l('When uninstalling, you will not be able to receive payments. Are you sure?');
        $this->limited_countries = array('CL');
        $this->limited_currencies = array('CLP');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);


        if (!isset($this->token_secret) || !isset($this->token_service)) {
            $this->warning = $this->l('Token Service and Token Secret they must be configured to continue.');
        }
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        Configuration::updateValue('PAGOFACIL16_ES_DEVEL', false);

        /*
        * We generate the new order state
        */
        if (!$this->installOrderState()) {
            return false;
        }

        return parent::install() &&
          $this->registerHook('header') &&
          $this->registerHook('backOfficeHeader') &&
          $this->registerHook('payment') &&
          $this->registerHook('paymentReturn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PAGOFACIL16_ES_DEVEL');
        Configuration::deleteByName('PAGOFACIL16_TOKEN_SECRET');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {

      /**
       * If values have been submitted in the form, process.
       */
        if (((bool)Tools::isSubmit('submitPagoFacil16Module')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPagoFacil16Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
          . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
          'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
          'languages' => $this->context->controller->getLanguages(),
          'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
          'form' => array(
              'legend' => array(
                  'title' => $this->l('Settings'),
                  'icon' => 'icon-cogs',
              ),
              'input' => array(
                  array(
                      'type' => 'switch',
                      'label' => $this->l('is Development'),
                      'name' => 'PAGOFACIL16_ES_DEVEL',
                      'is_bool' => true,
                      'desc' => $this->l('Do you want to connect to the test server?'),
                      'values' => array(
                          array(
                              'id' => 'active_on',
                              'value' => true,
                              'label' => $this->l('Enabled')
                          ),
                          array(
                              'id' => 'active_off',
                              'value' => false,
                              'label' => $this->l('Disabled')
                          )
                      ),
                  ),
                  array(
                      'col' => 3,
                      'type' => 'text',
                      'prefix' => '<i class="icon icon-gear"></i>',
                      'desc' => $this->l('Enter the token assigned to your service.'),
                      'name' => 'PAGOFACIL16_TOKEN_SERVICE',
                      'label' => $this->l('Token Service'),
                  ),
                  array(
                      'col' => 3,
                      'type' => 'text',
                      'prefix' => '<i class="icon icon-key"></i>',
                      'desc' => $this->l('Enter the token assigned to your service.'),
                      'name' => 'PAGOFACIL16_TOKEN_SECRET',
                      'label' => $this->l('Token Secret'),
                  )
              ),
              'submit' => array(
                  'title' => $this->l('Save'),
              ),
          ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
          'PAGOFACIL16_ES_DEVEL' => Configuration::get('PAGOFACIL16_ES_DEVEL'),
          'PAGOFACIL16_TOKEN_SERVICE' => Configuration::get('PAGOFACIL16_TOKEN_SERVICE', null),
          'PAGOFACIL16_TOKEN_SECRET' => Configuration::get('PAGOFACIL16_TOKEN_SECRET', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
          'id_order' => $order->id,
          'reference' => $order->reference,
          'params' => $params,
          'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }
    /*
    * Function for generate the order state
    */
    public function installOrderState()
    {
        if (Configuration::get('PS_OS_PAGOFACIL_PENDING_PAYMENT') < 1) {
            $order_state = new OrderState();
            $order_state->send_email = false;
            $order_state->module_name = $this->name;
            $order_state->invoice = false;
            $order_state->color = '#98c3ff';
            $order_state->logable = true;
            $order_state->shipped = false;
            $order_state->unremovable = false;
            $order_state->delivery = false;
            $order_state->hidden = false;
            $order_state->paid = false;
            $order_state->deleted = false;
            $lang = (int)Configuration::get('PS_LANG_DEFAULT');
            $order_state->name = array( $lang => pSQL($this->l('Pago Facil - Outstanding')));
            if ($order_state->add()) {
                // We save the order State ID in Configuration database
                Configuration::updateValue('PS_OS_PAGOFACIL_PENDING_PAYMENT', $order_state->id);
            } else {
                return false;
            }
        }
        return true;
    }
}
