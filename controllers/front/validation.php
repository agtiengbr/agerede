<?php

class AgERedeValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->module->active) {
            exit();
        }

        $cart = $this->context->cart;
        if (
            $cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->doPayment();
    }
    
    protected function doPayment()
    {
        $cart = $this->context->cart;

        $card_number      = Tools::getValue('agerede_cardnumber');
        $card_cvv         = Tools::getValue('agerede_cvv');
        $expiration_month = Tools::getValue('agerede_month');
        $expiration_year  = Tools::getValue('agerede_year');
        $card_holder      = Tools::getValue('agerede_name');

        if (Tools::getValue('payment_mode') === 'credit_card') {
            $qty_installments = Tools::getValue('agerede_installment');
        }

        $card_data =[
            'card_number' => preg_replace('/[^0-9]/', '', $card_number),
            'cvv'         => $card_cvv,
            'month'       => $expiration_month,
            'year'        => $expiration_year,
            'card_holder' => $card_holder
        ];

        try {
            if (Tools::getValue('payment_mode') === 'credit_card') {
                $transaction = $this->module->createTransactionForCart($cart, $card_data, $qty_installments);
                $payment_str = 'E-Rede - Cartão de Crédito';
            } else {
                $transaction = $this->module->createDebitTransactionForCart($cart, $card_data);
                $payment_str = 'E-Rede - Cartão de Débito';
            }

            $order_status = 2;

            $this->module->validateOrder($cart->id, $order_status, $cart->getOrderTotal(), $payment_str, NULL, NULL, (int)$this->context->currency->id, false, $this->context->customer->secure_key);

            $ps_order = Order::getByCartId($cart->id);

            $obj = new AgERedeTransaction;
            $obj->id_order = $ps_order->id;
            $obj->tid = $transaction['tid'] ?? null;
            $obj->installments = @$qty_installments ?: ($transaction['authorization']['installments'] ?? 1);
            $obj->nsu = $transaction['authorization']['nsu'] ?? null;
            $obj->authorization_code = $transaction['authorization']['authorizationCode'] ?? null;
            $obj->card_bin = $transaction['authorization']['cardBin'] ?? null;
            $obj->last4 = $transaction['authorization']['last4'] ?? null;
            $obj->ip_address = $_SERVER['REMOTE_ADDR'];

            $antifraud = $transaction['antifraud'] ?? [];
            $obj->antifraud_score = $antifraud['score'] ?? null;
            $obj->antifraud_recommendation = $antifraud['recommendation'] ?? null;
            $obj->antifraud_risk_level = $antifraud['riskLevel'] ?? null;

            $obj->add();

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$ps_order->id.'&key='.$this->context->customer->secure_key);
        } catch (Exception $e) {
            $this->module->removeDiscount();

            Logger::addLog('agerede - Erro processando o pagamento do cliente ' . $this->context->customer->email . ' - ' . $e->getMessage(), 3, null, null, null, true);

            if (method_exists($e, 'getCode')) {
                switch($e->getCode()) {
                    case '58':
                        $this->errors[] = 'O pagamento foi recusado por sua operadora de cartão de crédito.';
                        break;
                    default:
                        $this->errors[] = 'Ocorreu um erro ao processar o seu pagamento.';
                        break;
                }
            } else {
                $this->errors[] = 'Ocorreu um erro ao processar o seu pagamento. Por favor confira os dados informados e tente novamente.';
            }

            $link = $this->context->link->getPageLink('order', true, null, 'step=3');
            $this->redirectWithNotifications($link);
        }
    }
}
