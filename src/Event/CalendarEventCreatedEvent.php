<?php

namespace Drupal\drupal_calendar\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is dispatched when a calendar event is created.
 *
 * Allows other modules to react to the creation of a new calendar event.
 */
class CalendarEventCreatedEvent extends Event {
  /**
   * The event name for calendar event creation.
   */
  const EVENT_NAME = 'drupal_calendar.event_created';

  /**
   * The event data array.
   *
   * @var array
   */
  protected $eventData;

  /**
   * Constructs a new CalendarEventCreatedEvent object.
   *
   * @param array $eventData
   *   The event data array.
   */
  public function __construct(array $eventData) {
    $this->eventData = $eventData;
  }

  /**
   * Gets the event data array.
   *
   * @return array
   *   The event data array.
   */
  public function getEventData() {
    return $this->eventData;
  }

}
