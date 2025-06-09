<?php
namespace Drupal\drupal_calendar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Upcoming Events' block.
 *
 * @Block(
 *   id = "drupal_calendar_upcoming_block",
 *   admin_label = @Translation("Upcoming Events"),
 *   category = @Translation("Calendar")
 * )
 */
class UpcomingEventsBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {
  public function build() {
    $events = [];
    if (class_exists('Drupal') && method_exists('Drupal', 'getContainer')) {
      $container = \Drupal::getContainer();
      if ($container->has('state')) {
        $events = $container->get('state')->get('drupal_calendar.events', []);
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
    usort($upcoming, function($a, $b) {
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }
}
