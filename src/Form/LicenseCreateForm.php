<?php

namespace Drupal\commerce_license\Form;

use Drupal\commerce_license\LicenseTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for License edit forms.
 *
 * @ingroup commerce_license
 */
class LicenseCreateForm extends LicenseForm {

  /**
   * The license type plugin manager.
   *
   * @var \Drupal\commerce_license\LicenseTypeManager
   */
  protected $pluginManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(LicenseTypeManager $plugin_manager, EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $this->pluginManager = $plugin_manager;
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_license_type'),
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    $base_form_id = 'commerce_license_form';
    return $base_form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_license_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $account = NULL) {
    $step = $form_state->get('step');
    if (!$step) {
      $step = 'license_type';
      // Skip the payment method type selection if there's only 1 type.
      $plugins = array_column($this->pluginManager->getDefinitions());
      if (count($plugins) === 1) {
        /** @var \Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface $license_type */
        $license_type = reset($plugins);
        error_log($license_type);
        $form_state->set('license_type', $license_type);
        $step = 'license';
      }
      $form_state->set('step', $step);
    }


    if ($step == 'license_type') {
      $form = $this->buildLicenseTypeForm($form, $form_state, $account);
    }
    elseif ($step == 'license') {
      // Create our license now. We can't do it in RouteMatch because reasons.
      $values = [];
      // If the entity has bundles, fetch it from the route match.
      $values['type'] = $form_state->get('license_type');

      $entity = $this->entityManager->getStorage('commerce_license')->create($values);
      $this->setEntity($entity);

      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

  public function buildLicenseTypeForm(array $form, FormStateInterface $form_state, UserInterface $account = NULL) {
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    $plugin_ids = array_keys($plugins);
    $first_plugin = reset($plugin_ids);

    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('license_type', $first_plugin);

    $form['license_type'] = [
      '#type' => 'select',
      '#title' => $this->t('License type'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
    ];

    // Retrieve and add the form actions array.
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];
    $actions['#type'] = 'actions';
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 'license') {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 'license_type') {
      $form_state->set('license_type', $form_state->getValue('license_type'));
      $form_state->set('step', 'license');
      $form_state->setRebuild(TRUE);
    }
    elseif ($step == 'license') {
      parent::submitForm($form, $form_state);
    }
  }

}
