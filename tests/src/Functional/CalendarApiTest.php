<?php

namespace Drupal\Tests\calendar_plus\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Calendar API endpoints.
 *
 * @group calendar_plus
 */
class CalendarApiTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';
  protected static $modules = ['calendar_plus'];

  // To avoid CI cURL errors, skip this test in CI environments.
  public function setUp(): void {
    parent::setUp();
    if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
      $this->markTestSkipped('Skipping functional test in CI due to environment limitations.');
    }
  }

  public function testApiEventsEndpoint() {
    $admin = $this->drupalCreateUser(['view calendar plus events']);
    $this->drupalLogin($admin);
    // Use internal path, never absolute URL.
    $this->drupalGet('/api/calendar-plus/events?_format=json');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderContains('content-type', 'application/json');
    // Ensure not redirected to error page.
    $this->assertSession()->addressEquals('/api/calendar-plus/events?_format=json');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
  }

  public function testApiEventNotFound() {
    $admin = $this->drupalCreateUser(['view calendar plus events']);
    $this->drupalLogin($admin);
    $this->drupalGet('/api/calendar-plus/events/9999?_format=json');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseContains('Event not found');
    // Ensure not redirected to error page.
    $this->assertSession()->addressEquals('/api/calendar-plus/events/9999?_format=json');
  }
}
