<?php

namespace Drupal\commerce_license\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface;

/**
 * Defines the License entity.
 *
 * @ingroup commerce_license
 *
 * @ContentEntityType(
 *   id = "commerce_license",
 *   label = @Translation("License"),
 *   label_collection = @Translation("Licenses"),
 *   label_singular = @Translation("license"),
 *   label_plural = @Translation("licenses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count license",
 *     plural = "@count licenses",
 *   ),
 *   bundle_label = @Translation("License type"),
 *   bundle_plugin_type = "commerce_license_type",
 *   handlers = {
 *     "access" = "Drupal\commerce_license\LicenseAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_license\LicenseListBuilder",
 *     "storage" = "Drupal\commerce_license\LicenseStorage",
 *     "form" = {
 *       "default" = "Drupal\commerce_license\Form\LicenseForm",
 *       "checkout" = "Drupal\commerce_license\Form\LicenseCheckoutForm",
 *       "create" = "Drupal\commerce_license\Form\LicenseCreateForm",
 *       "edit" = "Drupal\commerce_license\Form\LicenseForm",
 *       "delete" = "Drupal\commerce_license\Form\LicenseDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_license",
 *   admin_permission = "administer licenses",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "license_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/licenses/{commerce_license}",
 *     "create-form" = "/admin/commerce/licenses/create",
 *     "edit-form" = "/admin/commerce/licenses/{commerce_license}/edit",
 *     "delete-form" = "/admin/commerce/licenses/{commerce_license}/delete",
 *     "collection" = "/admin/commerce/licenses",
 *   },
 * )
 */
class License extends ContentEntityBase implements LicenseInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
      'created' => \Drupal::service('datetime.time')->getRequestTime(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If the state is being changed to 'active', set a timestamp.
    // We don't notify the license type plugin here in case the save is
    // cancelled by something else.
    // (Note that $this->original is not set on new entities.)
    if ((isset($this->original) && $this->state->value != $this->original->state->value) || !isset($this->original)) {
      if ($this->state->value == 'active') {
        $this->set('granted', \Drupal::service('datetime.time')->getRequestTime());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If the state was changed, notify our license type plugin.
    // (Note that $this->original is not set on new entities.)
    if ((isset($this->original) && $this->state->value != $this->original->state->value) || !isset($this->original)) {
      if ($this->state->value == 'active') {
        // The state is moved to 'active', or the license was created active:
        // the license activates.
        $this->getTypePlugin()->grantLicense($this);
      }

      if (isset($this->original) && $this->original->state->value == 'active') {
        // The state is moved away from 'active': the license is revoked.
        $this->getTypePlugin()->revokeLicense($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Revoke the license if it is active.
    if ($this->state->value == 'active') {
      $this->getTypePlugin()->revokeLicense($this);
    }

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    /** @var \Drupal\commerce_license\LicenseTypeManager $license_type_manager */
    $license_type_manager = \Drupal::service('plugin.manager.commerce_license_type');
    return $license_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function setValuesFromPlugin(LicenseTypeInterface $license_plugin) {
    $license_plugin->setConfigurationValuesOnLicense($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public static function getWorkflowId(LicenseInterface $license) {
    return $license->getTypePlugin()->getWorkflowId();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('OWner'))
      ->setDescription(t('The user ID of the license owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_license\Entity\License::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The license state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_license\Entity\License', 'getWorkflowId']);

    $fields['queues'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Queues'))
      ->setDescription(t('The queues in which this license is currently placed.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['product'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Licensed product'))
      ->setDescription(t('The licensed product.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the license was created.'));

    $fields['granted'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Granted'))
      ->setDescription(t('The time that the license was granted or activated.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 1,
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'n/Y',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the license was last modified.'));

    $fields['expiration'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiration'))
      ->setDescription(t('The time that the license will expire, if any.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 1,
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'n/Y',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    $fields['autoexpire'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Automatic expiration'))
      ->setDescription(t('Whether or not the license will automatically expire.'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
