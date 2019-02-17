<?php

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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class MonetbilreturnModuleFrontController
 *
 * process action with module on payment method page
 */
class MonetbilreturnModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {

        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php');
        }

        $params = Monetbil::getQueryParams();
        $service_secret = Monetbil::getServiceSecret();

        if (!Monetbil::checkSign($service_secret, $params)) {
            Tools::redirect('index.php');
        }

        $data = array();

        $user = (int) Monetbil::getQuery('user');

        $customer = new Customer($user);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPagelink('order', true, null, array('step' => 1)));
        }

        $module = $this->module;
        $module instanceof Monetbil;

        $item_ref = Monetbil::getQuery('item_ref');

        $cart_id = (int) $item_ref;
        $cart = new Cart($cart_id);

        if (!$cart instanceof Cart) {
            Tools::redirect('index.php');
        }

        if (!$module->active
                or $cart->id_customer == 0
                or $cart->id_address_delivery == 0
                or $cart->id_address_invoice == 0
        ) {
            Tools::redirect($this->context->link->getPagelink('order', true, null, array('step' => 1)));
        }

        // Check that this payment option is still available
        $authorized = false;
        foreach (Module::getPaymentModules() as $paymentModule) {
            if ($paymentModule['name'] == $module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            Tools::redirect($this->context->link->getPagelink('order', true, null, array('step' => 1)));
        }

        $id_order = (int) version_compare(_PS_VERSION_, '1.7.1.0', '>=') ? Order::getIdByCartId((int) $cart->id) : Order::getOrderByCartId((int) $cart->id);

        if ($id_order === false) {
            $data['msg_details'] = $this->module->l('The order with this id does not exist.');
        }

        $status = Monetbil::getQuery('status');

        switch ($status) {
            case Monetbil::STATUS_SUCCESS_STR:

                Tools::redirect($this->context->link->getPageLink(
                                'order-confirmation', true, null, array(
                            'id_cart' => (int) $cart->id,
                            'id_module' => (int) $this->module->id,
                            'id_order' => $id_order,
                            'key' => $cart->secure_key,
                )));

                $data['msg_details'] = $this->module->l('Thank you. Your order has been received.');
                break;
            case Monetbil::STATUS_FAILED_STR:
                $data['msg_details'] = $this->module->l('Unfortunately your payment was failed.');
                break;
            case Monetbil::STATUS_CANCELLED_STR:
                $data['msg_details'] = $this->module->l('Unfortunately your payment was cancelled.');
                break;
            default:
                $data['msg_details'] = $this->module->l('The transaction has an unexpected status.');
                break;
        }

        $this->context->smarty->assign($data);
        $this->context->smarty->assign('link', $this->context->link);

        $this->setTemplate('payment_return.tpl');
    }

    /**
     * Prepend module path if PS version >= 1.7
     *
     * @param string      $template
     * @param array       $params
     * @param string|null $locale
     *
     * @throws PrestaShopException
     *
     * @since 3.3.2
     */
    public function setTemplate($template, $params = array(), $locale = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $template = "module:monetbil/views/templates/front/{$template}";
        }
        parent::setTemplate($template, $params, $locale);
    }

}
