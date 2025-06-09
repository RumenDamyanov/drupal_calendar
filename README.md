# Drupal Calendar Module

[![CI](https://github.com/RumenDamyanov/drupal-calendar/actions/workflows/ci.yml/badge.svg)](https://github.com/rumenx/drupal-calendar/actions/workflows/ci.yml)

A modern, feature-rich calendar module for Drupal 11+ that supports event creation, ICS file generation, invitations, RSVP, recurring events, admin logging, REST API, and more.

## Features

- Create and manage custom events
- Generate ICS files for events (attach to entity, static file, or email)
- Admin panel for event management, settings, and logs
- User invitations and RSVP tracking
- Recurring events (daily, weekly, monthly)
- Frontend calendar (simple list and FullCalendar grid)
- Upcoming events block
- REST API for events and RSVPs
- Granular permissions and access control
- Email reminders and notifications
- Logging and audit trail
- Unit and functional tests

## Installation

1. Place the module in your `modules/custom` directory.
2. Run `composer install` to install dependencies (rumenx/php-calendar).
3. Enable the module via Drupal admin or Drush.

## Configuration

- Visit `/admin/config/drupal-calendar` to configure settings (ICS handling, email templates, reminders, etc).
- Place the "Upcoming Events" block as needed.
- Access the admin event list at `/admin/content/drupal-calendar/events`.

## REST API

- List events: `GET /api/drupal-calendar/events?_format=json`
- Get event: `GET /api/drupal-calendar/events/{event_id}?_format=json`
- Get RSVPs: `GET /api/drupal-calendar/events/{event_id}/rsvps?_format=json`

## Permissions

- Create, view, and manage events and invitations via Drupal permissions.

## Testing

- **Unit tests:** Run with Composer-installed PHPUnit:

  - `vendor/bin/phpunit` (runs only unit tests by default)
  - `vendor/bin/phpunit --testsuite Unit` (explicitly runs unit tests)

- **Functional tests:** Must be run inside a full Drupal site:

  - With Drush: `drush test:run drupal_calendar`
  - Or: `php core/scripts/run-tests.sh --module drupal_calendar`

Unit tests do not require a Drupal site. Functional tests require a working Drupal environment and will fail if run with plain PHPUnit.

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
