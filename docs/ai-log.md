# AI usage log

Notes from building this. I used Cursor as a pair on a slice only after I
had decided what that slice was. Specific wording throughout is reconstructed
from memory, not verbatim.

## Before any code

I read the brief on my own and sketched the plan on a whiteboard. The state
machine was the part I wanted in front of me: draft, sent, partially
received, closed, and stock as a sum of movements rather than a column
someone can overwrite. A few spots I was not ready to lock, so I talked
those through in a separate chat conversation, before Cursor was opened at
all. What `received` actually means. What a sale should do when the count
would go below zero. Whether the official React starter kit was the right
start. After that conversation I wrote the plan down and locked it. The
Cursor sessions start at T1, against that plan.

Closed follows the quantities. A person pressing "close" can hide goods that
never arrived. A sale has already happened at the till, so the books record
it and the negative number is the signal to recount. I dropped the starter
kit. It is Inertia and a set of auth pages, and the UI would not be calling
the API the POS calls. Docker only, nothing installed on the machine.

## T1: test harness

- `php artisan pest:install` does not exist here; `vendor/bin/pest --init` does.
- Replaced the stock `AGENTS.md` with the rules I did not want to repeat: stock is a ledger, status changes only through `transitionTo`.

## T2-T3: catalog

- Shared quantity-line validation in one trait so purchase orders would not get a second copy.

## T4: purchase order state machine

- The status test enumerates every from/to pair, including the illegal ones. A test that only checks `draft -> sent` stays green if a shortcut gets added later.
- The first purchase-order test failed because the default status lived only on the column, so a new model had `status = null` in memory and the resource crashed on `->value`. The default is also on the model's `$attributes`.

## T5: ledger and deliveries

- `InvalidOperation` had a promoted `private readonly string $code`, which collides with `Exception::$code` and fatally errored the first time a delivery actually threw. The field is `$errorCode` now. Earlier tests were green because nothing had thrown it.
- Over-receipt is checked on every line before any insert. One bad line rolls the whole receipt back; the test counts movements and delivery rows, so a 422 that still inserted stock fails.
- Create quietly turned into a 200: `fresh()` drops `wasRecentlyCreated`, and Laravel was inferring 201 from that. The controller sets 201 itself.

## T6-T8: sales, stock, seed data

- Checked the seeded stock by hand: beef 6000, minus 12 burgers at 150 g, minus 5 deluxe at 200 g, is 3200.
- JSON `700.0` decodes as the integer `700`, so `toBe(700.0)` fails; `toEqual` matches what the API returns.

## UI (T9–T13)

Found while building the screens and clicking through the built assets:

- `App.tsx` and `app.tsx` are the same file on macOS, so the entry overwrote the component. The entry is `main.tsx`. Would have been invisible on Linux.
- Tailwind v4 refused `@apply btn` on a custom class, so the button styles are separate classes used together.
- TypeScript 7 dropped `baseUrl`, so the `@/` paths are relative, and Vite needed the same alias.
- `crypto.randomUUID` only exists in a secure context, so a Docker hostname or a LAN IP crashed the POS tab. There is a `getRandomValues` fallback.
- Creating an order left the detail panel on the previous one. The selection was corrected from a stale cached list before the refetch came back; it now waits until that query has settled.
- After a partial delivery the form still showed the quantities just submitted, so a second click would send them again. The form is recreated when a delivery is added and prefills the new outstanding.

## T14: README

Written last, from the decisions above. I checked the test names and the
request fields it mentions against the code. One `make test-filter` example
pointed at a test that does not exist. The sample sale response I copied
from a `curl` against the seeded app.
