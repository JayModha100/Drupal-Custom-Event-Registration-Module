<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventConfigForm extends FormBase {

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
    return 'event_registration_event_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the Event'),
      '#options' => [
        'Online Workshop' => 'Online Workshop',
        'Hackathon' => 'Hackathon',
        'Conference' => 'Conference',
        'One-day Workshop' => 'One-day Workshop',
      ],
      '#required' => TRUE,
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
    ];

    $form['reg_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration Start Date'),
      '#required' => TRUE,
    ];

    $form['reg_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration End Date'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->database->insert('event_registration_event')
      ->fields([
        'event_name'     => $form_state->getValue('event_name'),
        'category'       => $form_state->getValue('category'),
        'event_date'     => strtotime($form_state->getValue('event_date')),
        'reg_start_date' => strtotime($form_state->getValue('reg_start_date')),
        'reg_end_date'   => strtotime($form_state->getValue('reg_end_date')),
        'created'        => time(),
      ])
      ->execute();

    // This works because FormBase already provides it
    $config = $this->configFactory()->get('event_registration.settings');

    $this->messenger()->addStatus($this->t('Event saved successfully.'));
  }
}
