<?php

/**
  Plugin Name: Monetbil - Mobile Money Gateway for Prestashop 1.7
  Plugin URI: https://github.com/Monetbil/monetbil-prestashop-1.7
  Description: A Payment Gateway for Mobile Money Payments - Prestashop 1.7
  Version: 1.12
  Author: Serge NTONG
  Author URI: https://www.monetbil.com/
 */
/*
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License or any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Monetbil extends PaymentModule
{

    const GATEWAY = 'monetbil';
    const WIDGET_URL = 'https://www.monetbil.com/widget/';
    const CHECK_PAYMENT_URL = 'https://api.monetbil.com/payment/v1/checkPayment';
    // Monetbil Order States
    const MONETBIL_OS_SUCCESS_PAYMENT = 'MONETBIL_OS_SUCCESS_PAYMENT';
    const MONETBIL_OS_FAILED_PAYMENT = 'MONETBIL_OS_FAILED_PAYMENT';
    const MONETBIL_OS_CANCELLED_PAYMENT = 'MONETBIL_OS_CANCELLED_PAYMENT';
    // Monetbil Service
    const MONETBIL_SERVICE_KEY = 'MONETBIL_SERVICE_KEY';
    const MONETBIL_SERVICE_SECRET = 'MONETBIL_SERVICE_SECRET';
    const MONETBIL_SERVICE_LOGO = 'MONETBIL_SERVICE_LOGO';
    const MONETBIL_PAYMENT_TITLE = 'MONETBIL_PAYMENT_TITLE';
    const MONETBIL_PAYMENT_DESCRIPTION = 'MONETBIL_PAYMENT_DESCRIPTION';
    // Live mode
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 0;
    const STATUS_CANCELLED = -1;
    //
    const STATUS_SUCCESS_STR = 'success';
    const STATUS_FAILED_STR = 'failed';
    const STATUS_CANCELLED_STR = 'cancelled';

    protected $_html;

    public function __construct()
    {
        $this->name = Monetbil::GATEWAY;
        $this->tab = 'payments_gateways';
        $this->version = '1.12';
        $this->module_key = '';
        $this->is_eu_compatible = 1;
        $this->author = 'Serge NTONG';

        parent::__construct();

        $this->displayName = $this->l('Monetbil');
        $this->description = $this->l('A Payment Gateway for Mobile Money Payments - Prestashop 1.7');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
    }

    public function install()
    {
        if (!parent::install()
                or ! $this->registerHook('displayPayment')
                or ! $this->registerHook('displayPaymentEU')
                or ! $this->registerHook('paymentOptions')
        ) {
            return false;
        }

        // Create Order States
        $this->addOrderStates(Monetbil::MONETBIL_OS_SUCCESS_PAYMENT, 'Monetbil Successful payment', '#086fd1', 'payment', true, true, true, true);
        $this->addOrderStates(Monetbil::MONETBIL_OS_FAILED_PAYMENT, 'Monetbil Payment failed', '#086fd1', '', false, false, false, false);
        $this->addOrderStates(Monetbil::MONETBIL_OS_CANCELLED_PAYMENT, 'Monetbil Transaction cancelled', '#086fd1', '', false, false, false, false);

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
                or ! $this->unregisterHook('displayPayment')
                or ! $this->unregisterHook('displayPaymentEU')
                or ! $this->unregisterHook('paymentOptions')

                or ! $this->unregisterHook('paymentReturn')

                or ! $this->unregisterHook('displayOrderConfirmation')
        ) {
            return false;
        }

        // Delete Order States
        $ordS1 = new OrderState(Configuration::get(Monetbil::MONETBIL_OS_SUCCESS_PAYMENT));
        $ordS1->delete();

        $ordS3 = new OrderState(Configuration::get(Monetbil::MONETBIL_OS_FAILED_PAYMENT));
        $ordS3->delete();

        $ordS5 = new OrderState(Configuration::get(Monetbil::MONETBIL_OS_CANCELLED_PAYMENT));
        $ordS5->delete();

        // Clean configuration table
        Configuration::deleteByName(Monetbil::MONETBIL_SERVICE_KEY);
        Configuration::deleteByName(Monetbil::MONETBIL_SERVICE_SECRET);
        Configuration::deleteByName(Monetbil::MONETBIL_SERVICE_LOGO);

        Configuration::deleteByName(Monetbil::MONETBIL_PAYMENT_TITLE);
        Configuration::deleteByName(Monetbil::MONETBIL_PAYMENT_DESCRIPTION);

        Configuration::deleteByName(Monetbil::MONETBIL_OS_SUCCESS_PAYMENT);
        Configuration::deleteByName(Monetbil::MONETBIL_OS_FAILED_PAYMENT);
        Configuration::deleteByName(Monetbil::MONETBIL_OS_CANCELLED_PAYMENT);

        return true;
    }

    /**
     * This function creates the states
     * for the order. Needed for
     * order creation and updates.
     */
    private function addOrderStates($key, $name, $color, $template, $invoice, $send_email, $paid, $logable)
    {
        // Create a new Order state if not already done
        if (!(Configuration::get($key) > 0)) {
            // Create a new state
            // and set the state
            // as Open

            $orderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));

            $orderState->name = $this->l($name);
            $orderState->invoice = $invoice;
            $orderState->send_email = $send_email;
            $orderState->module_name = $this->name;
            $orderState->color = $color;
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = $logable;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = $paid;
            $orderState->deleted = false;
            $orderState->template = $template;
            $orderState->add();

            // Update the value
            // in the configuration database
            Configuration::updateValue($key, $orderState->id);

            // Create an icon
            if (file_exists(PS_MODULE_DIR . $this->name . '/assets/img/os/logo_white_16.gif')) {
                copy(PS_MODULE_DIR . $this->name
                        . '/assets/img/os/logo_white_16.gif', PS_THEME_DIR
                        . '/assets/img/os/' . $orderState->id . '.gif');
            }
        }
    }

    /**
     * Configuration content
     */
    public function getContent()
    {
        $this->_html .=$this->postProcess();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Configuration processing
     * @return type
     */
    public function postProcess()
    {
        $result = false;
        if (Tools::isSubmit('SubmitPaymentConfiguration')) {
            $r = Configuration::updateValue(Monetbil::MONETBIL_SERVICE_KEY, Tools::getValue(Monetbil::MONETBIL_SERVICE_KEY));
            $r2 = $r and Configuration::updateValue(Monetbil::MONETBIL_SERVICE_SECRET, Tools::getValue(Monetbil::MONETBIL_SERVICE_SECRET));
            $r3 = $r2 and Configuration::updateValue(Monetbil::MONETBIL_SERVICE_LOGO, Tools::getValue(Monetbil::MONETBIL_SERVICE_LOGO));
            $r4 = $r3 and Configuration::updateValue(Monetbil::MONETBIL_PAYMENT_TITLE, Tools::getValue(Monetbil::MONETBIL_PAYMENT_TITLE));
            $result = $r4 and Configuration::updateValue(Monetbil::MONETBIL_PAYMENT_DESCRIPTION, Tools::getValue(Monetbil::MONETBIL_PAYMENT_DESCRIPTION));

            if ($result) {
                return $this->displayConfirmation($this->l('Configuration updated with success'));
            }

            return $this->displayWarning($this->l('Your configuration has not been saved'));
        }

        return '';
    }

    /**
     * Admin configuration form
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Payment Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'description' => $this->l('Get your service keys here -> https://www.monetbil.com/services'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Service key'),
                        'name' => Monetbil::MONETBIL_SERVICE_KEY,
                        'required' => true,
                        'empty_message' => $this->l('Please fill the payment service key'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Service secret'),
                        'name' => Monetbil::MONETBIL_SERVICE_SECRET,
                        'required' => true,
                        'empty_message' => $this->l('Please fill the payment service secret'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Your service logo link (Recommended 194x49 px)'),
                        'name' => Monetbil::MONETBIL_SERVICE_LOGO,
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Payment title on checkout page'),
                        'name' => Monetbil::MONETBIL_PAYMENT_TITLE,
                        'required' => true
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Payment description on checkout page'),
                        'name' => Monetbil::MONETBIL_PAYMENT_DESCRIPTION,
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = 'monetbil';
        $helper->identifier = 'monetbil';
        $helper->submit_action = 'SubmitPaymentConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Retrieving admin form configuration variables
     */
    public function getConfigFieldsValues()
    {
        return [
            Monetbil::MONETBIL_SERVICE_KEY => Tools::getValue(Monetbil::MONETBIL_SERVICE_KEY, Configuration::get(Monetbil::MONETBIL_SERVICE_KEY)),
            Monetbil::MONETBIL_SERVICE_SECRET => Tools::getValue(Monetbil::MONETBIL_SERVICE_SECRET, Configuration::get(Monetbil::MONETBIL_SERVICE_SECRET)),
            Monetbil::MONETBIL_SERVICE_LOGO => Tools::getValue(Monetbil::MONETBIL_SERVICE_LOGO, Configuration::get(Monetbil::MONETBIL_SERVICE_LOGO)),
            Monetbil::MONETBIL_PAYMENT_TITLE => Tools::getValue(Monetbil::MONETBIL_PAYMENT_TITLE, Configuration::get(Monetbil::MONETBIL_PAYMENT_TITLE, null, null, null, $this->l('Mobile Money (Monetbil)'))),
            Monetbil::MONETBIL_PAYMENT_DESCRIPTION => Tools::getValue(Monetbil::MONETBIL_PAYMENT_DESCRIPTION, Configuration::get(Monetbil::MONETBIL_PAYMENT_DESCRIPTION, null, null, null, $this->l('Pay safely using your MTN Mobile Money, Orange Money, Visa/MasterCard... account.')))
        ];
    }

    /**
     * @param array $params
     * @see hookPayment
     */
    public function hookDisplayPayment($params)
    {
        return $this->hookPayment($params);
    }

    /**
     * show module on payment step
     * this hook is used to output the current method of payment to the choice
     * list of available methods on the checkout pages.
     *
     * @param array $params
     * @return string
     */
    public function hookPayment($params)
    {
        return $this->hookPaymentOptions($params);
    }

    /**
     * Affichage du paiement dans le checkout
     * PS 17 
     * @param type $params
     * @return type
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active
                or ! Monetbil::getServiceKey()
                or ! Monetbil::getServiceSecret()
        ) {
            return;
        }

        $cart = $this->context->cart;

        $apiPayement = new PaymentOption();

        if (!$cart instanceof Cart) {
            return;
        }

        if ($cart->id === null) {
            return;
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        $total = Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH), 0);

        // Get the return url
        $return_url = $this->context->link->getModuleLink(Monetbil::GATEWAY, 'return', array(), true);

        // Get the notify url
        $notify_url = $this->context->link->getModuleLink(Monetbil::GATEWAY, 'notify', array(), true);

        $monetbil_args = array(
            'amount' => $total,
            'phone' => '',
            'locale' => '', // Display language fr or en
            'country' => 'CM',
            'currency' => 'XAF',
            'item_ref' => $cart->id,
            'payment_ref' => $cart->secure_key . '/' . mt_rand(1000, 999999),
            'user' => $customer->id,
            'first_name' => $customer->lastname,
            'last_name' => $customer->firstname,
            'email' => $customer->email,
            'logo' => Monetbil::getServiceLogo(),
            'return_url' => $return_url,
            'notify_url' => $notify_url
        );


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.monetbil.com/widget/v2.1/' . Monetbil::getServiceKey());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($monetbil_args, '', '&'));

        $response = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($response, true);

        $payment_url = '';
        if (is_array($result) and array_key_exists('payment_url', $result)) {
            $payment_url = $result['payment_url'];
        }

        if (!$payment_url) {
            return array();
        }

        $this->context->smarty->assign(array(
            'payment_url' => $payment_url,
            'description' => Monetbil::getPaymentDescription(),
        ));

        $apiPayement->setModuleName($this->name)
                ->setCallToActionText(Monetbil::getPaymentTitle())
                ->setForm($this->fetch('module:monetbil/views/templates/hook/payment_widget_v2.tpl'))
                ->setAdditionalInformation($this->fetch('module:monetbil/views/templates/hook/displayPaymentApi.tpl'));

        return array($apiPayement);
    }

    /**
     * return correct path for .tpl file
     *
     * @param string $file
     * @return string
     */
    public function getHookTemplate($file)
    {
        return '/views/templates/hook/' . $file;
    }

    /**
     * return correct path for .tpl file
     *
     * @param string $file
     * @return string
     */
    public function getFrontTemplate($file)
    {
        return '/views/templates/front/' . $file;
    }

    /**
     * return correct path for .tpl file
     *
     * @param $file
     * @return string
     */
    public function getBackTemplate($file)
    {
        return '/views/templates/back/' . $file;
    }

    /**
     * Check if is config page
     *
     * @return boolean
     */
    public function isMonetbilConfigurationPage()
    {
        return Monetbil::GATEWAY == Monetbil::getQuery('configure');
    }

    /**
     * sign
     *
     * @return string
     */
    public static function sign($service_secret, $params)
    {
        ksort($params);
        $signature = md5($service_secret . implode('', $params));
        return $signature;
    }

    /**
     * checkSign
     *
     * @return boolean
     */
    public static function checkSign($service_secret, $params)
    {
        if (!array_key_exists('sign', $params)) {
            return false;
        }

        $sign = $params['sign'];
        unset($params['sign']);

        $signature = Monetbil::sign($service_secret, $params);

        return ($sign == $signature);
    }

    /**
     * checkPayment
     *
     * @param string $paymentId
     * @return int payment_status
     */
    public static function checkPayment($paymentId)
    {
        $postData = array(
            'paymentId' => $paymentId
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, Monetbil::CHECK_PAYMENT_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData, '', '&'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $json = curl_exec($ch);
        $result = json_decode($json, true);

        $payment_status = 0;
        if (is_array($result) and array_key_exists('transaction', $result)) {
            $transaction = $result['transaction'];

            $payment_status = (int) $transaction['status'];
        }

        return $payment_status;
    }

    /**
     * getQueryParams
     *
     * @return array
     */
    public static function getQueryParams()
    {
        $queryParams = array();
        $parts = explode('?', Monetbil::getUrl());

        if (isset($parts[1])) {
            parse_str($parts[1], $queryParams);
        }

        return $queryParams;
    }

    /**
     * getServerUrl
     *
     * @return string
     */
    public static function getServerUrl()
    {
        $server_name = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        $scheme = 'http';

        if ('443' === $port) {
            $scheme = 'https';
        }

        $url = $scheme . '://' . $server_name;
        return $url;
    }

    /**
     * getUrl

     * @return string
     */
    public static function getUrl()
    {
        $url = Monetbil::getServerUrl() . Monetbil::getUri();
        return $url;
    }

    /**
     * getUri
     *
     * @return string
     */
    public static function getUri()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $uri = '/' . ltrim($requestUri, '/');

        return $uri;
    }

    /**
     * getPost
     *
     * @param string $key
     * @param string $default
     * @return string | null
     */
    public static function getPost($key = null, $default = null)
    {
        return $key == null ? $_POST : (isset($_POST[$key]) ? $_POST[$key] : $default);
    }

    /**
     * getQuery
     *
     * @param string $key
     * @param string $default
     * @return string | null
     */
    public static function getQuery($key = null, $default = null)
    {
        return $key == null ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : $default);
    }

    /**
     * getServiceKey
     *
     * @return string
     */
    public static function getServiceKey()
    {
        return Configuration::get(Monetbil::MONETBIL_SERVICE_KEY);
    }

    /**
     * getServiceSecret
     * @return string
     */
    public static function getServiceSecret()
    {
        return Configuration::get(Monetbil::MONETBIL_SERVICE_SECRET);
    }

    /**
     * getServiceLogo
     * @return string
     */
    public static function getServiceLogo()
    {
        return Configuration::get(Monetbil::MONETBIL_SERVICE_LOGO);
    }

    /**
     * getPaymentTitle
     *
     * @return string
     */
    public static function getPaymentTitle()
    {
        return Configuration::get(Monetbil::MONETBIL_PAYMENT_TITLE);
    }

    /**
     * getPaymentDescription
     *
     * @return string
     */
    public static function getPaymentDescription()
    {
        return Configuration::get(Monetbil::MONETBIL_PAYMENT_DESCRIPTION);
    }

}
