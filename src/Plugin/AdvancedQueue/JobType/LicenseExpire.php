<?php

namespace Drupal\commerce_license\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the job type for expiring licenses.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_license_expire",
 *   label = @Translation("Expire licenses."),
 * )
 */
class LicenseExpire extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LicenseExpire object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $license_id = $job->getPayload()['license_id'];
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    /** @var \Drupal\commerce_license\Entity\License $license */
    $license = $license_storage->load($license_id);
    if (!$license) {
      return JobResult::failure('License not found.');
    }

    if ($license->state->value != 'active') {
      return JobResult::failure('License is no longer active.');
    }

    try {
      // Set the license to expired. The plugin will take care of revoking it.
      $license->state = 'expired';
      $license->save();
    }
    catch (Exception $exception) {
      return $result = JobResult::failure($exception->getMessage());
    }
    return JobResult::success();
  }

}
