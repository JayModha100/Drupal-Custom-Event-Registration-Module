<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class EventRegistrationAdminForm extends FormBase {

  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database')
    );
  }

  public function getFormId(): string {
    return 'event_registration_admin_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {

    $selected_category = $form_state->getValue('category');
    $selected_date = $form_state->getValue('event_date');
    $selected_event_id = $form_state->getValue('event_id');

    /* ---------- CATEGORY FILTER ---------- */
    $categories = $this->database->select('event_registration_event', 'e')
      ->fields('e', ['category'])
      ->distinct()
      ->execute()
      ->fetchCol();

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => ['' => '- All -'] + array_combine($categories, $categories),
      '#ajax' => [
        'callback' => '::updateDates',
        'wrapper' => 'event-date-wrapper',
      ],
    ];

    /* ---------- EVENT DATE FILTER ---------- */
    $form['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-date-wrapper'],
    ];

    $dates = [];
    if ($selected_category) {
      $dates = $this->database->select('event_registration_event', 'e')
        ->fields('e', ['event_date'])
        ->condition('category', $selected_category)
        ->distinct()
        ->execute()
        ->fetchCol();
    }

    $form['event_date_wrapper']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => ['' => '- All -'] + array_combine(
        $dates,
        array_map(fn($ts) => date('Y-m-d', $ts), $dates)
      ),
      '#ajax' => [
        'callback' => '::updateEventNames',
        'wrapper' => 'event-name-wrapper',
      ],
    ];

    /* ---------- EVENT NAME FILTER ---------- */
    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $event_options = [];
    if ($selected_category && $selected_date) {
      $events = $this->database->select('event_registration_event', 'e')
        ->fields('e', ['id', 'event_name'])
        ->condition('category', $selected_category)
        ->condition('event_date', $selected_date)
        ->execute()
        ->fetchAll();

      foreach ($events as $event) {
        $event_options[$event->id] = $event->event_name;
      }
    }

    $form['event_name_wrapper']['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => ['' => '- All -'] + $event_options,
      '#ajax' => [
        'callback' => '::updateTable',
        'wrapper' => 'registration-table-wrapper',
      ],
    ];

    /* ---------- RESULTS TABLE ---------- */
    $form['registration_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'registration-table-wrapper'],
    ];

    $table = $this->buildTable($form_state);

    $form['registration_table_wrapper']['total'] = [
      '#markup' => $this->t(
        '<strong>Total participants:</strong> @count',
        ['@count' => count($table['#rows'])]
      ),
    ];

    $form['registration_table_wrapper']['table'] = $table;

    /* ---------- EXPORT ACTION ---------- */
    $form['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export CSV'),
      '#submit' => ['::exportCsv'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  protected function buildTable(FormStateInterface $form_state): array {

    $db_query = $this->database->select('event_registration_submission', 's')
      ->fields('s');

    $category = $form_state->getValue('category');
    $event_date = $form_state->getValue('event_date');
    $event_id = $form_state->getValue('event_id');

    if ($category) {
      $db_query->condition('s.category', $category);
    }
    if ($event_date) {
      $db_query->condition('s.event_date', $event_date);
    }
    if ($event_id) {
      $db_query->condition('s.event_id', $event_id);
    }

    $results = $db_query->execute()->fetchAll();
    $rows = [];

    foreach ($results as $r) {
      $rows[] = [
        $r->full_name,
        $r->email,
        date('Y-m-d', $r->event_date),
        $r->college,
        $r->department,
        date('Y-m-d H:i:s', $r->created),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Event Date'),
        $this->t('College Name'),
        $this->t('Department'),
        $this->t('Submission Date'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found.'),
    ];
  }

  public function updateDates(array &$form, FormStateInterface $form_state) {
    return $form['event_date_wrapper'];
  }

  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event-name-wrapper'] ?? $form['event_name_wrapper'];
  }

  public function updateTable(array &$form, FormStateInterface $form_state) {
    return $form['registration_table_wrapper'];
  }

  public function exportCsv(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getUserInput();

    $query = $this->database->select('event_registration_submission', 's')
      ->fields('s');

    if (!empty($values['category'])) {
      $query->condition('s.category', $values['category']);
    }
    if (!empty($values['event_date'])) {
      $query->condition('s.event_date', $values['event_date']);
    }
    if (!empty($values['event_id'])) {
      $query->condition('s.event_id', $values['event_id']);
    }

    $results = $query->execute()->fetchAll();

    $handle = fopen('php://temp', 'w+');

    fputcsv($handle, [
      'Full Name',
      'Email',
      'Event Date',
      'College',
      'Department',
      'Submission Date',
    ]);

    foreach ($results as $r) {
      fputcsv($handle, [
        $r->full_name,
        $r->email,
        date('Y-m-d', $r->event_date),
        $r->college,
        $r->department,
        date('Y-m-d H:i:s', $r->created),
      ]);
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set(
      'Content-Disposition',
      'attachment; filename="registrations.csv"'
    );

    $response->send();
    exit;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
