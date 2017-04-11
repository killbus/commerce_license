<?php

namespace Drupal\commerce_license\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\commerce\Event\CommerceEvents;

/**
 * Class ReferenceablePluginTypesSubscriber.
 *
 * @package Drupal\commerce_license
 */
class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      CommerceEvents::REFERENCEABLE_PLUGIN_TYPES => 'onPluginTypes',
    ];
  }

  /**
   * Registers the 'commerce_license_type' plugin type as referenceable.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $plugin_types = $event->getPluginTypes();
    $plugin_types['commerce_license_type'] = t('License type');
    $event->setPluginTypes($plugin_types);
  }

}
