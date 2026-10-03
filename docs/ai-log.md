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
