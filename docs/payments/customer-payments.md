# Customer payments stage 3

Local implementation only; see the paired sparkleen-app `docs/customer-payments.md`
and `docs/api/sparkleen-mobile-v1.md` for the customer contract. All four payment
routes are authenticated and ownership-scoped. Creation is disabled by default via
MOBILE_PAYMENTS_ENABLED=false. The provider remains SIMULATION: do not send real money.

Deploy Payment API and Laundry compatible versions together, after backing up the
DB and running migration `2026-11-10-000003_AddCustomerPaymentLifecycle`. Configure
PAYMENT_API_URL, PAYMENT_API_KEY, separate PAYMENT_WEBHOOK_SECRET server-side. HTTPS
is mandatory in production. Migration rollback refuses unresolved CREATING attempts;
reconcile these before removing metadata. No production migration/deployment performed.

CREATING is persisted before network with an idempotency request key and unique active
order. Timeouts preserve the attempt; repeated creates resume the same remote invoice.
Signed create/status/cancel responses must match the snapshot. Cancel clears local
reference only after confirmed provider terminal state. Cash is blocked while an
online intent is active. A SUCCESS during cancellation settles via the existing
atomic ledger service; repeated settlement is idempotent. Existing web cashier cancel
now also waits for confirmed remote state. Legacy intents without known simulation
mode display REVIEW rather than potentially genuine payment instruments.

Reconciliation is foreground/on-demand, not a persistent callback queue. Outbox/retry,
real bank integration and notifications/points redemption remain separate work.

Tests: `php vendor/bin/phpunit`, plus opt-in real Dart/HTTP/Node/SQLite/MariaDB test in
sparkleen-app. Use only disposable localhost laundry_db_test; no external provider.

Verification: full suite 143 tests / 664 assertions passed; final cashier guard
and payment regression: 26 tests / 165 assertions. Paired Flutter 107 tests, analyze
clean, web release build and real localhost three-service integration passed.
Payment API: 17 tests passed.
