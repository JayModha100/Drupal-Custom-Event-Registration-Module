<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EventRegistrationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'event_registration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('event_registration.settings');

    $form['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin email'),
      '#default_value' => $config->get('admin_email') ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Email address to receive notifications when a user registers.'),
    ];

    $form['admin_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable admin notifications'),
      '#default_value' => $config->get('admin_notify') ?? TRUE,
      '#description' => $this->t('Check to notify the admin for every new registration.'),
    ];

    return parent::buildForm($form, $form_state) + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('admin_email');
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('admin_email', $this->t('Please enter a valid email address.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('admin_notify', (bool) $form_state->getValue('admin_notify'))
      ->save();

    $this->messenger()->addStatus($this->t('Settings saved successfully.'));

    parent::submitForm($form, $form_state);
  }
}
