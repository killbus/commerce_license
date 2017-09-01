<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseExpiration;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an expiration based on a rolling interval from the start date.
 *
 * @CommerceLicenseExpiration(
 *   id = "interval",
 *   label = @Translation("Interval"),
 *   description = @Translation("Provide expiration selection using an interval"),
 * )
 */
class Interval extends LicenseExpirationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The interval configuration.
      'interval' => [
        // The interval period. This is the ID of an interval plugin, for
        // example 'month'.
        'period' => '',
        // The interval. This is a value which multiplies the period.
        'interval' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['interval'] = [
      '#type' => 'interval',
      '#title' => 'Interval',
      '#default_value' => $config['interval'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $this->configuration['interval'] = $values['interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDate($start) {
    // Get our interval values from our configuration.
    $config = $this->getConfiguration();
    $interval_configuration = $config['interval'];
    // The interval plugin ID is the 'period' value.
    $interval_plugin_id = $interval_configuration['period'];

    // Create a DateInterval that represents the interval.
    // TODO: This can be removed when https://www.drupal.org/node/2900435 lands.
    $interval_plugin_definition = \Drupal::service('plugin.manager.interval.intervals')->getDefinition($interval_plugin_id);
    $value = $interval_configuration['interval'] * $interval_plugin_definition['multiplier'];
    $date_interval = \DateInterval::createFromDateString($value . ' ' . $interval_plugin_definition['php']);

    // Apply the interval to the start date.
    // All date calculations are done in UTC.
    $start_date_time = new \DateTime('@' . $start);
    $end_date_time = $start_date_time->add($date_interval);

    return $end_date_time->getTimestamp();
  }

}
