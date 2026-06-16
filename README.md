# Eemo Find

Enterprise GPS tracking, family safety, subscription, payments, support, SOS, geofence, and mobile API platform built with Laravel 11 and PHP 8.2.

## Local setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Default seeded administrator:

- Email: `admin@eemofind.com`
- Password: `Eemo@12345`

Change these through `ADMIN_EMAIL` and `ADMIN_PASSWORD` before running the production seed. Change the password immediately after first login.

## Production workers

Run the queue worker and scheduler under a process manager:

```bash
php artisan queue:work --sleep=2 --tries=3 --timeout=90
php artisan schedule:work
```

Set `QUEUE_CONNECTION=database` or Redis in production. Configure MySQL 8 in `.env`, then run `php artisan migrate --force`.

## Integrations

Open **Settings** as Super Admin to configure:

- Firebase project ID and Realtime Database URL
- Firebase service-account JSON
- Razorpay key ID and secret
- Tracking heartbeat/offline thresholds
- Branding, support details, pairing code length, and theme

Firebase stores live location, location history, device status, heartbeat, SOS events, and geofence cache. MySQL stores only the latest device snapshot for operational queries.

## API

Mobile endpoints are versioned under `/api/v1` and authenticated with Laravel Sanctum. Run `php artisan route:list --path=api/v1` for the complete endpoint list.

## Verification

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
npm audit
```
