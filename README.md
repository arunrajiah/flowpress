# FlowPress — Automation for WordPress

Free, open-source, on-site automation for WordPress. Build "when X happens, do Y" workflows — no cloud service, no monthly fee, no data leaving your site.

---

## Features

- **Visual recipe builder** — select a trigger, optionally add conditions, then wire up one or more actions
- **Token placeholders** — use `{{post_title}}`, `{{user_email}}`, etc. inside any action field
- **AND / OR conditions** — 10 operators including `contains`, `starts_with`, `greater_than`, `is_set`
- **Retry queue** — failed actions retry up to 4 times with exponential back-off (60 s → 5 min → 30 min)
- **Full run log** — every execution stores its payload, per-action result, and status
- **Extensible** — register custom triggers and actions from any plugin with two action hooks

## Bundled Triggers

| Trigger | WordPress Hook |
|---|---|
| Post Published | `transition_post_status` |
| Comment Posted | `comment_post` |
| User Registered | `user_register` |
| User Role Changed | `set_user_role` |
| WooCommerce Order Placed *(WC only)* | `woocommerce_checkout_order_processed` |
| Incoming Webhook | REST `POST /wp-json/flowpress/v1/webhook/{slug}` |

## Bundled Actions

| Action | Description |
|---|---|
| Send Email | `wp_mail()` with token-resolved subject and body |
| Outbound Webhook | HTTP POST/GET/PUT with optional HMAC signature |
| WooCommerce Create Coupon *(WC only)* | Generates a `WC_Coupon` |
| WooCommerce Add Order Note *(WC only)* | Adds internal or customer-facing note |

## Requirements

- WordPress 5.9+
- PHP 7.4+

## Installation

```bash
# Clone into your plugins directory
git clone https://github.com/flowpress/flowpress.git wp-content/plugins/flowpress

# Install dev dependencies (optional — for testing/linting only)
composer install
```

Activate the plugin in **Plugins > Installed Plugins**, then navigate to **FlowPress** in the admin sidebar.

## Development

### Running Tests

```bash
# Install WordPress test suite (once)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run PHPUnit
composer test
```

### Code Style

```bash
# Check
composer cs

# Fix automatically
composer cs-fix
```

### CI

GitHub Actions runs PHPCS (PHP 8.1) and PHPUnit (PHP 7.4 / 8.1 / 8.2 × WordPress latest) on every push and pull request.

## Extending FlowPress

Register custom triggers and actions from any plugin:

```php
add_action( 'flowpress_register_triggers', function ( string $registry ): void {
    $registry::register( new My_Custom_Trigger() );
} );

add_action( 'flowpress_register_actions', function ( string $registry ): void {
    $registry::register( new My_Custom_Action() );
} );
```

See [`docs/DEVELOPERS.md`](docs/DEVELOPERS.md) and [`example-integration/`](example-integration/) for the full API reference and a working example plugin.

## Project Structure

```
flowpress/
├── flowpress.php                    # Plugin entry point
├── flowpress-hooks.php              # Public hook reference (never loaded at runtime)
├── readme.txt                       # WordPress.org plugin directory readme
├── uninstall.php                    # Cleanup on plugin deletion
├── composer.json
├── phpcs.xml
├── .distignore                      # Files excluded from release ZIP
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
│   ├── triggers/
│   └── actions/
├── assets/
│   ├── js/admin.js
│   └── css/admin.css
└── tests/
    ├── test-plugin-activation.php
    ├── test-runner.php
    └── test-conditions.php
```

## Changelog

### 0.1.0

Initial release. See [readme.txt](readme.txt) for the full changelog.

## License

GPL-2.0-or-later © FlowPress Contributors
