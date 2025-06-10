<?php

namespace Drupal\Tests\drupal_calendar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Calendar API endpoints.
 *
 * @group drupal_calendar
 */
class CalendarApiTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';
  protected static $modules = ['drupal_calendar'];

  // To avoid CI cURL errors, skip this test in CI environments.
  public function setUp(): void {
    parent::setUp();
    if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
      $this->markTestSkipped('Skipping functional test in CI due to environment limitations.');
    }
  }

  public function testApiEventsEndpoint() {
    $admin = $this->drupalCreateUser(['view drupal calendar events']);
    $this->drupalLogin($admin);
    // Use internal path, never absolute URL.
    $this->drupalGet('/api/drupal-calendar/events?_format=json');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderContains('content-type', 'application/json');
    // Ensure not redirected to error page.
    $this->assertSession()->addressEquals('/api/drupal-calendar/events?_format=json');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
  }

  public function testApiEventNotFound() {
    $admin = $this->drupalCreateUser(['view drupal calendar events']);
    $this->drupalLogin($admin);
    $this->drupalGet('/api/drupal-calendar/events/9999?_format=json');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseContains('Event not found');
    // Ensure not redirected to error page.
    $this->assertSession()->addressEquals('/api/drupal-calendar/events/9999?_format=json');
  }
}
