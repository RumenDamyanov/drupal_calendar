<?php

namespace Drupal\calendar_plus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Upcoming Events' block for the Drupal Calendar module.
 *
 * Displays a table of the next five upcoming events.
 *
 * @Block(
 *   id = "calendar_plus_upcoming_block",
 *   admin_label = @Translation("Upcoming Events"),
 *   category = @Translation("Calendar")
 * )
 */
class UpcomingEventsBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $events = [];
    if (class_exists('Drupal') && method_exists('Drupal', 'getContainer')) {
      $container = \Drupal::getContainer();
      if ($container->has('state')) {
        $events = $container->get('state')->get('calendar_plus.events', []);
      }
    }
    $now = time();
    $upcoming = [];
    foreach ($events as $event) {
      $date = strtotime($event['date']);
      if ($date && $date > $now) {
        $upcoming[] = $event;
      }
    }
    usort($upcoming, function ($a, $b) {
      return strtotime($a['date']) - strtotime($b['date']);
    });
    $rows = [];
    foreach (array_slice($upcoming, 0, 5) as $event) {
      $rows[] = [
        $event['title'],
        $event['date'],
      ];
    }
    return [
      '#type' => 'table',
      '#header' => ['Title', 'Date'],
      '#rows' => $rows,
      '#empty' => 'No upcoming events.',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   Returns an instance of the block plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
