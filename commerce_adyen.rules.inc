<?php

/**
 * @file
 * Rules integration.
 */

/**
 * Implements hook_rules_action_info().
 */
function commerce_adyen_rules_action_info() {
  $actions = [];

  // @see commerce_adyen_capture_request()
  $actions['commerce_adyen_capture_request'] = [
    'label' => t('Adyen capture request'),
    'group' => 'Commerce Order',
    'parameter' => [
      'order' => [
        'type' => 'commerce_order',
        'label' => t('Commerce order'),
      ],
    ],
  ];

  return $actions;
}
