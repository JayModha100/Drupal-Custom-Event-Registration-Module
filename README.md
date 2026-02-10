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
  
## Database Tables
- `event_registration_event → Stores event configuration details (event_name, category, event_date, reg_start_date, reg_end_date, created)`
- `event_registration_submission → Stores event registrations (full_name, email, college, department, category, event_date, event_id, created)`

SQL dump is provided in database/event_registration.sql.

## Validation & Email Logic
- `Validation: Prevents duplicate registrations per event based on email and event date`
- `Email notifications: Sent to the user confirming registration and to the admin for new submissions. Uses Drupal mail system (SMTP can be configured if required)`

## Notes
- Email sending uses Drupal mail system (SMTP can be configured if required).
- CSV export reflects filtered admin table data.

## Drupal Version
- Drupal 10

## Evaluator Quick Test
   1. Enable the module and assign required permissions.
   2. Create events from the Event Configuration page.
   3. Register a user via /event/register.
   4. Verify admin listing, filters, total count, and CSV export at /admin/event-registrations.
