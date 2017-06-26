<?php

namespace Commerce\Adyen\Payment\Form;

use Commerce\Utils\Payment\Form\SubmitFormBase;
use Commerce\Adyen\Payment\Controller\Checkout;

/**
 * {@inheritdoc}
 */
class SubmitForm extends SubmitFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, array &$values, array &$checkout_pane) {
    $payment_types = commerce_adyen_payment_types();

    if (!empty($payment_types)) {
      $payment_method = $this->getPaymentMethod();
      $order_wrapper = $this->getOrder();
      $order = $order_wrapper->value();

      // This is a dummy type.
      $payment_type = '';

      // Add a "default" option to be able back to standard payment.
      $options = [
        $payment_type => t('Regular'),
      ];

      // Describe a standard payment type a little.
      $descriptions = [
        $payment_type => [
          '#description' => t('Extra data, required by other payment methods, will not be sent'),
        ],
      ];

      foreach ($payment_types as $name => $data) {
        /* @var \Commerce\Adyen\Payment\Controller\Payment $payment_controller */
        $payment_controller = $data['controllers']['payment'];

        $options[$name] = $data['label'];
        $descriptions[$name]['#description'] = implode(', ', $payment_controller::subTypes());
      }

      // Form have already been submitted. Use selected value.
      if (!empty($pane_values['payment_details']['payment_type'])) {
        $payment_type = $pane_values['payment_details']['payment_type'];
      }
      // In other case we can try to get the chosen payment type from an order.
      elseif (!empty($order->data['commerce_adyen_payment_type'])) {
        $payment_type = $order->data['commerce_adyen_payment_type'];
      }

      // Existing orders can have payment type selected. If this type will
      // be disabled, while an order be in active state, then fatal error
      // will be appeared on redirection to Adyen HPP. But we are not stupid,
      // we are double-check subtype for existence.
      if (empty($payment_types[$payment_type])) {
        $payment_type = '';
      }

      $form['payment_type'] = $descriptions + [
        '#type' => 'radios',
        '#title' => t('Type'),
        '#options' => $options,
        '#default_value' => $payment_type ?: $payment_method['settings']['default_payment_type'],
        '#ajax' => [
          // @see commerce_payment_pane_checkout_form()
          'callback' => 'commerce_payment_pane_checkout_form_details_refresh',
          'wrapper' => 'payment-details',
        ],
      ];

      $checkout_controller = commerce_adyen_invoke_controller('checkout', $payment_type, $payment_method['settings'], $payment_types);

      if (NULL !== $checkout_controller) {
        $checkout_controller->setOrder($order_wrapper);
        $checkout_controller->setPaymentMethod($payment_method);

        if (!empty($payment_method['settings']['use_checkout_form'])) {
          $checkout_form = $checkout_controller->checkoutForm();

          if (!empty($checkout_form)) {
            $form[$payment_type] = $checkout_form;
            $form[$payment_type]['#tree'] = TRUE;
          }
        }

        $form['#checkout_controller'] = $checkout_controller;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$values, array &$balance) {
    // An instance of checkout controller will be available
    // only if payment subtype has been selected.
    if (!empty($values['payment_type'])) {
      $payment_type = $values['payment_type'];
      // Simulate empty list of values from checkout form.
      $values += [$payment_type => []];

      $order = $this->getOrder()->value();
      // Save payment type into an order to be able to use it in a redirect
      // form. It will be needed to instantiate payment controller and
      // extending regular payment by a subtype.
      $order->data['commerce_adyen_payment_type'] = $payment_type;
      // Store values for the payment type from checkout form.
      $order->data[$payment_type] = $values[$payment_type];

      if (!empty($form['#checkout_controller']) && !empty($form[$payment_type]) && !empty($values[$payment_type])) {
        $checkout_controller = $form['#checkout_controller'];

        if ($checkout_controller instanceof Checkout) {
          $checkout_controller->checkoutFormValidate($form[$payment_type], $values[$payment_type]);
        }
      }
    }
  }

}
