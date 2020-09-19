<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_recurring\RecurringOrderManager;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;

/**
 * Prevents purchase of a license that grants rights the user already has.
 *
 * This does not check existing licenses, but checks the granted features
 * directly. For example, for a role license, this checks whether the user has
 * the role the license grants, rather than whether they have a license for
 * that role.
 *
 * Using an availability checker rather than an order processor, even though
 * they currently ultimately do the same thing (as availability checkers are
 * processed by AvailabilityOrderProcessor, which is itself an order processor),
 * because eventually availability checkers should deal with hiding the 'add to
 * cart' form -- see https://www.drupal.org/node/2710107.
 *
 * @see Drupal\commerce_license\LicenseOrderProcessorMultiples
 */
class LicenseAvailabilityCheckerExistingRights implements AvailabilityCheckerInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a LicenseAvailabilityCheckerExistingRights instance.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function applies(PurchasableEntityInterface $entity) {
    // This applies only to product variations which have our license trait on
    // them. Check for the field the trait provides, as checking for the trait
    // on the bundle is expensive -- see https://www.drupal.org/node/2894805.
    if (!$entity->hasField('license_type')) {
      return FALSE;
    }

    // This applies only to license types that implement the interface.
    $license_type_plugin = $entity->get('license_type')->first()->getTargetInstance();
    if ($license_type_plugin instanceof ExistingRightsFromConfigurationCheckingInterface) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Don't do an availability check on recurring orders.
    // Very ugly workaround for the lack of any information about the order at
    // this point.
    // See https://www.drupal.org/project/commerce/issues/2937041
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    foreach ($backtrace as $backtrace_call) {
      // If the availability check is being done for an order being saved or
      // created by Commerce Recurring's RecurringOrderManager, then it's a
      // recurring order, and we shouldn't do anything.
      if (isset($backtrace_call['class']) && $backtrace_call['class'] == RecurringOrderManager::class) {
        return;
      }
    }

    $customer = $context->getCustomer();
    // Load the full user entity for the plugin.
    $user = $this->entityTypeManager->getStorage('user')->load($customer->id());

    // Handle licence renewal.
    /** @var \Drupal\commerce_license\Entity\LicenseInterface $existing_license */
    $existing_license = $this->entityTypeManager
      ->getStorage('commerce_license')
      ->getExistingLicense($entity, $user->id());

    if ($existing_license && $existing_license->canRenew()) {
      return;
    }

    // Shows a message to indicate window start time,
    // in case license is renewable but we're out of its renewable window.
    $unsetNotPurchasableMessage = FALSE;
    if ($existing_license && !is_null($existing_license->getRenewalWindowStartTime())) {
      $this->setRenewalStartTimeMessage(
        $existing_license->getRenewalWindowStartTime(),
        $entity->label()
      );

      // Removes the notPurchasable message.
      $unsetNotPurchasableMessage = TRUE;
      // TODO remove Drupal\commerce_order\Plugin\Validation\Constraint message:
      // @product-label is not available with a quantity of @quantity.
    }

    return $this->checkPurchasable($entity, $user, $unsetNotPurchasableMessage);
  }

  /**
   * Adds a renewalStartTimeMessage status message to queue.
   *
   * @param  int | null $renewal_window_start_time
   *   The renewal window start time
   * @param string $label
   *   The purchased product label
   */
  private function setRenewalStartTimeMessage($renewal_window_start_time, $label) {
    \Drupal::messenger()->addStatus(
      t('You have an existing license for this @product-label. You will be able to renew your license after @date.', [
        '@date' => \Drupal::service('date.formatter')->format($renewal_window_start_time),
        '@product-label' => $label,
      ])
    );
  }

  /**
   * Hand over to the license type plugin configured in the product variation,
   * to let it determine whether the user already has what the license would
   * grant. Adds a notPurchasableMessage status message to queue.
   *
   * @param PurchasableEntityInterface $entity
   *   The purchased entity
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user the license would be granted to
   * @param bool $unsetNotPurchasableMessage
   *   Whether to display a notPurchasableMessage message or not
   *
   * @return mixed
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function checkPurchasable($entity, $user, $unsetNotPurchasableMessage) {
    $license_type_plugin = $entity->get('license_type')->first()->getTargetInstance();
    $existing_rights_result = $license_type_plugin->checkUserHasExistingRights($user);

    if ($existing_rights_result->hasExistingRights()) {
      // Show a message that includes the reason from the rights check.
      if ($user->id() == $this->currentUser->id()) {
        $rights_check_message = $existing_rights_result->getOwnerUserMessage();
      }
      else {
        $rights_check_message = $existing_rights_result->getOtherUserMessage();
      }

      if ($unsetNotPurchasableMessage === FALSE) {
        \Drupal::messenger()->addWarning(
          $rights_check_message . ' ' . t("You may not purchase the @product-label product.", [
            '@product-label' => $entity->label(),
          ])
        );
      }
      return FALSE;
    }
    // No opinion: return NULL.
  }

}
