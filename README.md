# Calendar Plus Module

[![CI](https://github.com/RumenDamyanov/calendar_plus/actions/workflows/ci.yml/badge.svg)](https://github.com/RumenDamyanov/calendar_plus/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/RumenDamyanov/calendar_plus/branch/master/graph/badge.svg)](https://codecov.io/gh/RumenDamyanov/calendar_plus)

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

You can install this module via Composer:

```sh
composer require drupal/calendar_plus
```

Or manually:

1. Download the module from the [Drupal.org project page](https://www.drupal.org/project/calendar_plus) or clone from the [Drupal.org GitLab repository](https://git.drupalcode.org/project/calendar_plus.git).
2. Place the module in your `modules/custom` directory.
3. Run `composer install` to install dependencies (`drupal/php-calendar`).
4. Enable the module via Drupal admin or Drush.

## Configuration

- Visit `/admin/config/calendar-plus` to configure settings (ICS handling, email templates, reminders, etc).
- Place the "Upcoming Events" block as needed.
- Access the admin event list at `/admin/content/calendar-plus/events`.

## REST API

- List events: `GET /api/calendar-plus/events?_format=json`
- Get event: `GET /api/calendar-plus/events/{event_id}?_format=json`
- Get RSVPs: `GET /api/calendar-plus/events/{event_id}/rsvps?_format=json`

## Permissions

- Create, view, and manage events and invitations via Drupal permissions.

## Testing

- **Unit tests:** Run with Composer-installed PHPUnit:

  - `vendor/bin/phpunit` (runs only unit tests by default)
  - `vendor/bin/phpunit --testsuite Unit` (explicitly runs unit tests)

- **Functional tests:** Must be run inside a full Drupal site:

  - With Drush: `drush test:run calendar_plus`
  - Or: `php core/scripts/run-tests.sh --module calendar_plus`

Unit tests do not require a Drupal site. Functional tests require a working Drupal environment and will fail if run with plain PHPUnit.

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
