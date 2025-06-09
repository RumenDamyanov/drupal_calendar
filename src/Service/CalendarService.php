<?php
/**
 * @file
 * Service wrapper for php-calendar integration.
 */

namespace Drupal\drupal_calendar\Service;

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
    // Use rumenx/php-calendar if it provides a class or function for ICS generation.
    // If not, fallback to the simple ICS generator as before.
    if (class_exists('Rumenx\PhpCalendar\IcsGenerator')) {
      $ics = (new \Rumenx\PhpCalendar\IcsGenerator())->generate($event);
      if (is_string($ics)) {
        return $ics;
      }
    }
    // Fallback: simple ICS generator
    $dtstamp = gmdate('Ymd\THis\Z');
    $dtstart = gmdate('Ymd\THis\Z', strtotime($event['date']));
    $uid = uniqid('event-');
    $ics = "BEGIN:VCALENDAR\r\n" .
           "VERSION:2.0\r\n" .
           "PRODID:-//Drupal Calendar//EN\r\n" .
           "BEGIN:VEVENT\r\n" .
           "UID:$uid\r\n" .
           "DTSTAMP:$dtstamp\r\n" .
           "DTSTART:$dtstart\r\n" .
           "SUMMARY:" . addcslashes($event['title'], ",;\\") . "\r\n" .
           "DESCRIPTION:" . addcslashes($event['description'], ",;\\") . "\r\n" .
           "END:VEVENT\r\n" .
           "END:VCALENDAR\r\n";
    return $ics;
  }
}
