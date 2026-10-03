# Agent guidelines for this repository

Read this before proposing changes. It encodes the architectural decisions so
that generated code fits the existing shape instead of inventing a new one.

## What this is

A small inventory and purchasing service for a single-branch restaurant:
Laravel 13 / PHP 8.5 JSON API under `/api`, React + TypeScript UI served from
one Blade view, SQLite, Pest tests. Everything runs in Docker (`make up`);
never assume `php`, `composer` or `node` exist on the host. Use
`docker compose exec app ...` or the `Makefile` targets.

## Architecture rules

- API-first. The UI and the POS use the same `/api/*` routes. Do not add
  UI-only endpoints or server-rendered forms.
- Stock is never stored; it is `SUM(stock_movements.quantity)` per
  ingredient. Every stock change is an immutable `StockMovement` row with a
  `type` and a polymorphic `source`. Never `UPDATE` a stock number.
- Purchase order status changes go through `PurchaseOrderStatus::transitionTo()`
  so invalid moves throw `InvalidStateTransition` (409). Never assign
  `$po->status = ...` directly outside that path.
- Business logic lives in `app/Actions/*` as single-purpose invokable classes
  wrapped in `DB::transaction`. Controllers validate (Form Requests), call an
  Action, and return a Resource. Models hold relationships, casts and scopes.
- Business-rule violations throw `App\Exceptions\DomainException` subclasses;
  `bootstrap/app.php` renders them as JSON. Do not return error responses
  from Actions.
- Quantities are `decimal(12,3)` in the ingredient's own unit. No unit
  conversion.

## Testing

- Pest 4. Feature tests hit the HTTP API with `RefreshDatabase` on in-memory
  SQLite. Unit tests only for pure logic (enums, value objects).
- Every write endpoint needs tests for the happy path and each rejected path.
- Run with `make test` or `make test-filter F=<name>`.

## Style

- `vendor/bin/pint` for PHP (`make pint`). Typed properties, `readonly`
  where possible, enums over string constants.
- Keep diffs small and focused; one concern per commit.
