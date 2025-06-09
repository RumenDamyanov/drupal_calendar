<?php
namespace Drupal\drupal_calendar\Controller;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once DRUPAL_ROOT . '/vendor/autoload.php';

/**
 * Calendar event controller for drupal-calendar.
 */
class CalendarEventController {
  protected $calendarService;

  /**
   * Constructor.
   * @param $calendarService \Drupal\drupal_calendar\Service\CalendarService
   */
  public function __construct($calendarService) {
    $this->calendarService = $calendarService;
  }

  /**
   * Factory method for dependency injection.
   * @param $container \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public static function create($container) {
    return new static(
      $container->get('drupal_calendar.calendar_service')
    );
  }

  public function listEvents() {
    // Fallback: Use static property for demo/testing if \Drupal is not available.
    static $static_events = [];
    $events = $static_events;
    if (function_exists('drupal_container') && drupal_container()->has('state')) {
      $events = drupal_container()->get('state')->get('drupal_calendar.events', []);
    }
    $header = [
      'Title',
      'Date',
      'Description',
      'Created',
      'ICS',
      'RSVPs',
      'Entity',
    ];
    $rows = [];
    foreach ($events as $index => $event) {
      $ics_url = '/admin/content/drupal-calendar/events/' . $index . '/ics';
      $rsvp_summary = '';
      if (!empty($event['rsvp_enabled']) && !empty($event['rsvps'])) {
        $yes = 0;
        $no = 0;
        $pending = 0;
        $total = count($event['attendees'] ?? []);
        foreach ($event['attendees'] ?? [] as $attendee) {
          if (isset($event['rsvps'][$attendee])) {
            if ($event['rsvps'][$attendee] === 'yes') {
              $yes++;
            } elseif ($event['rsvps'][$attendee] === 'no') {
              $no++;
            }
          } else {
            $pending++;
          }
        }
        $rsvp_summary = "Yes: $yes, No: $no, Pending: $pending";
      } elseif (!empty($event['rsvp_enabled'])) {
        $rsvp_summary = 'No responses yet.';
      } else {
        $rsvp_summary = '-';
      }
      $rows[] = [
        $event['title'],
        $event['date'],
        $event['description'],
        date('Y-m-d H:i', $event['created']),
        [
          'data' => [
            '#type' => 'link',
            '#title' => 'Download ICS',
            '#url' => $ics_url,
            '#attributes' => ['target' => '_blank'],
          ],
        ],
        $rsvp_summary,
        $event['entity_type'] ?? '-',
        $event['entity_id'] ?? '-',
      ];
    }
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No events found.',
    ];
  }

  /**
   * Download ICS file for an event.
   *
   * @param int $event_id
   *   The event index in the log.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The ICS file response.
   */
  public function downloadIcs($event_id) {
    $events = [];
    if (class_exists('Drupal') && method_exists('Drupal', 'getContainer')) {
      $container = call_user_func(['Drupal', 'getContainer']);
      if ($container && $container->has('state')) {
        $events = $container->get('state')->get('drupal_calendar.events', []);
      }
    }
    if (!isset($events[$event_id]) || empty($events[$event_id]['ics'])) {
      return new \Symfony\Component\HttpFoundation\Response('ICS not found', 404);
    }
    $ics = $events[$event_id]['ics'];
    $response = new \Symfony\Component\HttpFoundation\Response($ics);
    $response->headers->set('Content-Type', 'text/calendar');
    $response->headers->set('Content-Disposition', 'attachment; filename="event.ics"');
    return $response;
  }

  /**
   * RSVP handler for event invitations.
   */
  public function rsvp($event_id) {
    $request = Request::createFromGlobals();
    $email = $request->query->get('email');
    $response_message = 'Invalid RSVP.';
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $container = call_user_func(['Drupal', 'getContainer']);
      $events = $container->get('state')->get('drupal_calendar.events', []);
      if (isset($events[$event_id]) && $events[$event_id]['rsvp_enabled']) {
        // Show RSVP form (Yes/No) and current status if available
        $current = isset($events[$event_id]['rsvps'][$email]) ? $events[$event_id]['rsvps'][$email] : null;
        if ($request->getMethod() === 'POST') {
          $rsvp = $request->request->get('rsvp');
          $events[$event_id]['rsvps'][$email] = $rsvp;
          $container->get('state')->set('drupal_calendar.events', $events);
          // Notify event creator if available
          if (!empty($events[$event_id]['creator_email']) && filter_var($events[$event_id]['creator_email'], FILTER_VALIDATE_EMAIL)) {
            $params = [
              'subject' => 'RSVP Update for ' . $events[$event_id]['title'],
              'body' => 'User ' . $email . ' responded: ' . $rsvp . ' to event "' . $events[$event_id]['title'] . '".',
            ];
            $container->get('plugin.manager.mail')->mail('drupal_calendar', 'rsvp_update', $events[$event_id]['creator_email'], NULL, $params);
          }
          $response_message = 'Thank you for your response: ' . htmlspecialchars($rsvp) . '. You can update your RSVP at any time.';
        } else {
          // Show current RSVP status and allow change
          $status = $current ? '<p>Your current RSVP: <b>' . htmlspecialchars($current) . '</b></p>' : '';
          return new Response('<form method="POST">' .
            $status .
            '<p>Will you attend this event?</p>' .
            '<button name="rsvp" value="yes" type="submit">Yes</button> '
            . '<button name="rsvp" value="no" type="submit">No</button>' .
            '</form>');
        }
      }
    }
    return new Response($response_message);
  }

  /**
   * Frontend calendar view with switchable simple/advanced modes.
   */
  public function calendarView() {
    $request = \Drupal::request();
    $mode = $request->query->get('view_mode', 'simple');
    $container = call_user_func(['Drupal', 'getContainer']);
    $events = $container->get('state')->get('drupal_calendar.events', []);
    $build = [];
    $build['switch'] = [
      '#markup' => '<a href="/calendar?view_mode=simple" style="' . ($mode === 'simple' ? 'font-weight:bold;' : '') . '">Simple List View</a>' .
        '<a href="/calendar?view_mode=advanced" style="margin-left:10px;' . ($mode === 'advanced' ? 'font-weight:bold;' : '') . '">Calendar Grid View</a>',
    ];
    if ($mode === 'advanced') {
      // Attach FullCalendar library
      $build['#attached']['library'][] = 'drupal-calendar/fullcalendar';
      // Prepare events for JS
      $calendar_events = [];
      foreach ($events as $index => $event) {
        // Handle recurring events: expand into multiple dates for display
        $dates = [$event['date']];
        if (!empty($event['recurring']) && $event['recurring_count'] > 1 && in_array($event['recurring_rule'], ['daily','weekly','monthly'])) {
          $base = strtotime($event['date']);
          for ($i = 1; $i < $event['recurring_count']; $i++) {
            if ($event['recurring_rule'] === 'daily') {
              $dates[] = date('Y-m-d\TH:i:s', strtotime("+{$i} day", $base));
            } elseif ($event['recurring_rule'] === 'weekly') {
              $dates[] = date('Y-m-d\TH:i:s', strtotime("+{$i} week", $base));
            } elseif ($event['recurring_rule'] === 'monthly') {
              $dates[] = date('Y-m-d\TH:i:s', strtotime("+{$i} month", $base));
            }
          }
        }
        foreach ($dates as $date) {
          $calendar_events[] = [
            'id' => $index,
            'title' => $event['title'],
            'start' => $date,
            'description' => $event['description'],
            'rsvp_enabled' => !empty($event['rsvp_enabled']),
            'entity_type' => $event['entity_type'] ?? '',
            'entity_id' => $event['entity_id'] ?? '',
          ];
        }
      }
      $build['#attached']['drupalSettings']['drupalCalendarEvents'] = $calendar_events;
      $build['calendar'] = [
        '#markup' => '<div id="calendar-advanced" style="max-width:900px;margin:40px auto;"></div>' .
          '<div id="calendar-event-modal" style="display:none;position:fixed;top:20%;left:50%;transform:translate(-50%,0);background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,0.2);z-index:10001;max-width:400px;"></div>' .
          '<div id="calendar-modal-backdrop" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:10000;"></div>',
      ];
      $build['#attached']['html_head'][] = [
        [
          '#tag' => 'script',
          '#value' => "document.addEventListener('DOMContentLoaded', function() {\n  if (window.FullCalendar) {\n    var calendarEl = document.getElementById('calendar-advanced');\n    var modal = document.getElementById('calendar-event-modal');\n    var backdrop = document.getElementById('calendar-modal-backdrop');\n    var calendar = new FullCalendar.Calendar(calendarEl, {\n      initialView: 'dayGridMonth',\n      events: window.drupalSettings.drupalCalendarEvents,\n      eventClick: function(info) {\n        var e = info.event;\n        var rsvpStatus = '';\n        if (e.extendedProps.rsvp_enabled && e.extendedProps.rsvps) {\n          var yes = 0, no = 0, pending = 0;\n          var attendees = e.extendedProps.attendees || [];\n          attendees.forEach(function(email) {\n            if (e.extendedProps.rsvps[email] === 'yes') yes++;\n            else if (e.extendedProps.rsvps[email] === 'no') no++;\n            else pending++;\n          });\n          rsvpStatus = '<p><b>RSVPs:</b> Yes: ' + yes + ', No: ' + no + ', Pending: ' + pending + '</p>';\n        }\n        var html = '<h3>' + e.title + '</h3>' +\n          '<p><b>Date:</b> ' + e.start.toLocaleString() + '</p>' +\n          (e.extendedProps.description ? '<p>' + e.extendedProps.description + '</p>' : '');\n        if (e.extendedProps.entity_type && e.extendedProps.entity_id) {\n          html += '<p><b>Entity:</b> ' + e.extendedProps.entity_type + ' #' + e.extendedProps.entity_id + '</p>';\n        }\n        html += rsvpStatus;\n        if (e.extendedProps.rsvp_enabled) {\n          html += '<p><a href=\"/admin/content/drupal-calendar/events/' + e.id + '/rsvp\" target=\"_blank\">RSVP to this event</a></p>'\n        }\n        html += '<button onclick=\"document.getElementById(\\'calendar-event-modal\\').style.display=\\'none\\';document.getElementById(\\'calendar-modal-backdrop\\').style.display=\\'none\\';\">Close</button>'\n        modal.innerHTML = html;\n        modal.style.display = 'block';\n        backdrop.style.display = 'block';\n      }\n    });\n    calendar.render();\n    backdrop.onclick = function() {\n      modal.style.display = 'none';\n      backdrop.style.display = 'none';\n    };\n  }\n});",
          '#attributes' => [],
        ],
        'drupal-calendar-fullcalendar-enhanced',
      ];
    } else {
      // Simple list view
      $rows = [];
      foreach ($events as $event) {
        $rows[] = [
          $event['title'],
          $event['date'],
          $event['description'],
        ];
      }
      $build['calendar'] = [
        '#type' => 'table',
        '#header' => ['Title', 'Date', 'Description'],
        '#rows' => $rows,
        '#empty' => 'No events found.',
      ];
    }
    return $build;
  }

  /**
   * Display the event/action logs.
   */
  public function listLogs() {
    $logs = [];
    if (class_exists('Drupal') && method_exists('Drupal', 'getContainer')) {
      $container = call_user_func(['Drupal', 'getContainer']);
      if ($container->has('state')) {
        $logs = $container->get('state')->get('drupal_calendar.logs', []);
      }
    }
    $header = ['Time', 'Type', 'Message', 'Context'];
    $rows = [];
    foreach (array_reverse($logs) as $log) {
      $rows[] = [
        date('Y-m-d H:i:s', $log['timestamp']),
        $log['type'],
        $log['message'],
        json_encode($log['context']),
      ];
    }
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No logs found.',
    ];
  }

  /**
   * REST API: List all events as JSON.
   */
  public function apiEvents() {
    try {
      $container = call_user_func(['Drupal', 'getContainer']);
      $events = $container->get('state')->get('drupal_calendar.events', []);
      return new \Symfony\Component\HttpFoundation\JsonResponse(array_values($events));
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'error' => 'Internal server error',
        'details' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * REST API: Get a single event by ID as JSON.
   */
  public function apiEvent($event_id) {
    try {
      $container = call_user_func(['Drupal', 'getContainer']);
      $events = $container->get('state')->get('drupal_calendar.events', []);
      if (!isset($events[$event_id])) {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
          'error' => 'Event not found',
          'event_id' => $event_id,
        ], 404);
      }
      return new \Symfony\Component\HttpFoundation\JsonResponse($events[$event_id]);
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'error' => 'Internal server error',
        'details' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * REST API: Get RSVPs for an event as JSON.
   */
  public function apiRsvps($event_id) {
    try {
      $container = call_user_func(['Drupal', 'getContainer']);
      $events = $container->get('state')->get('drupal_calendar.events', []);
      if (!isset($events[$event_id])) {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
          'error' => 'Event not found',
          'event_id' => $event_id,
        ], 404);
      }
      if (empty($events[$event_id]['rsvps'])) {
        return new \Symfony\Component\HttpFoundation\JsonResponse([
          'error' => 'No RSVPs found for this event',
          'event_id' => $event_id,
        ], 404);
      }
      return new \Symfony\Component\HttpFoundation\JsonResponse($events[$event_id]['rsvps']);
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'error' => 'Internal server error',
        'details' => $e->getMessage(),
      ], 500);
    }
  }

}
