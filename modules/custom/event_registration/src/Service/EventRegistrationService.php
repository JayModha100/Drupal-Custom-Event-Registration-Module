<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;

class EventRegistrationService {

  protected Connection $database;
  protected TimeInterface $time;

  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Returns events that are currently open for registration.
   */
  public function getOpenEvents(): array {
    $now = $this->time->getCurrentTime();

    return $this->database->select('event_registration_event', 'e')
      ->fields('e')
      ->condition('reg_start_date', $now, '<=')
      ->condition('reg_end_date', $now, '>=')
      ->execute()
      ->fetchAll();
  }

  /**
   * Saves a user registration.
   */
  public function saveRegistration(array $values): void {
    $event = $this->database->select('event_registration_event', 'e')
      ->fields('e', ['category', 'event_date'])
      ->condition('id', $values['event_id'])
      ->execute()
      ->fetchObject();

    if (!$event) {
      throw new \InvalidArgumentException('Invalid event selected.');
    }

    $this->database->insert('event_registration_submission')
      ->fields([
        'full_name'  => $values['full_name'],
        'email'      => $values['email'],
        'college'    => $values['college'],
        'department' => $values['department'],
        'category'   => $event->category,
        'event_date' => $event->event_date,
        'event_id'   => $values['event_id'],
        'created'    => $this->time->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Checks if a registration already exists.
   */
  public function isDuplicateRegistration(string $email, int $event_id): bool {
    $existing = $this->database->select('event_registration_submission', 's')
      ->fields('s', ['id'])
      ->condition('email', $email)
      ->condition('event_id', $event_id)
      ->execute()
      ->fetchField();

    return !empty($existing);
  }

  /**
   * Returns event dates (timestamps) filtered by category.
   */
  public function getEventDatesByCategory(string $category): array {
    $now = $this->time->getCurrentTime();

    $result = $this->database->select('event_registration_event', 'e')
      ->fields('e', ['event_date'])
      ->condition('category', $category)
      ->condition('reg_start_date', $now, '<=')
      ->condition('reg_end_date', $now, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();

    $dates = [];
    foreach ($result as $timestamp) {
      $dates[$timestamp] = date('Y-m-d', $timestamp);
    }

    return $dates;
  }

  /**
   * Returns event names filtered by category and event date (timestamp).
   */
  public function getEventNamesByCategoryAndDate(string $category, int $event_date): array {
    $now = $this->time->getCurrentTime();

    $result = $this->database->select('event_registration_event', 'e')
      ->fields('e', ['id', 'event_name'])
      ->condition('category', $category)
      ->condition('event_date', $event_date)
      ->condition('reg_start_date', $now, '<=')
      ->condition('reg_end_date', $now, '>=')
      ->execute()
      ->fetchAll();

    $options = [];
    foreach ($result as $event) {
      $options[$event->id] = $event->event_name;
    }

    return $options;
  }

  /**
   * Returns full event info by event ID.
   */
  public function getEventById(int $id): ?object {
    return $this->database->select('event_registration_event', 'e')
      ->fields('e')
      ->condition('id', $id)
      ->execute()
      ->fetchObject() ?: null;
  }

}
