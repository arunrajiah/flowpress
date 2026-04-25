# FlowPress — Automation for WordPress

[![CI](https://github.com/arunrajiah/flowpress/actions/workflows/ci.yml/badge.svg)](https://github.com/arunrajiah/flowpress/actions/workflows/ci.yml)
[![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/arunrajiah/flowpress/releases/tag/v0.1.0)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-21759B.svg)](https://wordpress.org/)
[![Sponsor](https://img.shields.io/badge/sponsor-%E2%99%A5-f43f5e.svg)](https://github.com/sponsors/arunrajiah)

Free, open-source, on-site automation for WordPress. Build **"when X happens, do Y"** workflows — no cloud service, no monthly fee, no data ever leaving your server.

---

## Why FlowPress?

Most WordPress automation tools phone home to a third-party service, charge a monthly fee, or both. FlowPress runs entirely inside WordPress:

| | FlowPress | Cloud automation tools |
|---|---|---|
| Monthly cost | **Free** | $20–$100 / month |
| Data leaves your server | **Never** | Always |
| Works without internet | **Yes** | No |
| Open source | **MIT** | Usually not |
| Extends via code | **Yes** | Sometimes |

---

## Features

- **Visual recipe builder** — pick a trigger, add optional conditions, then chain one or more actions
- **Token placeholders** — insert `{{post_title}}`, `{{user_email}}`, `{{order_total}}` into any action field
- **AND / OR conditions** — 10 operators: `equals`, `not_equals`, `contains`, `not_contains`, `starts_with`, `ends_with`, `greater_than`, `less_than`, `is_set`, `is_empty`
- **Retry queue** — failed actions are retried up to 4 times with exponential back-off (60 s → 5 min → 30 min → 2 hr)
- **Full run log** — every execution records its payload, per-action result, and final status
- **Dry-run test** — fire any recipe from the builder UI without publishing a real post or placing a real order
- **Duplicate recipes** — clone a recipe in one click to build variants quickly
- **Extensible** — register custom triggers and actions from any plugin with two action hooks

---

## Bundled Triggers

| Trigger | WordPress Hook | Tokens |
|---|---|---|
| Post Published | `transition_post_status` | `post_id`, `post_title`, `post_url`, `post_type`, `post_author_id`, `post_author_name`, `post_author_email`, `post_status`, `post_date`, `post_excerpt` |
| Comment Posted | `comment_post` | `comment_id`, `comment_author`, `comment_author_email`, `comment_content`, `comment_post_id`, `comment_post_title` |
| User Registered | `user_register` | `user_id`, `user_login`, `user_email`, `user_display_name`, `user_registered` |
| User Role Changed | `set_user_role` | `user_id`, `user_email`, `user_display_name`, `old_role`, `new_role` |
| WooCommerce Order Placed *(requires WC)* | `woocommerce_checkout_order_processed` | `order_id`, `order_total`, `order_status`, `billing_email`, `billing_first_name`, `billing_last_name`, `billing_address`, `payment_method`, `customer_id` |
| Incoming Webhook | REST `POST /wp-json/flowpress/v1/webhook/{slug}` | Any key from the JSON body |

---

## Bundled Actions

| Action | Description |
|---|---|
| **Send Email** | `wp_mail()` with token-resolved `To`, `Subject`, and `Body`; supports HTML |
| **Outbound Webhook** | HTTP POST / GET / PUT to any URL; optional HMAC-SHA256 signature header |
| **WooCommerce Create Coupon** *(requires WC)* | Generates a `WC_Coupon` with configurable discount type, amount, and expiry |
| **WooCommerce Add Order Note** *(requires WC)* | Appends an internal or customer-facing note to any order |

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 5.9 |
| PHP | 7.4 |
| MySQL / MariaDB | 5.6 / 10.0 |
| WooCommerce *(optional)* | 7.0 |

---

## Installation

**Option 1 — GitHub Release ZIP** *(recommended)*

1. Download the latest ZIP from the [Releases page](https://github.com/arunrajiah/flowpress/releases).
2. In WordPress go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Activate**.

**Option 2 — Git clone**

```bash
cd wp-content/plugins
git clone https://github.com/arunrajiah/flowpress.git
```

Then activate the plugin in **Plugins → Installed Plugins**.

**Option 3 — Composer** *(coming soon)*

```bash
composer require arunrajiah/flowpress
```

---

## Quick Start

1. Navigate to **FlowPress → Recipes** and click **Add New Recipe**.
2. Give the recipe a name and choose a **Trigger** (e.g. *Post Published*).
3. Optionally add **Conditions** (e.g. *post_type equals product*).
4. Click **+ Add Action** and configure it (e.g. *Send Email* with `{{post_author_email}}` in the To field).
5. Click **Save Recipe** then **Enable** it from the list.

The next time the trigger fires, FlowPress will execute the recipe and record the result in **FlowPress → Runs**.

---

## Token Reference

Tokens are written as `{{token_key}}` inside any action field. Each trigger exposes its own set of tokens:

```
{{post_id}}           →  123
{{post_title}}        →  "Hello World"
{{post_url}}          →  "https://example.com/hello-world/"
{{user_email}}        →  "jane@example.com"
{{order_total}}       →  "49.99"
```

Tokens that are not present in the current trigger payload are left as-is (not replaced), making it easy to spot configuration errors in the run log.

---

## Architecture

```
WordPress hook fires
        │
        ▼
  FlowPress_Runner
  ├── Finds all enabled recipes for this trigger
  ├── Evaluates conditions (FlowPress_Condition_Evaluator)
  │       └── AND / OR across 10 operators
  ├── Resolves {{tokens}} (FlowPress_Placeholder)
  └── Executes actions in order
          ├── Success → logs result
          └── Failure → FlowPress_Retry_Queue (WP-Cron, exponential back-off)

Admin layer
  ├── Recipe builder (JS + PHP AJAX)
  ├── Recipes list table (WP_List_Table)
  └── Runs dashboard (per-recipe log viewer)
```

All data lives in standard WordPress tables (`wp_posts`, `wp_postmeta`) plus two custom tables (`flowpress_run_log`, `flowpress_audit_log`). No external database, no proprietary storage format.

---

## Extending FlowPress

Register custom triggers and actions from any plugin:

```php
// Register a custom trigger
add_action( 'flowpress_register_triggers', function ( string $registry ): void {
    $registry::register( new My_Plugin_Trigger_Form_Submitted() );
} );

// Register a custom action
add_action( 'flowpress_register_actions', function ( string $registry ): void {
    $registry::register( new My_Plugin_Action_Send_Slack() );
} );
```

A trigger must extend `FlowPress_Abstract_Trigger` and implement `get_type()`, `get_label()`, `get_tokens()`, and `register_hook()`. An action must extend `FlowPress_Abstract_Action` and implement `get_type()`, `get_label()`, `get_fields()`, and `execute()`.

See [`docs/DEVELOPERS.md`](docs/DEVELOPERS.md) for the full API reference and [`example-integration/`](example-integration/) for a working starter plugin.

---

## Project Structure

```
flowpress/
├── flowpress.php                    # Plugin entry point & constants
├── flowpress-hooks.php              # Public hook reference (never loaded at runtime)
├── readme.txt                       # WordPress.org plugin directory readme
├── uninstall.php                    # Cleanup on plugin deletion
├── composer.json
├── phpcs.xml
├── docs/
│   └── DEVELOPERS.md               # Extension API guide
├── example-integration/            # Copy-and-rename starter integration plugin
│   ├── flowpress-example-integration.php
│   └── includes/
│       ├── class-fp-example-trigger-cf7.php
│       └── class-fp-example-action-slack.php
├── includes/
│   ├── class-flowpress.php          # Singleton orchestrator
│   ├── class-flowpress-activator.php
│   ├── class-flowpress-deactivator.php
│   ├── class-flowpress-runner.php
│   ├── class-flowpress-recipe.php
│   ├── class-flowpress-placeholder.php
│   ├── class-flowpress-condition-evaluator.php
│   ├── class-flowpress-retry-queue.php
│   ├── class-flowpress-incoming-webhook.php
│   ├── class-flowpress-abstract-trigger.php
│   ├── class-flowpress-abstract-action.php
│   ├── triggers/
│   │   ├── class-flowpress-trigger-post-published.php
│   │   ├── class-flowpress-trigger-comment-posted.php
│   │   ├── class-flowpress-trigger-user-registered.php
│   │   ├── class-flowpress-trigger-user-role-changed.php
│   │   ├── class-flowpress-trigger-woo-order-placed.php
│   │   └── class-flowpress-trigger-incoming-webhook.php
│   ├── actions/
│   │   ├── class-flowpress-action-send-email.php
│   │   ├── class-flowpress-action-outbound-webhook.php
│   │   ├── class-flowpress-action-woo-create-coupon.php
│   │   └── class-flowpress-action-woo-order-note.php
│   └── admin/
│       ├── class-flowpress-admin.php
│       ├── class-flowpress-recipes-list-table.php
│       ├── class-flowpress-runs-admin.php
│       └── views/
│           ├── builder.php
│           ├── promo-banner.php
│           └── runs-page.php
├── assets/
│   ├── js/admin.js
│   └── css/admin.css
└── tests/
    ├── test-plugin-activation.php
    ├── test-runner.php
    └── test-conditions.php
```

---

## Development

### Running Tests

```bash
# Install WordPress test suite (one-time, requires a local MySQL instance)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run PHPUnit
composer test

# Run a single test file
./vendor/bin/phpunit tests/test-runner.php

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

### Code Style

```bash
# Check (zero errors expected)
composer cs

# Auto-fix
composer cs-fix
```

### CI

GitHub Actions runs PHPCS and PHPUnit across PHP 7.4, 8.1, and 8.2 against the latest WordPress on every push and pull request.

---

## Contributing

Contributions are welcome — bug reports, feature requests, code, docs, or tests.

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request. Key points:

- Branch: `feat/<description>`, `fix/<description>`, `docs/<description>`
- Commit style: [Conventional Commits](https://www.conventionalcommits.org/)
- All CI checks must pass before merge

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full history of changes.

---

## License

MIT © [FlowPress Contributors](https://github.com/arunrajiah/flowpress/graphs/contributors)

Built with ♥ by [arunrajiah](https://github.com/arunrajiah).
[Become a sponsor](https://github.com/sponsors/arunrajiah) to support continued development.
