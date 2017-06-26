<?php

namespace Commerce\Adyen\Payment\Authorisation;

use Adyen\Util\Util;

/**
 * Payment signature.
 */
trait Signature {

  /**
   * {@inheritdoc}
   */
  protected function getSignature() {
    // Re-save the data to don't touch original array.
    $data = $this->data;
    // The "merchantSig" never must not take part in signature calculation.
    unset($data['merchantSig']);

    return Util::calculateSha256Signature($this->getPaymentMethod()['settings']['hmac'], $data);
  }

}
