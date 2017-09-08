<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides a license type which grants one or more roles.
 *
 * @CommerceLicenseType(
 *   id = "role",
 *   label = @Translation("Role"),
 * )
 */
class Role extends LicenseTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $args = [
      '@id' => $license->license_id,
    ];
    return $this->t('Role license (@id)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'license_role' => ''
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Get the role ID that this license grants.
    $role_id = $license->license_role->first()->target_id;

    // Get the owner of the license and grant them the role.
    $owner = $license->getOwner();
    $owner->addRole($role_id);

    $owner->save();

    // TODO: Log this, as it's something admins should see?
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Get the role ID that this license grants.
    $role_id = $license->license_role->first()->target_id;

    // Get the owner of the license and remove that role.
    $owner = $license->getOwner();
    $owner->removeRole($role_id);

    $owner->save();

    // TODO: Log this, as it's something admins should see?
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roles = \Drupal::service('entity_type.manager')->getStorage('user_role')->loadMultiple();

    // Skip the built-in roles.
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    // Remove the admin role if it exists.
    // TODO: consider removing any role that has is_admin set.
    unset($roles['administrator']);

    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    $form['license_role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Licensed role'),
      '#options' => $options,
      '#default_value' => $this->configuration['license_role'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $this->configuration['license_role'] = $values['license_role'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['license_role'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Roles'))
      ->setDescription(t('The roles this product grants access to.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user_role');

    return $fields;
  }

}
