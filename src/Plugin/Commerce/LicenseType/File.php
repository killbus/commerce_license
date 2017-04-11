<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides the PayPal payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "file",
 *   label = @Translation("File"),
 * )
 */
class LicenseTypeFile extends LicenseTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $args = [
      '@id' => $license->license_id,
    ];
    return $this->t('PayPal account (@paypal_mail)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['paypal_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('PayPal Email'))
      ->setDescription(t('The email address associated with the PayPal account.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
