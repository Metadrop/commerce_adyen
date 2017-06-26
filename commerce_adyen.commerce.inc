<?php

/**
 * @file
 * Commerce integration.
 */

use Commerce\Utils\PaymentPlugin;
use Commerce\Adyen\Payment\Notification;
use Commerce\Adyen\Payment\NotificationController;
use Commerce\Adyen\Payment\Form\SettingsForm;
use Commerce\Adyen\Payment\Form\SubmitForm;
use Commerce\Adyen\Payment\Form\RedirectForm;
use Commerce\Adyen\Payment\Authorisation\Request;
use Commerce\Adyen\Payment\Authorisation\Response;
use Commerce\Adyen\Payment\Transaction\Refund as RefundTransaction;
use Commerce\Adyen\Payment\Transaction\Payment as PaymentTransaction;

/**
 * Implements hook_commerce_payment_method_info().
 */
function commerce_adyen_commerce_payment_method_info() {
  $info = [];

  $info[COMMERCE_ADYEN_PAYMENT_METHOD] = [
    'title' => 'Adyen',
    'description' => t('Redirect users to submit payments through Adyen.'),
    'active' => TRUE,
    'terminal' => FALSE,
    'offsite' => TRUE,
    'offsite_autoredirect' => TRUE,
    'payment_plugin' => (new PaymentPlugin())
      ->setSettingsFormClass(SettingsForm::class)
      ->setSubmitFormClass(SubmitForm::class)
      ->setRedirectFormClass(RedirectForm::class)
      ->setAuthorisationRequestClass(Request::class)
      ->setAuthorisationResponseClass(Response::class)
      ->setPaymentTransactionClass(PaymentTransaction::class)
      ->setRefundTransactionClass(RefundTransaction::class)
      ->setNotificationsController(NotificationController::class),
  ];

  return $info;
}

/**
 * Implements hook_commerce_adyen_notification().
 */
function commerce_adyen_commerce_adyen_notification($event_code, \stdClass $order, \stdClass $data) {
  switch ($event_code) {
    case Notification::AUTHORISATION:
    case Notification::PENDING:
      // Load/create transaction object.
      $transaction = commerce_adyen_get_transaction_instance('payment', $order);

      if ($data->success) {
        if (Notification::AUTHORISATION === $event_code) {
          if (!$transaction->isAuthorised()) {
            $transaction->authorise($data->pspReference);
            $transaction->setPayload($data);
            $transaction->save();

            // Send capture request automatically.
            commerce_adyen_capture_request($order);
            commerce_payment_redirect_pane_next_page($order);
          }
        }
        // This state is available in case of "Boleto"
        // payments. Capturing is not needed.
        elseif (Notification::PENDING === $event_code) {
          if (!$transaction->isPending()) {
            $transaction->pending($data->pspReference);
            $transaction->setPayload($data);
            $transaction->save();

            commerce_order_status_update($order, 'pending');
            commerce_payment_redirect_pane_next_page($order);
          }
        }
      }
      // Allow to fail only new transaction because authorised or
      // pending transaction were processed.
      elseif ($transaction->isNew()) {
        // Something went wrong: order isn't paid.
        $transaction->fail($data->pspReference);
        $transaction->setPayload($data);
        $transaction->save();

        commerce_payment_redirect_pane_previous_page($order);
      }
      break;

    case Notification::CAPTURE:
      if ($data->success) {
        $transaction = commerce_adyen_get_transaction_instance('payment', $order);

        if (!$transaction->isFinalized()) {
          $transaction->finalize();
          $transaction->setPayload($data);
          $transaction->save();
        }
      }
      break;

    case Notification::EXPIRE:
    case Notification::CANCELLATION:
      $transaction = commerce_adyen_get_transaction_instance('payment', $order);

      // Do not allow expire/cancel finalized transactions.
      if (!$transaction->isFinalized()) {
        $transaction->fail($data->pspReference);
        $transaction->setPayload($data);
        $transaction->save();

        commerce_order_status_update($order, 'canceled');
      }
      break;

    case Notification::REFUND:
      // @todo Handle refund notification.
      break;

    case Notification::REFUND_FAILED:
      // @todo Handle failed refund notification.
      break;
  }
}
