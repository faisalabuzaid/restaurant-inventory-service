# Restaurant Inventory Service

Inventory and purchasing backend (with a small web UI) for a single-branch
restaurant: ingredients, suppliers, recipes, purchase orders, deliveries, POS
sales, and live stock visibility.

Built for the Foodics ERP build challenge. Laravel 13 / PHP 8.5, React +
TypeScript, SQLite, Pest. Everything runs in Docker.

## Quick start

Prerequisites: Docker Desktop (or Docker Engine with Compose v2). Nothing
else is installed on the host.

```sh
git clone <repo-url> restaurant-inventory-service
cd restaurant-inventory-service
make up        # builds the image, installs deps, migrates + seeds, starts the app
```

Then open http://localhost:8000. First boot takes a minute or two while
Composer and npm dependencies install; subsequent starts are seconds.

```sh
make test      # run the test suite
make logs      # tail logs
make fresh     # reset and reseed the database
make down      # stop
make help      # all targets
```

## Features

_To be completed._

## Data model

_To be completed._

## Purchase order lifecycle

_To be completed._

## API

_To be completed._

## Decisions on the open-ended parts

_To be completed._

## Architecture notes

_To be completed._

## Testing

_To be completed._

## How I used AI

_To be completed. Running log in [docs/ai-log.md](docs/ai-log.md)._

## What I would do next

_To be completed._
