<?php

namespace Drupal\calendar_plus\Service;

use Rumenx\PhpCalendar\IcsGenerator;

/**
 * Service for generating ICS files for calendar events.
 *
 * Integrates with rumenx/php-calendar if available, otherwise provides a
 * fallback ICS generator.
 */
class CalendarService {

  /**
   * Generate a simple ICS file for a given event.
   *
   * @param array $event
   *   Event data (title, date, description).
   *
   * @return string
   *   The ICS file content.
   */
  public function generateIcs(array $event) {
    // If needed use rumenx/php-calendar for ICS generation,
    // or fallback to the simple ICS generator as before.
    if (class_exists('Rumenx\PhpCalendar\IcsGenerator')) {
      $ics = (new IcsGenerator())->generate($event);
      if (is_string($ics)) {
        return $ics;
      }
    }
    // Fallback: simple ICS generator.
    $dtstamp = gmdate('Ymd\THis\Z');
    $date = $event['date'] ?? '';
    $title = $event['title'] ?? '';
    $description = $event['description'] ?? '';
    $dtstart = '';
    if ($date !== '' && is_string($date)) {
      $timestamp = strtotime($date);
      $dtstart = gmdate(
        'Ymd\THis\Z',
        $timestamp !== FALSE ? $timestamp : 0
      );
    }
    else {
      $dtstart = gmdate('Ymd\THis\Z', 0);
    }
    $uid = uniqid('event-');
    $ics = "BEGIN:VCALENDAR\r\n" .
           "VERSION:2.0\r\n" .
           "PRODID:-//Drupal Calendar//EN\r\n" .
           "BEGIN:VEVENT\r\n" .
           "UID:$uid\r\n" .
           "DTSTAMP:$dtstamp\r\n" .
           "DTSTART:$dtstart\r\n" .
           "SUMMARY:" . addcslashes((string) $title, ",;\\") . "\r\n" .
           "DESCRIPTION:" . addcslashes((string) $description, ",;\\") . "\r\n" .
           "END:VEVENT\r\n" .
           "END:VCALENDAR\r\n";
    return $ics;
  }

}
