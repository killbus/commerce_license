<?php

namespace Drupal\commerce_license\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides a form for deleting License entities.
 *
 * @ingroup commerce_license
 */
class LicenseDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var @var \Drupal\commerce_license\Entity\LicenseInterface $license */
    $license = $this->getEntity();
    // Licenses are revoked rather than being deleted.
    $license->getType()->revoke();
  }

}
