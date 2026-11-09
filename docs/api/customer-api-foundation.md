# Customer API foundation

Branch: `feat/customer-api-foundation`, based on Laundry `524f554`.

This backend implements the 11 customer endpoints used by Sparkleen App. It does
not implement pickup requests, mobile payment initiation, a wallet, or push messaging.
The existing cashier tables remain authoritative for amounts, points and order status.

## Routes

All paths below are relative to `/api/v1`.

| Method | Path | Authentication |
| --- | --- | --- |
| POST | `/auth/register` | Public, rate limited |
| POST | `/auth/activate` | Public, rate limited |
| POST | `/auth/login` | Public, rate limited |
| POST | `/auth/logout` | Bearer |
| GET | `/me` | Bearer |
| PATCH | `/me` | Bearer |
| GET | `/layanan` | Bearer |
| GET | `/outlet` | Bearer |
| GET | `/transaksi` | Bearer |
| GET | `/transaksi/{id}` | Bearer |
| GET | `/transaksi/{id}/nota` | Bearer |

Successful responses use `{data, meta?}`; errors use
`{error: {code, message, fields?}}`. Logout returns 204 with no body. Money is
integer rupiah and dates include the Jakarta UTC offset. Pagination accepts
`status=aktif|selesai|semua`, positive `page`, and `per_page` between 1 and 100.
Active means menunggu/proses/setrika; selesai means selesai/diambil. The list is
newest first. Orders from a different customer, deleted orders, and missing orders
all return 404. List/nota never include the pickup PIN; owned detail may include it.

PINs use PHP password hashing. Random 64-character tokens are issued only at
authentication; only their SHA-256 hashes are stored. Valid use extends expiry by
30 days; logout revokes the current token. Inactive customers cannot authenticate.
The API accepts legacy `08...` and canonical `62...` phone forms and rejects
ambiguous legacy duplicates for operator review. Activation requires the customer's
latest non-deleted nota and never replaces an existing PIN.

Login/register/activation use atomic DB counters: 5 attempts per action, normalized
phone and IP in 15 minutes, plus 30 attempts per IP. A 429 includes `Retry-After`
and `error.fields.retry_after`. Retention for `tb_api_auth_attempts` should be set
up before sustained public traffic; expired counter rows can be pruned by
`window_start` in scheduled maintenance.

## Configuration and migration

Use the existing Composer lock file, PHP 8.2+ and MySQL/MariaDB. Configure the normal
application database through the existing environment settings. Back up the database
and verify the migration on a disposable copy before applying it operationally.

```sh
composer install
php spark migrate --all
```

New migration `2026-11-09-000001_AddCustomerMobileAuth` adds three nullable auth columns
to `tb_pelanggan`, plus `tb_api_token` and `tb_api_auth_attempts`. It does not alter
`tb_transaksi`. Rollback removes mobile credentials/tokens, so customers must
activate again if this migration is later reapplied. Avoid rolling back the entire
legacy migration history: some old migrations have incomplete down methods.

Public outlet information is server configuration, for example:

```ini
outlet.nama = 'Sparkleen Laundry'
outlet.noWa = ''
outlet.alamat = ''
outlet.jamBuka = '08:00'
outlet.jamTutup = '21:00'
MOBILE_ALLOWED_ORIGINS = 'https://your-customer-web.example'
```

Use the exact `outlet.noWa`/`outlet.jamBuka` property spelling above. Empty contact
fields remain empty instead of inventing operational information. Android needs no
CORS origin. Web clients require an explicit allowed origin; cookies are not used
for customer API authentication. Production connections must use HTTPS. API auth
is independent of cashier web sessions, and web CSRF protection remains enabled.
No merchant secret belongs in Sparkleen App.

## Tests and local integration demo

**The shared PHPUnit bootstrap drops tables only in the explicitly named local
`laundry_db_test` database. This must be a disposable test database.** It refuses
remote hosts and other database names. Configure `database.tests.*` locally if
needed; no credentials should be committed. Runtime provisioning is external to
the repository.

```sh
php vendor/bin/phpunit --filter CustomerApiTest
php vendor/bin/phpunit
```

For a local demo using real DB/API requests rather than fake HTTP repositories:

1. Create the disposable database above and configure `database.tests.*`.
2. Run `php tools/mobile_demo.php reset` from this repo. This deliberately resets
   only that local test DB, migrates it and seeds fictional customers/orders.
3. Start the test API in a separate terminal:
   `CI_ENVIRONMENT=testing php -S 127.0.0.1:42525 -t public public/index.php`.
   In PowerShell, set `$env:CI_ENVIRONMENT='testing'` before running PHP.
4. From the Sparkleen App checkout set `LAUNDRY_TEST_ROOT` to this repo's absolute
   path and `TEST_PHP_BIN` to the local PHP executable. Run:

```sh
CI=true flutter test tool/customer_api_integration_test.dart --dart-define=API_BASE_URL=http://127.0.0.1:42525
```

The opt-in integration tool uses fake **device token storage**, but real Dart
repositories, Dio HTTP, CodeIgniter routes and the test database. It also invokes
the actual cashier status/payment service against demo data, with WhatsApp sending
turned off. It checks another account cannot read the first customer's order.
Ordinary `flutter test` retains HTTP isolation and does not contact a live API.

The seeder creates customer 1 with test phone `081298765432` and demo PIN `654321`.
These are fictional test credentials, not an operational account. The demo's
`cashier-update` action never calls a payment provider. Never expose this test
server publicly or use the test fixture against an operational DB.

## Payment guardrail and remaining scope

An unavailable/rejected online gateway now returns a failure to the cashier
instead of falling through into a local payment insert. Explicit cash payments
remain supported. Missing configuration and transport failure are covered by tests.

This does not make the whole payment ecosystem production ready. Webhook amount
validation/atomic deduplication, callback retry/reconciliation, merchant/user
separation and Smart Pay's simulated approval/saldo still need separate work.
Existing public debug/seed routes should also be reviewed before public deployment.
Deployment identity, native Android behavior and operational data migration have
not been verified by local integration tests.
