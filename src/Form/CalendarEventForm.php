<?php
namespace Drupal\drupal_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CalendarEventForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_calendar_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Title'),
      '#required' => TRUE,
    ];
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
    ];
    $form['attendees'] = [
      '#type' => 'textarea',
      '#title' => 'Invite Users (emails, one per line)',
      '#description' => 'Enter one email address per line to invite users to this event.',
    ];
    $form['rsvp_enabled'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable RSVP for this event',
      '#default_value' => FALSE,
      '#description' => 'If checked, invited users will receive a link to RSVP (Yes/No).',
    ];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => 'Attach to Entity Type',
      '#options' => [
        '' => '- None -',
        'node' => 'Content (Node)',
        'user' => 'User',
        // Add more entity types as needed
      ],
      '#description' => 'Optionally attach this event to a Drupal entity.',
    ];
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => 'Entity ID',
      '#description' => 'Enter the ID of the entity to attach this event to.',
      '#states' => [
        'visible' => [
          ':input[name="entity_type"]' => ['!value' => ''],
        ],
      ],
    ];
    $form['recurring'] = [
      '#type' => 'checkbox',
      '#title' => 'Recurring Event',
      '#description' => 'Check if this event should repeat.',
      '#default_value' => FALSE,
    ];
    $form['recurring_rule'] = [
      '#type' => 'select',
      '#title' => 'Recurrence Rule',
      '#options' => [
        '' => '- None -',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
      ],
      '#states' => [
        'visible' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => 'How often should this event repeat?',
    ];
    $form['recurring_count'] = [
      '#type' => 'number',
      '#title' => 'Repeat Count',
      '#default_value' => 1,
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => 'How many times should this event repeat?',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save Event',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = [
      'title' => $form_state->getValue('title'),
      'date' => $form_state->getValue('date'),
      'description' => $form_state->getValue('description'),
      'attendees' => array_filter(array_map('trim', explode("\n", $form_state->getValue('attendees')))),
      'rsvp_enabled' => $form_state->getValue('rsvp_enabled'),
      'entity_type' => $form_state->getValue('entity_type'),
      'entity_id' => $form_state->getValue('entity_id'),
      'recurring' => $form_state->getValue('recurring'),
      'recurring_rule' => $form_state->getValue('recurring_rule'),
      'recurring_count' => $form_state->getValue('recurring_count'),
      'rsvps' => [],
    ];
    // Generate ICS file content using the service.
    $ics = \Drupal::service('drupal_calendar.calendar_service')->generateIcs($event);
    // Get admin setting for ICS storage.
    $ics_storage = \Drupal::config('drupal_calendar.settings')->get('ics_storage') ?: 'entity';
    // Store event log (for now, use state API; later, use entity or custom table)
    $events = \Drupal::state()->get('drupal_calendar.events', []);
    $event['ics'] = $ics;
    $event['created'] = time();
    // Store creator email when event is created (if available)
    $current_user = \Drupal::currentUser();
    $event['creator_email'] = $current_user->getEmail() ?: \Drupal::config('system.site')->get('mail');
    // Handle ICS storage
    if ($ics_storage === 'static') {
      // Save ICS as static file in public://drupal-calendar/
      $directory = 'public://drupal-calendar';
      \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
      $filename = $directory . '/event_' . time() . '.ics';
      file_put_contents(\Drupal::service('file_system')->realpath($filename), $ics);
      $event['ics_file'] = $filename;
    } elseif ($ics_storage === 'email') {
      // Send ICS as email attachment to site mail
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'drupal_calendar';
      $key = 'event_ics';
      $to = \Drupal::config('system.site')->get('mail');
      $params['subject'] = 'New Event: ' . $event['title'];
      $params['body'] = 'See attached ICS file.';
      $params['attachments'][] = [
        'filecontent' => $ics,
        'filename' => 'event.ics',
        'filemime' => 'text/calendar',
      ];
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = true;
      $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      // Send invitations if attendees are provided and email mode is enabled.
      if ($ics_storage === 'email' && !empty($event['attendees'])) {
        // Get admin-configured email subject/body and replace tokens.
        $config = \Drupal::config('drupal_calendar.settings');
        $email_subject = $config->get('email_subject') ?: 'Invitation: [event_title]';
        $email_body = $config->get('email_body') ?: "You are invited to [event_title] on [event_date].\n[event_description]";

        foreach ($event['attendees'] as $email) {
          if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $tokens = [
              '[event_title]' => $event['title'],
              '[event_date]' => $event['date'],
              '[event_description]' => $event['description'],
              '[rsvp_url]' => ($event['rsvp_enabled'] ? (\Drupal::request()->getSchemeAndHttpHost() . '/admin/content/drupal-calendar/events/' . count($events) . '/rsvp?email=' . urlencode($email)) : ''),
            ];
            $subject = strtr($email_subject, $tokens);
            $body = strtr($email_body, $tokens);
            $params['subject'] = $subject;
            $params['body'] = $body;
            $params['attachments'][] = [
              'filecontent' => $ics,
              'filename' => 'event.ics',
              'filemime' => 'text/calendar',
            ];
            $mailManager->mail($module, $key, $email, $langcode, $params, NULL, $send);
          }
        }
      }
    }
    $events[] = $event;
    \Drupal::state()->set('drupal_calendar.events', $events);
    // Display message
    \Drupal::messenger()->addMessage('Event created and ICS generated.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('title'))) {
      $form_state->setErrorByName('title', 'Event title is required.');
    }
    if (empty($form_state->getValue('date'))) {
      $form_state->setErrorByName('date', 'Event date is required.');
    } elseif (strtotime($form_state->getValue('date')) < time()) {
      $form_state->setErrorByName('date', 'Event date must be in the future.');
    }
    $attendees = array_filter(array_map('trim', explode("\n", $form_state->getValue('attendees'))));
    foreach ($attendees as $email) {
      if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('attendees', 'Invalid email address: ' . $email);
      }
    }
    if ($form_state->getValue('entity_type') && empty($form_state->getValue('entity_id'))) {
      $form_state->setErrorByName('entity_id', 'Entity ID is required if entity type is selected.');
    }
  }

}
