<?php

namespace Drupal\commerce_license\EventSubscriber;

use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles license renewal.
 *
 * Set the already existing license in the order item.
 */
class LicenseRenewalCartEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The license storage.
   *
   * @var \Drupal\commerce_license\LicenseStorage
   */
  protected $licenseStorage;

  /**
   * Constructs a new LicenseRenewalCartEventSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    DateFormatterInterface $date_formatter
  ) {
    $this->licenseStorage = $entity_type_manager->getStorage('commerce_license');
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events = [
      CartEvents::CART_ENTITY_ADD => ['onCartEntityAdd', 100],
    ];
    return $events;
  }

  /**
   * Sets the already existing license in the order item.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The cart event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function onCartEntityAdd(CartEntityAddEvent $event) {
    $order_item = $event->getOrderItem();
    // Only act if the order item has a license reference field.
    if (!$order_item->hasField('license')) {
      return;
    }
    // We can't renew license types that don't allow us to find a license
    // given only a product variation and a user.
    $variation = $order_item->getPurchasedEntity();

    $license_type_plugin = $variation->get('license_type')->first()->getTargetInstance();
    if (!($license_type_plugin instanceof ExistingRightsFromConfigurationCheckingInterface)) {
      return;
    }
    $existing_license = $this->licenseStorage->getExistingLicense($variation, $order_item->getOrder()->getCustomerId());
    if ($existing_license && $existing_license->canRenew()) {
      $order_item->set('license', $existing_license->id());
      $order_item->save();

      $transition = $existing_license->getState()->getWorkflow()->getTransition('renewal_in_progress');
      $existing_license->getState()->applyTransition($transition);
      $existing_license->save();

      // Shows a message with existing and extended dates when order completed.
      $expiresTime = $existing_license->getExpiresTime();
      $datetime = (new \DateTimeImmutable())->setTimestamp($expiresTime);
      $extendedDatetime = $existing_license->getExpirationPlugin()->calculateEnd($datetime);

      // TODO: link here once there is user admin UI for licenses!
      \Drupal::messenger()->addStatus(
        t('You have an existing license for @product-label until @expires-time.
        This will be extended until @extended-date when you complete this order.', [
          "@product-label" => $existing_license->label(),
          "@expires-time" => \Drupal::service('date.formatter')->format($expiresTime),
          "@extended-date" => \Drupal::service('date.formatter')->format($extendedDatetime->getTimestamp()),
        ])
      );

    }

    elseif ($existing_license) {

      // This will never be fired when expected,
      // since the CART_ENTITY_ADD is not fired at this point ?
      $renewal_window_start_time = $existing_license->getRenewalWindowStartTime();

      if (!is_null($renewal_window_start_time)) {
        $this->messenger->addStatus($this->t('You have an existing license for this product. You will be able to renew your license after %date.', [
          '%date' => $this->dateFormatter->format($renewal_window_start_time),
        ]));
      }
    }
  }

}
