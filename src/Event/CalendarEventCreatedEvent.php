<?php
namespace Drupal\drupal_calendar\Event;

use Symfony\Component\EventDispatcher\Event;

class CalendarEventCreatedEvent extends Event {
  const EVENT_NAME = 'drupal_calendar.event_created';

  protected $eventData;

  public function __construct(array $eventData) {
    $this->eventData = $eventData;
  }

  public function getEventData() {
    return $this->eventData;
  }
}
