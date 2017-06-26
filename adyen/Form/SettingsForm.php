<?php

namespace Commerce\Adyen\Payment\Form;

use Adyen\Contract;
use Adyen\Environment;
use Commerce\Utils\Payment\Form\SettingsFormBase;

/**
 * {@inheritdoc}
 */
class SettingsForm extends SettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, array &$settings) {
    $payment_types = commerce_adyen_payment_types();
    $backend_link = sprintf('https://ca-%s.adyen.com/ca/ca', isset($settings['mode']) ? $settings['mode'] : Environment::TEST);
    $types = [];

    $link = function ($title, $link) use ($backend_link) {
      // @codingStandardsIgnoreStart
      return ' ' . t('You will find the right value in the <a href="@href" target="_blank">' . $title . '</a>.', [
        // @codingStandardsIgnoreEnd
        '@href' => "{$backend_link}/{$link}",
      ]);
    };

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => t('Mode'),
      '#required' => TRUE,
      '#options' => [
        Environment::TEST => t('Test'),
        Environment::LIVE => t('Live'),
      ],
      '#default_value' => Environment::TEST,
    ];

    $form['merchant_account'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant Account'),
      '#required' => TRUE,
      '#description' => t('Do not confuse this with your Adyen account name.') . $link('account list', 'accounts/show.shtml?accountTypeCode=MerchantAccount'),
    ];

    $form['client_user'] = [
      '#type' => 'textfield',
      '#title' => t('Client user'),
      '#required' => TRUE,
      '#description' => t('Username for a web service.') . $link('user list', 'config/users.shtml?userType=SU&status=Active'),
    ];

    $form['client_password'] = [
      '#type' => 'textfield',
      '#title' => t('Client password'),
      '#required' => TRUE,
      '#description' => t('Password for a web service user.'),
    ];

    $form['skin_code'] = [
      '#type' => 'textfield',
      '#title' => t('Skin Code'),
      '#required' => TRUE,
      '#description' => t('A valid HPP skin code.') . $link('skin list', 'skin/skins.shtml'),
    ];

    $form['hmac'] = [
      '#type' => 'textfield',
      '#title' => t('HMAC key'),
      '#required' => TRUE,
      '#description' => t('Make sure this exactly matches the HMAC in Adyen skin configuration.'),
    ];

    $form['shopper_locale'] = [
      '#type' => 'select',
      '#title' => t('Shopper locale'),
      '#required' => TRUE,
      '#description' => t('A combination of language code and country code to specify the language used in the session.'),
      '#default_value' => 'en_GB',
      // @link https://docs.adyen.com/developers/hpp-manual#createaskin
      '#options' => array_map('t', [
        'zh' => 'Chinese – Traditional',
        'cz' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en_GB' => 'English – British',
        'en_CA' => 'English – Canadian',
        'en_US' => 'English – US',
        'fi' => 'Finnish',
        'fr' => 'French',
        'fr_BE' => 'French – Belgian',
        'fr_CA' => 'French – Canadian',
        'fr_CH' => 'French – Swiss',
        'fy_NL' => 'Frisian',
        'de' => 'German',
        'el' => 'Greek',
        'hu' => 'Hungarian',
        'it' => 'Italian',
        'li' => 'Lithuanian',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'sk' => 'Slovak',
        'es' => 'Spanish',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
      ]),
    ];

    $form['recurring'] = [
      '#type' => 'select',
      '#title' => t('Recurring contract'),
      '#empty_option' => t('Do not used'),
      '#options' => [
        Contract::ONECLICK => t('One click'),
        Contract::RECURRING => t('Recurring'),
        Contract::ONECLICK_RECURRING => t('One click, recurring'),
      ],
    ];

    $form['state'] = [
      '#type' => 'select',
      '#title' => t('Fields state'),
      '#default_value' => 0,
      '#description' => t('State of fields on Adyen HPP.'),
      '#options' => [
        t('Fields are visible and modifiable'),
        t('Fields are visible but unmodifiable'),
        t('Fields are not visible and unmodifiable'),
      ],
    ];

    $form['payment_types'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($payment_types as $payment_type => $data) {
      $config_form = commerce_adyen_invoke_controller('payment', $payment_type, $settings, $payment_types)
        ->configForm();

      if (!empty($config_form)) {
        $config_form['#type'] = 'fieldset';
        $config_form['#title'] = $data['label'];

        $form['payment_types'][$payment_type] = $config_form;
      }

      // Form a list of payment types and their labels.
      $types[$payment_type] = $data['label'];
    }

    $form['default_payment_type'] = [
      '#type' => 'select',
      '#title' => t('Default payment type'),
      '#options' => $types,
      '#disabled' => empty($types),
      '#description' => t('Selected payment type will be set as default extender for the payment request. This value can be changed during checkout process.'),
      '#empty_option' => t('- None -'),
    ];

    $form['use_checkout_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Use checkout forms'),
      '#disabled' => empty($payment_types),
      '#description' => t('Allow to use checkout forms for filing additional data for the payment type.'),
    ];
  }

}
