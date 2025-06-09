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
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('BEGIN:VEVENT', $ics);
    $this->assertStringContainsString('SUMMARY:Fallback Event', $ics);
    $this->assertStringContainsString('DESCRIPTION:Fallback description.', $ics);
    $this->assertStringContainsString('DTSTART:20250611T153000Z', $ics);
  }

  public function testGenerateIcsWithExternalLibrary() {
    if (!class_exists('Rumenx\\PhpCalendar\\IcsGenerator')) {
      $this->markTestSkipped('External ICS generator not available.');
    }
    $service = new CalendarService();
    $event = [
      'title' => 'External Event',
      'date' => '2025-06-12T09:00:00',
      'description' => 'External description.',
    ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:External Event', $ics);
    $this->assertStringContainsString('DESCRIPTION:External description.', $ics);
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
    // Check for correct escaping: commas, semicolons, and backslashes are escaped.
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
    // Only title provided
    $event = [ 'title' => 'Partial Event' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Partial Event', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
    // Only date provided
    $event = [ 'date' => '2025-06-16T10:00:00' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:', $ics);
    // Only description provided
    $event = [ 'description' => 'Only description' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:', $ics);
    $this->assertStringContainsString('DESCRIPTION:Only description', $ics);
  }

  public function testGenerateIcsExternalLibraryReturnsNonString() {
    // Dynamically define a fake IcsGenerator class in the right namespace.
    if (!class_exists('Rumenx\\PhpCalendar\\IcsGenerator')) {
      eval('namespace Rumenx\\PhpCalendar; class IcsGenerator { public function generate($event) { return ["not", "a", "string"]; } }');
    }
    $service = new CalendarService();
    $event = [ 'title' => 'Fake', 'date' => '2025-06-17T10:00:00', 'description' => 'Fake' ];
    $ics = $service->generateIcs($event);
    $this->assertIsString($ics);
    $this->assertStringContainsString('SUMMARY:Fake', $ics);
    $this->assertStringContainsString('DESCRIPTION:Fake', $ics);
  }

  public function testGenerateIcsExternalLibraryThrowsException() {
    // Dynamically define a fake IcsGenerator class that throws.
    if (!class_exists('Rumenx\\PhpCalendar\\IcsGenerator')) {
      eval('namespace Rumenx\\PhpCalendar; class IcsGenerator { public function generate($event) { throw new \\Exception("fail"); } }');
    } else {
      // If it exists, skip to avoid breaking real tests.
      $this->markTestSkipped('Real IcsGenerator exists.');
    }
    $service = new CalendarService();
    $event = [ 'title' => 'Exception', 'date' => '2025-06-18T10:00:00', 'description' => 'Exception' ];
    try {
      $ics = $service->generateIcs($event);
      $this->assertIsString($ics);
      $this->assertStringContainsString('SUMMARY:Exception', $ics);
    } catch (\Exception $e) {
      $this->fail('Exception should be caught by fallback logic.');
    }
  }

  public function testClassIsInstantiable() {
    $service = new CalendarService();
    $this->assertInstanceOf(CalendarService::class, $service);
  }

  public function testReflectionForCoverage() {
    $service = new CalendarService();
    $class = new \ReflectionClass($service);
    $this->assertSame('CalendarService', $class->getShortName());
    $this->assertTrue($class->hasMethod('generateIcs'));
    $method = $class->getMethod('generateIcs');
    $this->assertTrue($method->isPublic());
  }

}
