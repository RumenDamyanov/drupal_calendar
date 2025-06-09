<?php

namespace Drupal\Tests\drupal_calendar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests event creation and listing in the calendar.
 *
 * @group drupal_calendar
 */
class CalendarEventCreationTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupal_calendar'];

  /**
   * Test event creation form and event listing.
   */
  public function testEventCreationAndListing() {
    // Login as admin.
    $admin = $this->drupalCreateUser(['create drupal calendar events', 'view drupal calendar events', 'administer site configuration']);
    $this->drupalLogin($admin);

    // Go to add event form (internal path).
    $this->drupalGet('/admin/content/drupal-calendar/events/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Event Title');
    $this->assertSession()->fieldExists('Event Date');
    $this->assertSession()->addressEquals('/admin/content/drupal-calendar/events/add');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');

    // Submit the form.
    $edit = [
      'title' => 'Test Event',
      'date[0][value][date]' => date('Y-m-d', strtotime('+1 day')),
      'date[0][value][time]' => '12:00:00',
      'description' => 'Test event description.',
    ];
    $this->submitForm($edit, 'Save Event');
    $this->assertSession()->pageTextContains('Event created');
    $this->assertSession()->addressEquals('/admin/content/drupal-calendar/events');

    // Check event appears in listing.
    $this->drupalGet('/admin/content/drupal-calendar/events');
    $this->assertSession()->pageTextContains('Test Event');
    $this->assertSession()->pageTextContains('Test event description.');
    $this->assertSession()->addressEquals('/admin/content/drupal-calendar/events');
  }
}
