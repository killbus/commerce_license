<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseExpiration;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an expiration that never expires.
 *
 * @CommerceLicenseExpiration(
 *   id = "unlimited",
 *   label = @Translation("Unlimited"),
 *   description = @Translation("No expiration date"),
 * )
 */
class Unlimited extends LicenseExpirationBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $form['description'] = [
      '#markup' => 'This license will never expire.',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDate($start) {
    return 0;
  }

}
