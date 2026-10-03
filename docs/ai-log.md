# AI usage log

Notes from building this. I used Cursor as a pair on a slice only after I
had decided what that slice was. Prompts below are from memory, so the
wording is rough.

## Before any code

I read the brief on my own and sketched the plan on a whiteboard before
opening Cursor. The state machine was the part I wanted in front of me:
draft, sent, partially received, closed, and stock as a sum of movements
rather than a column someone can overwrite. A few spots I was not ready to
lock, so I talked those through afterwards. What `received` actually means.
What a sale should do when the count would go below zero. Whether the
official React starter kit was the right start. After that conversation I
wrote the plan down and stopped changing it.

Closed follows the quantities. A person pressing "close" can hide goods that
never arrived. A sale has already happened at the till, so the books record
it and the negative number is the signal to recount. I dropped the starter
kit. It is Inertia and a set of auth pages, and the UI would not be calling
the API the POS calls. Docker only, nothing installed on the machine.

## Docker, then the test setup

First slice was getting Laravel to boot inside Docker. Docker Desktop's pull
proxy hung once even though running containers had a network; a restart of
Desktop cleared it. Next was Pest. The install command I was given,
`php artisan pest:install`, does not exist here. `vendor/bin/pest --init`
does, which I found on the first failed run. I also threw out the stock
AGENTS.md and kept a short one with the rules I did not want to repeat in
every later prompt: stock is a ledger, status changes only go through
`transitionTo`.

## Catalog, orders, ledger, deliveries

Ingredients, suppliers, then recipes. Once purchase orders needed the same
quantity lines I pulled the validation into one trait instead of letting a
second copy appear. For the status enum I asked for every from/to pair in
the test, including the illegal ones. A test that only checks `draft -> sent`
stays green if a shortcut gets added later.

The first purchase-order test failed for a dull reason. The default status
lived only on the column, so a newly created model had `status = null` in
memory and the JSON resource crashed on `->value`. The default is now also
on the model's `$attributes`.

Deliveries I read line by line. Two things I sent back:

- One bad line has to roll the whole receipt back. The test counts movements
  and delivery rows, so a 422 that still inserted stock fails.
- Over-receipt is checked on every line before any insert.

An exception class from the first day collided with `Exception::$code` and
fatally errored the first time a delivery actually threw. The field is
`$errorCode` now. Nothing had thrown it before, so the earlier tests were
green. A separate test caught the create endpoint quietly turning into a
200: `fresh()` drops `wasRecentlyCreated`, and Laravel was inferring 201
from that. The controller sets 201 itself now.

## Sales, stock, seed data

Sales went through on the first test run. I checked the seeded numbers by
hand against the recipes before trusting them (beef 6000, minus 12 burgers
at 150 g, minus 5 deluxe at 200 g, is 3200). One assertion nit: PHP turns
JSON `700.0` into the integer `700`, so `toBe(700.0)` fails. `toEqual` is
the one that matches what the API actually returns.

## React scaffold

`App.tsx` for the component and `app.tsx` for the entry are the same file
on macOS. The entry overwrote the component and `tsc` said there was no
default export. The entry is `main.tsx` now. That would have been invisible
on Linux. Tailwind v4 also refused `@apply btn` on a custom class, so the
button styles are separate classes used together. TypeScript 7 dropped
`baseUrl`, which meant the `@/` paths had to be relative, and Vite needed
the same alias or the imports failed in the browser.
