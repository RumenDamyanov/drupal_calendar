<?php

namespace Drupal\calendar_plus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin configuration form for Drupal Calendar settings.
 *
 * Allows administrators to configure ICS storage, email templates,
 * and reminders.
 */
class CalendarAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_plus_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['calendar_plus.settings'];
  }

  /**
   * Builds the admin settings form for the calendar module.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('calendar_plus.settings');
    $form['ics_storage'] = [
      '#type' => 'select',
      '#title' => $this->t('ICS File Storage'),
      '#options' => [
        'entity' => $this->t('Attach to entity'),
        'static' => $this->t('Upload as static file'),
        'email' => $this->t('Send as email attachment'),
      ],
      '#default_value' => $config->get('ics_storage') ?: 'entity',
      '#description' => $this->t('Choose how ICS files are handled.'),
    ];
    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => 'Invitation Email Subject',
      '#default_value' => $config->get('email_subject') ?: 'Invitation: [event_title]',
      '#description' => 'You can use [event_title], [event_date], [event_description] as tokens.',
    ];
    $form['email_body'] = [
      '#type' => 'textarea',
      '#title' => 'Invitation Email Body',
      '#default_value' => $config->get('email_body') ?: "You are invited to [event_title] on [event_date].\n[event_description]",
      '#description' => 'You can use [event_title], [event_date], [event_description], [rsvp_url] as tokens.',
    ];
    $form['reminder_days'] = [
      '#type' => 'number',
      '#title' => 'Send Reminder (days before event)',
      '#default_value' => $config->get('reminder_days') ?: 1,
      '#min' => 0,
      '#description' => 'Number of days before the event to send a reminder email to attendees. Set to 0 to disable reminders.',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Handles submission of the admin settings form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('calendar_plus.settings')
      ->set('ics_storage', $form_state->getValue('ics_storage'))
      ->set('email_subject', $form_state->getValue('email_subject'))
      ->set('email_body', $form_state->getValue('email_body'))
      ->set('reminder_days', $form_state->getValue('reminder_days'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
