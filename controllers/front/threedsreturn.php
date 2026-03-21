<?php
class ageredethreedsreturnModuleFrontController extends ModuleFrontController 
{
    public function initContent()
    {
        $obj = new \AgERedeRequest;

        $obj->endpoint = 'Requisição Recebida pela Rede';
        $obj->headers = serialize($_SERVER);
        $obj->method = 'GET';
        $obj->body = serialize($_REQUEST);
        $obj->http_code = '';
        $obj->response = '';

        $obj->save();

        if (Tools::getValue('r') == 0) {
            Logger::addLog("agerede - Erro com verificação 3ds no cliente {$this->context->customer->email}", 2, null, null, null, true);
            $this->errors[] = "Ocorreu uma falha de segurança com a sua transação.";
            $link = $this->context->link->getPageLink('order', true, null, 'step=3');
            $this->redirectWithNotifications($link);
            exit();
        }

        $cart = $this->context->cart;
        if (Tools::getValue('payment_mode') == 0) {
            $payment_str = 'E-Rede - Cartão de Débito';
        } else {
            $payment_str = 'E-Rede - Cartão de Crédito';
        }

        
    $tid = Tools::getValue('tid');
    $client = $this->module->getAgRedeClient();
    $transaction = $client->getTransaction($tid);

        if (Tools::getValue('payment_mode') == 1) {
            $payment_str = 'E-Rede - Cartão de Crédito';
        } else {
            $payment_str = 'E-Rede - Cartão de Débito';
        }

        $order_status = 2;        
        $this->module->validateOrder($cart->id, $order_status, $cart->getOrderTotal(), $payment_str, NULL, NULL, (int)$this->context->currency->id, false, $this->context->customer->secure_key);

        $ps_order = new Order(Order::getOrderByCartId($cart->id));

        $obj = new AgERedeTransaction;
        $obj->id_order = $ps_order->id;
        $obj->tid = $tid;
    $auth = $transaction['authorization'] ?? [];
    $obj->installments = $auth['installments'] ?? null;
    $obj->nsu = $auth['nsu'] ?? null;
    $obj->authorization_code = $auth['authorizationCode'] ?? null;
    $obj->card_bin = $auth['cardBin'] ?? null;
    $obj->last4 = $auth['last4'] ?? null;
        $obj->ip_address = $_SERVER['REMOTE_ADDR'];

    $antifraud = $transaction['antifraud'] ?? [];
    $obj->antifraud_score = $antifraud['score'] ?? null;
    $obj->antifraud_recommendation = $antifraud['recommendation'] ?? null;
    $obj->antifraud_risk_level = $antifraud['riskLevel'] ?? null;

        $obj->save();

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$ps_order->id.'&key='.$this->context->customer->secure_key);
    }
}