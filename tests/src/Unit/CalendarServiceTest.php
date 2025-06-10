<?php
namespace Drupal\Tests\drupal_calendar\Unit;

use Drupal\drupal_calendar\Service\CalendarService;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CalendarService ICS generation.
 *
 * @group drupal_calendar
 */
class CalendarServiceTest extends TestCase {
  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   * This test covers the library code path by mocking IcsGenerator.
   */
  public function testGenerateIcsWithPhpCalendarIcsGeneratorReturnsString() {
    // Always define the mock in a separate process.
    if (!class_exists('Rumenx\\PhpCalendar\\IcsGenerator', false)) {
      eval('namespace Rumenx\\PhpCalendar; class IcsGenerator { public function generate($event) { return "ICS_FROM_LIBRARY"; } }');
    }
    $service = new \Drupal\drupal_calendar\Service\CalendarService();
    $event = [ 'title' => 'Lib Event', 'date' => '2025-06-20T10:00:00', 'description' => 'From library.' ];
    $ics = $service->generateIcs($event);
    $this->assertSame('ICS_FROM_LIBRARY', $ics);
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   * This test covers the library code path returning non-string.
   */
  public function testGenerateIcsWithPhpCalendarIcsGeneratorReturnsNonString() {
    if (!class_exists('Rumenx\\PhpCalendar\\IcsGenerator', false)) {
      eval('namespace Rumenx\\PhpCalendar; class IcsGenerator { public function generate($event) { return null; } }');
    }
    $service = new \Drupal\drupal_calendar\Service\CalendarService();
    $event = [ 'title' => 'Lib Event', 'date' => '2025-06-20T10:00:00', 'description' => 'From library.' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
    $this->assertStringContainsString('SUMMARY:Lib Event', $ics);
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   * This test ensures fallback code path is covered even if the library is present.
   * It is always run, regardless of the environment, to guarantee fallback coverage.
   */
  public function testFallbackCodePathAlwaysCovered() {
    // Do not check for the class; in a separate process, fallback will be used.
    $service = new \Drupal\drupal_calendar\Service\CalendarService();
    $event = [
      'title' => 'Fallback Coverage',
      'date' => '2025-06-30T12:00:00',
      'description' => 'Ensure fallback is covered.',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Fallback Coverage', $ics);
    $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
  }

  public function testGenerateIcsReturnsString() {
    $service = new CalendarService();
    $event = [
      'title' => 'Unit Test Event',
      'date' => '2025-06-10T12:00:00',
      'description' => 'Unit test description.',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
    $this->assertStringContainsString('SUMMARY:Unit Test Event', $ics);
  }

  public function testGenerateIcsWithFallback() {
    $service = new CalendarService();
    $event = [
      'title' => 'Fallback Event',
      'date' => '2025-06-11T15:30:00',
      'description' => 'Fallback description.',
    ];
    $expected = gmdate('Ymd\THis\Z', strtotime($event['date']));
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('BEGIN:VEVENT', $ics);
    $this->assertStringContainsString('SUMMARY:Fallback Event', $ics);
    $this->assertStringContainsString('DESCRIPTION:Fallback description.', $ics);
    $this->assertStringContainsString("DTSTART:$expected", $ics);
  }

  public function testGenerateIcsMissingFields() {
    $service = new CalendarService();
    $event = [
      'title' => '',
      'date' => '',
      'description' => '',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
  }

  public function testGenerateIcsWithSpecialCharacters() {
    $service = new CalendarService();
    $event = [
      'title' => 'Title, with; special\\chars',
      'date' => '2025-06-13T18:45:00',
      'description' => 'Desc; with, special\\chars',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Title\\, with\\; special\\\\chars', $ics);
    $this->assertStringContainsString('DESCRIPTION:Desc\\; with\\, special\\\\chars', $ics);
  }

  public function testGenerateIcsWithMinimalEvent() {
    $service = new CalendarService();
    $event = [
      'title' => 'Minimal',
      'date' => '2025-06-15T00:00:00',
      'description' => '',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Minimal', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
  }

  public function testGenerateIcsWithNoEventFields() {
    $service = new CalendarService();
    $event = [];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
  }

  public function testGenerateIcsWithInvalidDate() {
    $service = new CalendarService();
    $event = [
      'title' => 'Invalid Date Event',
      'date' => 'not-a-date',
      'description' => 'Should handle invalid date.',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    // DTSTART should fallback to epoch if strtotime fails.
    $this->assertMatchesRegularExpression('/DTSTART:19700101T000000Z/', $ics);
  }

  public function testGenerateIcsUidAndDtstampArePresent() {
    $service = new CalendarService();
    $event = [
      'title' => 'UID Test',
      'date' => '2025-06-14T10:00:00',
      'description' => 'UID and DTSTAMP test.',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertMatchesRegularExpression('/UID:event-[a-z0-9]+/', $ics);
    $this->assertMatchesRegularExpression('/DTSTAMP:\d{8}T\d{6}Z/', $ics);
  }

  public function testGenerateIcsWithPartialEventArray() {
    $service = new CalendarService();
    $event = [ 'title' => 'Partial Event' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Partial Event', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
    $event = [ 'date' => '2025-06-16T10:00:00' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
    $event = [ 'description' => 'Only description' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:Only description', $ics);
  }

  public function testClassIsInstantiable() {
    $service = new CalendarService();
    $this->assertInstanceOf(CalendarService::class, $service);
  }

}
