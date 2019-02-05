<?php

//namespace Commerce\Adyen\Payment\Controller;
namespace Drupal\commerce_adyen\Controller;

/**
 * Base controller.
 */
abstract class Controller {

  /**
   * Configuration of payment type. The values from "configFrom" method.
   *
   * @var array
   *
   * @see \Drupal\commerce_adyen\Controller\Payment::configForm()
   */
  protected $config = [];

  /**
   * OpenInvoice constructor.
   *
   * @param array $config
   *   Payment type configuration.
   */
  public function __construct(array $config) {
    $this->config = $config;
  }

}
