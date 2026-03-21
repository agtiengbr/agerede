<?php

class AgRedeTransactionBuilder
{
    private $data = [];

    public function __construct(float $amountReais, string $reference)
    {
        $this->data['amount'] = (int) round($amountReais * 100); // centavos
        $this->data['reference'] = $reference;
    }

    public function creditCard(string $number, string $cvv, string $month, string $year, string $holder): self
    {
    $this->data['kind'] = 'credit';
    $this->data['origin'] = 1;
        $this->data['cardNumber'] = preg_replace('/[^0-9]/', '', $number);
        $this->data['securityCode'] = preg_replace('/[^0-9]/', '', $cvv);
        $this->data['expirationMonth'] = (int) $month;
        $this->data['expirationYear'] = (int) $year;
        $this->data['cardHolderName'] = $holder;
        return $this;
    }

    public function debitCard(string $number, string $cvv, string $month, string $year, string $holder): self
    {
    $this->data['kind'] = 'debit';
    $this->data['origin'] = 1;
        $this->data['capture'] = 'true';
        $this->data['cardNumber'] = preg_replace('/[^0-9]/', '', $number);
        $this->data['securityCode'] = preg_replace('/[^0-9]/', '', $cvv);
        $this->data['expirationMonth'] = (int) $month;
        $this->data['expirationYear'] = (int) $year;
        $this->data['cardHolderName'] = $holder;
        return $this;
    }

    public function installments(int $qty): self
    {
        if ($qty > 1) {
            $this->data['installments'] = $qty;
        }
        return $this;
    }

    public function threeDSecure(string $failureUrl, string $successUrl): self
    {
        $this->data['threeDSecure'] = [
            'onFailure' => 'decline',
            'embedded'  => true,
        ];
        $this->data['urls'][] = ['url' => $failureUrl, 'kind' => 'threeDSecureFailure'];
        $this->data['urls'][] = ['url' => $successUrl, 'kind' => 'threeDSecureSuccess'];
        return $this;
    }

    public function antifraud(array $consumer, array $billing, array $shippingList, array $items, ?array $envConsumer = null): self
    {
        // Força o campo como 1 (compat com logs atuais)
        $this->data['antifraudRequired'] = 1;
        $cart = [
            'consumer' => $consumer,
            'billing'  => $billing,
            // API espera lista de endereços de entrega
            'shipping' => $shippingList,
            'items'    => $items,
        ];
        if ($envConsumer) {
            $cart['environment'] = [
                'consumer' => $envConsumer,
            ];
        }
        $this->data['cart'] = $cart;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
