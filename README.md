# Event Registration Module (Drupal 10)

## Overview
Custom Drupal 10 module that allows users to register for events and provides an admin interface to manage registrations and export them as CSV.

## Features
- Event registration form with AJAX-based dropdowns
- Duplicate registration prevention
- Email notifications (user + admin)
- Admin listing page with filters
- CSV export of registrations
- Custom permissions

## Installation
1. Place the module in:
   `modules/custom/event_registration`
2. Enable the module:
   `drush en event_registration`
3. Import database tables:
   `mysql -u USER -p DB_NAME < sql/event_registration.sql`

## Usage
- User registration page: `/event/register`
- Admin listing page: `/admin/event-registrations`
- Settings page: `/admin/config/event-registration/settings`

## Permissions
- `event registration`
- `administer event registrations`

## Notes
- Email sending uses Drupal mail system (SMTP can be configured if required).
- CSV export reflects filtered admin table data.

## Drupal Version
- Drupal 10
