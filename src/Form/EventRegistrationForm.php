<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\event_registration\Service\EventRegistrationService;
use Drupal\Core\Mail\MailManagerInterface;

class EventRegistrationForm extends FormBase {

  protected EventRegistrationService $eventService;
  protected MailManagerInterface $mailManager;

  public function __construct(
    EventRegistrationService $eventService,
    MailManagerInterface $mailManager
  ) {
    $this->eventService = $eventService;
    $this->mailManager = $mailManager;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('event_registration.service'),
      $container->get('plugin.manager.mail')
    );
  }

  public function getFormId(): string {
    return 'event_registration_user_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $events = $this->eventService->getOpenEvents();

    if (empty($events)) {
      $form['message'] = [
        '#markup' => $this->t('No events are currently open for registration.'),
      ];
      return $form;
    }

    /* ---------------- USER FIELDS ---------------- */
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['college'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
    ];

    /* ---------------- AJAX DROPDOWNS ---------------- */
    $categories = [];
    foreach ($events as $event) {
      $categories[$event->category] = $event->category;
    }

    $selected_category = $form_state->getValue('category');

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $categories,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventDates',
        'wrapper' => 'event-date-wrapper',
      ],
    ];

    // Event Date
    $form['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-date-wrapper'],
    ];

    $date_options = $selected_category
      ? $this->eventService->getEventDatesByCategory($selected_category)
      : [];

    $form['event_date_wrapper']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventNames',
        'wrapper' => 'event-name-wrapper',
      ],
    ];

    // Event Name
    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $selected_date = $form_state->getValue('event_date');

    $name_options = ($selected_category && $selected_date)
      ? $this->eventService->getEventNamesByCategoryAndDate(
          $selected_category,
          (int) $selected_date
        )
      : [];

    $form['event_name_wrapper']['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $name_options,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];

    // Submit button
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    return $form;
  }

  /* ---------------- AJAX CALLBACKS ---------------- */
  public function updateEventDates(array &$form, FormStateInterface $form_state) {
    return $form['event_date_wrapper'];
  }

  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event_name_wrapper'];
  }
  /* ---------------- FORM VALIDATION ---------------- */
public function validateForm(array &$form, FormStateInterface $form_state): void {
    $email = $form_state->getValue('email');
    $event_id = $form_state->getValue(['event_id']); // safer to use array access
    $event_id = is_numeric($event_id) ? (int) $event_id : 0;

    // 1. Prevent duplicate registration (Email + Event ID)
    if ($event_id > 0 && $this->eventService->isDuplicateRegistration($email, $event_id)) {
        $form_state->setErrorByName('event_id', $this->t('You have already registered for this event.'));
    }

    // 2. Special characters validation (allow letters, numbers, spaces only)
    $pattern = '/^[a-zA-Z0-9\s]+$/';
    $text_fields = [
        'full_name' => $this->t('Full Name'),
        'college' => $this->t('College Name'),
        'department' => $this->t('Department'),
    ];

    foreach ($text_fields as $field => $label) {
        $value = $form_state->getValue($field);
        if (!empty($value) && !preg_match($pattern, $value)) {
            $form_state->setErrorByName(
                $field,
                $this->t('@field should not contain special characters.', ['@field' => $label])
            );
        }
    }

    // 3. Email format is already checked by '#type' => 'email', but you can add extra regex if needed
}

 public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save registration
    $this->eventService->saveRegistration($values);

    // Fetch the actual event info
    $event = $this->eventService->getEventById((int) $values['event_id']);
    $event_name = $event->event_name ?? 'Unknown';
    $event_date = isset($event->event_date) ? date('Y-m-d', $event->event_date) : 'Unknown';

    // Load config
    $config = \Drupal::config('event_registration.settings');
    $admin_email = $config->get('admin_email');
    $notify_admin = $config->get('admin_notify');

    // Log what would be sent
    \Drupal::logger('event_registration')->notice(
        'User email: @email, Event: @event, Date: @date, Category: @cat',
        [
            '@email' => $values['email'],
            '@event' => $event_name,
            '@date' => $event_date,
            '@cat' => $values['category'],
        ]
    );

    if ($notify_admin && $admin_email) {
        \Drupal::logger('event_registration')->notice(
            'Admin notification would be sent to: @admin',
            ['@admin' => $admin_email]
        );
    }

    // Display confirmation message instead of sending real emails
    $this->messenger()->addStatus($this->t(
        'Registration successful! Email would be sent to @user and @admin if enabled.',
        [
            '@user' => $values['email'],
            '@admin' => $notify_admin ? $admin_email : $this->t('admin notifications disabled'),
        ]
    ));

    $form_state->setRedirect('<current>');
}



}
