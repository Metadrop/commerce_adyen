<?php

namespace Commerce\Adyen\Payment;

use Commerce\Utils\NotificationControllerBase;
use Commerce\Utils\Payment\Exception\NotificationException;

/**
 * Notifications handler.
 */
class NotificationController extends NotificationControllerBase {

  /**
   * {@inheritdoc}
   */
  const NOTIFICATIONS_PATH = 'commerce/adyen/notification';

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->data->eventCode;
  }

  /**
   * {@inheritdoc}
   */
  public function locateOrder() {
    return empty($this->data->merchantReference) ? NULL : $this->data->merchantReference;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(\stdClass $order) {
    // Yeah, Adyen, you are nice guy! Let's send us something similar
    // in that spirit. We will process everything.
    // The "success" or "live" properties will be strings with "true"
    // or "false" values.
    foreach ($this->data as $property => $value) {
      if (is_string($value)) {
        // Sometimes Adyen can send visually empty strings as values
        // of properties like "success", but programmatically it's not
        // true (space characters inside).
        $this->data->{$property} = trim($value);

        // Convert string representations of booleans.
        switch (drupal_strtolower($this->data->{$property})) {
          case 'true':
            $this->data->{$property} = TRUE;
            break;

          case 'false':
            $this->data->{$property} = FALSE;
            break;
        }
      }
    }

    // Treat any kind of emptiness as explicit "FALSE".
    foreach (['success', 'live'] as $property) {
      if (empty($this->data->{$property})) {
        $this->data->{$property} = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function error(NotificationException $exception) {

  }

  /**
   * {@inheritdoc}
   */
  public function terminate(\Exception $exception) {

  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    // Return "[accepted]" to Adyen. This is essential to let it know that
    // notification has been received. If Adyen do NOT receive "[accepted]"
    // then it'll try to send it again which will put all other notification
    // in a queue.
    print '[accepted]';
    drupal_exit();
  }

}
