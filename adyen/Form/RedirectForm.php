<?php

namespace Commerce\Adyen\Payment\Form;

use Commerce\Utils\Payment\Form\RedirectFormBase;
use Commerce\Utils\Payment\Authorisation\RequestBase;

/**
 * {@inheritdoc}
 *
 * @method \Commerce\Adyen\Payment\Authorisation\Response getResponse()
 */
class RedirectForm extends RedirectFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(RequestBase $request, array &$form, array &$form_state) {
    /* @var \Commerce\Adyen\Payment\Authorisation\Request $request */
    $payment_method = $request->getPaymentMethod();
    $order = $request->getOrder()->value();

    if (!empty($payment_method['settings']['recurring'])) {
      $request->setRecurringContract($payment_method['settings']['recurring']);
    }

    if (!empty($order->data['commerce_adyen_payment_type'])) {
      $payment_controller = commerce_adyen_invoke_controller('payment', $order->data['commerce_adyen_payment_type'], $payment_method['settings']);
      $payment_controller->setCheckoutValues($order->data[$order->data['commerce_adyen_payment_type']]);
      $request->extend($payment_controller);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function response() {
    $response = $this->getResponse();
    $status = $response->getAuthenticationResult();

    switch ($status) {
      case $response::AUTHORISED:
      case $response::PENDING:
        // Allow to authorise/pending the payment on local environments where
        // notifications are not available. Capturing must be done
        // manually from Adyen backend.
        if (variable_get('commerce_adyen_authorise_forcibly', FALSE)) {
          $transaction = $response->getTransaction();
          $transaction->{$response::PENDING === $status ? 'pending' : 'authorise'}($transaction->getRemoteId());
          $transaction->save();

          commerce_adyen_capture_request($response->getOrder()->value());
        }
        break;

      case $response::ERROR:
      case $response::REFUSED:
        throw new \RuntimeException(t('Payment authorisation was not successful. Please try again.'));

      case $response::CANCELLED:
        throw new \Exception(t('Payment has been cancelled.'));
    }
  }

}
