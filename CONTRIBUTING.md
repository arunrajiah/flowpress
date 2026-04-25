# Contributing to FlowPress

Thank you for taking the time to contribute! FlowPress is a community project and every contribution — bug reports, feature requests, code, docs, or tests — makes a difference.

Please read this guide before opening a pull request or issue. Following these guidelines helps maintainers review and accept your contributions quickly.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Reporting bugs](#reporting-bugs)
- [Requesting features](#requesting-features)
- [Development setup](#development-setup)
- [Making changes](#making-changes)
- [Pull request checklist](#pull-request-checklist)
- [Coding standards](#coding-standards)
- [Writing a custom trigger](#writing-a-custom-trigger)
- [Writing a custom action](#writing-a-custom-action)
- [Tests](#tests)

---

## Code of Conduct

Please be respectful and constructive in all interactions. We follow the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct. Report unacceptable behaviour to **arunrajiah@gmail.com**.

---

## Reporting bugs

Before opening a new issue:

1. Search [existing issues](https://github.com/arunrajiah/flowpress/issues) to avoid duplicates.
2. Reproduce the bug on a clean WordPress installation if possible.

A good bug report includes:

- WordPress version, PHP version, FlowPress version
- Step-by-step reproduction instructions
- Expected vs actual behaviour
- Error messages or stack traces (check your PHP error log and browser console)
- Whether the bug also occurs with all other plugins deactivated

> **Security vulnerabilities** should not be reported via public GitHub issues.
> See [SECURITY.md](SECURITY.md) for the private disclosure process.

---

## Requesting features

Open a [Feature Request issue](https://github.com/arunrajiah/flowpress/issues/new) with:

- A clear use case ("As a developer I need…")
- Why it fits the project scope (on-site automation, no cloud dependency)
- Whether you are willing to implement it yourself

---

## Development setup

```bash
# Clone
git clone https://github.com/arunrajiah/flowpress.git
cd flowpress

# Install PHP dependencies
composer install

# Install WordPress test suite (one-time, requires a local MySQL instance)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

---

## Making changes

### Branch naming

| Type | Pattern | Example |
|---|---|---|
| Feature | `feat/<short-description>` | `feat/add-slack-action` |
| Bug fix | `fix/<short-description>` | `fix/retry-queue-cron-interval` |
| Documentation | `docs/<short-description>` | `docs/developer-api-guide` |
| Chore | `chore/<short-description>` | `chore/bump-phpunit` |

### Commit style

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(runner): support multiple trigger payload merges
fix(list-table): restore column headers lost after WP 6.5 update
docs(readme): add token reference table
chore(ci): add PHP 8.2 to PHPUnit matrix
test(conditions): cover greater_than operator with floats
```

### Workflow

1. Fork the repository.
2. Create a branch from `main`.
3. Make your changes — keep each commit focused on a single concern.
4. Run the full check locally (see [Tests](#tests)) before pushing.
5. Open a pull request against `main`.

---

## Pull request checklist

- [ ] `composer test` passes — no failures, no errors
- [ ] `composer cs` passes — zero PHPCS errors (warnings are OK)
- [ ] New functionality has tests in `tests/`
- [ ] `CHANGELOG.md` has an entry under `[Unreleased]`
- [ ] PR description explains the problem and the solution
- [ ] Breaking changes (if any) are called out explicitly

---

## Coding standards

FlowPress follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) (PHPCS rule set in `phpcs.xml`).

```bash
# Check
composer cs

# Auto-fix
composer cs-fix
```

**PHP compatibility:** all code must run on PHP 7.4 through 8.3.

**Key rules:**

- Prefix all global functions, classes, and constants with `flowpress_` / `FlowPress_` / `FLOWPRESS_`.
- Sanitise all inputs with the appropriate WordPress function (`sanitize_text_field()`, `absint()`, etc.).
- Escape all outputs with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.
- Never write raw `$_POST`, `$_GET`, or `$_SERVER` values to the database.
- Verify nonces in all form submissions and AJAX handlers.

---

## Writing a custom trigger

1. **Create the class file** at `includes/triggers/class-flowpress-trigger-{slug}.php`:

```php
<?php
/**
 * Fires when a Contact Form 7 form is submitted.
 *
 * @package FlowPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowPress_Trigger_CF7_Submitted extends FlowPress_Abstract_Trigger {

    public function get_type(): string {
        return 'cf7_submitted';
    }

    public function get_label(): string {
        return __( 'CF7 Form Submitted', 'flowpress' );
    }

    public function get_description(): string {
        return __( 'Fires when any Contact Form 7 form is submitted successfully.', 'flowpress' );
    }

    public function get_icon(): string {
        return 'dashicons-email-alt';
    }

    public function get_tokens(): array {
        return array(
            array( 'token' => 'form_id',    'label' => __( 'Form ID', 'flowpress' ) ),
            array( 'token' => 'form_title', 'label' => __( 'Form title', 'flowpress' ) ),
            array( 'token' => 'sender_email', 'label' => __( 'Sender email', 'flowpress' ) ),
        );
    }

    public function register_hook(): void {
        add_action( 'wpcf7_mail_sent', array( $this, 'handle' ) );
    }

    public function handle( $cf7 ): void {
        $submission = WPCF7_Submission::get_instance();
        $payload    = array(
            'form_id'      => $cf7->id(),
            'form_title'   => $cf7->title(),
            'sender_email' => $submission ? $submission->get_posted_data( 'your-email' ) : '',
        );
        FlowPress_Runner::run( $this->get_type(), $payload );
    }
}
```

2. **Register the trigger** via the `flowpress_register_triggers` hook (from your own plugin):

```php
add_action( 'flowpress_register_triggers', function ( string $registry ): void {
    $registry::register( new FlowPress_Trigger_CF7_Submitted() );
} );
```

3. **Write tests** covering `get_tokens()` returns the expected format and that `handle()` calls `FlowPress_Runner::run()` with the correct payload.

---

## Writing a custom action

1. **Create the class file** at `includes/actions/class-flowpress-action-{slug}.php`:

```php
<?php
/**
 * Posts a message to a Slack channel.
 *
 * @package FlowPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowPress_Action_Send_Slack extends FlowPress_Abstract_Action {

    public function get_type(): string {
        return 'send_slack';
    }

    public function get_label(): string {
        return __( 'Send Slack Message', 'flowpress' );
    }

    public function get_description(): string {
        return __( 'Post a message to a Slack channel via an Incoming Webhook URL.', 'flowpress' );
    }

    public function get_icon(): string {
        return 'dashicons-format-chat';
    }

    public function get_fields(): array {
        return array(
            array(
                'key'         => 'webhook_url',
                'label'       => __( 'Slack Webhook URL', 'flowpress' ),
                'type'        => 'url',
                'required'    => true,
                'placeholder' => 'https://hooks.slack.com/services/…',
            ),
            array(
                'key'         => 'message',
                'label'       => __( 'Message', 'flowpress' ),
                'type'        => 'textarea',
                'required'    => true,
                'placeholder' => __( 'New post: {{post_title}} — {{post_url}}', 'flowpress' ),
            ),
        );
    }

    public function execute( array $config, array $payload ): FlowPress_Action_Result {
        $url     = esc_url_raw( $config['webhook_url'] ?? '' );
        $message = $config['message'] ?? '';

        if ( empty( $url ) || empty( $message ) ) {
            return FlowPress_Action_Result::failed( 'Missing webhook_url or message.' );
        }

        $response = wp_remote_post( $url, array(
            'body'        => wp_json_encode( array( 'text' => $message ) ),
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'timeout'     => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return FlowPress_Action_Result::failed( $response->get_error_message() );
        }

        return FlowPress_Action_Result::success( 'Message posted.' );
    }
}
```

2. **Register the action** via the `flowpress_register_actions` hook:

```php
add_action( 'flowpress_register_actions', function ( string $registry ): void {
    $registry::register( new FlowPress_Action_Send_Slack() );
} );
```

3. **Write tests** covering `execute()` returns a `success` result on a 200 response and a `failed` result on a WP_Error.

See [`docs/DEVELOPERS.md`](docs/DEVELOPERS.md) and [`example-integration/`](example-integration/) for the full API reference.

---

## Tests

```bash
# Run all tests
composer test

# Run a single test file
./vendor/bin/phpunit tests/test-runner.php

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

Tests live in `tests/` and extend `WP_UnitTestCase`. The CI matrix runs on PHP 7.4, 8.1, and 8.2 against the latest WordPress on every push and pull request.

---

## Questions?

Open a [GitHub Discussion](https://github.com/arunrajiah/flowpress/discussions) or file an issue. Happy to help new contributors get started.
