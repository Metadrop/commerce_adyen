<?php

namespace Commerce\Adyen\Payment;

use Adyen\Service\Modification as ModificationBase;
use Commerce\Utils\Payment\Action\ActionBase;

/**
 * Abstract modification request.
 *
 * @property \Commerce\Adyen\Payment\Transaction\Refund|\Commerce\Adyen\Payment\Transaction\Payment $transaction
 *
 * @link https://github.com/Adyen/adyen-php-sample-code/blob/master/4.Modifications/httppost/refund.php
 * @link https://github.com/Adyen/adyen-php-sample-code/blob/master/4.Modifications/httppost/capture.php
 */
abstract class Modification extends ActionBase {

  use Client;

  /**
   * Marker of request to refund the money.
   */
  const REFUND = 'refund';
  /**
   * Marker of request to capture the money.
   */
  const CAPTURE = 'capture';

  /**
   * {@inheritdoc}
   */
  protected function buildRequest() {
    $currency_code = $this->transaction->getCurrency();

    // Make an API call to tell Adyen that we are waiting for notification
    // from it.
    return [
      'reference' => $this->transaction->getOrder()->order_number->value(),
      'merchantAccount' => $this->transaction->getPaymentMethod()['settings']['merchant_account'],
      'originalReference' => $this->transaction->getRemoteId(),
      'modificationAmount' => [
        'currency' => $currency_code,
        // Adyen doesn't accept amount with preceding minus.
        'value' => abs(commerce_adyen_amount($this->transaction->getAmount(), $currency_code)),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Adyen\AdyenException
   */
  protected function sendRequest($request) {
    $modification = (new ModificationBase($this->getClient($this->getPaymentMethod())))->{$this->action}($request);

    if ("[{$this->action}-received]" !== $modification['response']) {
      throw new \RuntimeException(t('Capture request was not received by Adyen.'));
    }

    return $modification;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTransactionType() {
    switch ($this->action) {
      case self::REFUND:
        return 'refund';

      case self::CAPTURE:
        return 'payment';
    }

    throw new \InvalidArgumentException(t('The "@modification" modification request is not supported.', [
      '@modification' => $this->action,
    ]));
  }

}
