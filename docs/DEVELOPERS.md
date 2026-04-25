# FlowPress Developer Guide

FlowPress is designed to be extended. Any WordPress plugin can register custom **triggers** and **actions** using two simple hooks. No forking required.

---

## Table of Contents

1. [Extension Hooks](#extension-hooks)
2. [Creating a Custom Trigger](#creating-a-custom-trigger)
3. [Creating a Custom Action](#creating-a-custom-action)
4. [Token System](#token-system)
5. [Placeholder Resolution](#placeholder-resolution)
6. [Action Results](#action-results)
7. [Incoming Webhook Trigger](#incoming-webhook-trigger)
8. [Full Example Plugin](#full-example-plugin)

---

## Extension Hooks

FlowPress fires two action hooks during `init` (priority 20). Hook in at priority 10 or earlier.

```php
// Register your custom trigger(s).
add_action( 'flowpress_register_triggers', function ( string $registry ) {
    $registry::register( new My_Custom_Trigger() );
} );

// Register your custom action(s).
add_action( 'flowpress_register_actions', function ( string $registry ) {
    $registry::register( new My_Custom_Action() );
} );
```

`$registry` is the fully-qualified class name of `FlowPress_Trigger_Registry` or `FlowPress_Action_Registry`. Calling `::register()` on it is the only API you need.

---

## Creating a Custom Trigger

Extend `FlowPress_Abstract_Trigger` and implement every abstract method.

```php
class My_Custom_Trigger extends FlowPress_Abstract_Trigger {

    // Unique machine-readable identifier. Use a prefix to avoid collisions.
    public function get_type(): string { return 'myplugin_my_event'; }

    // Human-readable label shown in the builder catalogue.
    public function get_label(): string { return 'My Custom Event'; }

    // One-sentence description shown under the label.
    public function get_description(): string { return 'Fires when my event happens.'; }

    // A dashicon class name: https://developer.wordpress.org/resource/dashicons/
    public function get_icon(): string { return 'dashicons-star-filled'; }

    /**
     * Declare the tokens this trigger exposes.
     * Each entry is ['token' => '<slug>', 'label' => '<Human Label>'].
     * Token slugs are used in {{token}} placeholders inside action configs.
     */
    public function get_tokens(): array {
        return [
            [ 'token' => 'my_field',  'label' => 'My Field' ],
            [ 'token' => 'my_value',  'label' => 'My Value' ],
        ];
    }

    /**
     * Example payload used in the "Test Recipe" dry run.
     * Keys must match the token slugs declared above.
     */
    public function get_sample_payload(): array {
        return [
            'my_field' => 'example-field',
            'my_value' => 'example-value',
        ];
    }

    /**
     * Attach the WordPress hook(s) that fire this trigger.
     * Called once, during 'init'.
     */
    public function attach(): void {
        add_action( 'my_plugin_event', [ $this, 'handle' ] );
    }

    /**
     * Your hook callback. Build the payload and call FlowPress_Runner::run().
     *
     * @param mixed $event_data Whatever your action passes.
     */
    public function handle( $event_data ): void {
        FlowPress_Runner::run(
            $this->get_type(),
            [
                'my_field' => $event_data->field ?? '',
                'my_value' => $event_data->value ?? '',
            ]
        );
    }
}
```

---

## Creating a Custom Action

Extend `FlowPress_Abstract_Action` and implement every abstract method.

```php
class My_Custom_Action extends FlowPress_Abstract_Action {

    public function get_type(): string { return 'myplugin_my_action'; }
    public function get_label(): string { return 'My Custom Action'; }
    public function get_description(): string { return 'Does something useful.'; }
    public function get_icon(): string { return 'dashicons-controls-forward'; }

    /**
     * Declare the configuration fields shown in the recipe builder.
     *
     * Supported field types: 'text', 'textarea', 'select', 'email'.
     * Set 'tokens' => true to show the {{token}} insert button beside the field.
     *
     * For 'select' fields, provide 'options' => [ 'value' => 'Label', ... ].
     */
    public function get_fields(): array {
        return [
            [
                'key'         => 'message',
                'label'       => 'Message',
                'type'        => 'textarea',
                'placeholder' => 'Hello, {{my_field}}!',
                'tokens'      => true,
            ],
            [
                'key'     => 'channel',
                'label'   => 'Channel',
                'type'    => 'select',
                'options' => [
                    'general' => '#general',
                    'alerts'  => '#alerts',
                ],
            ],
        ];
    }

    /**
     * Short plain-text summary used in the recipe summary sidebar.
     * Should complete the sentence "This recipe will…".
     */
    public function get_summary( array $config ): string {
        return 'do my custom action';
    }

    /**
     * Execute the action.
     *
     * @param array $config  The saved field values from the builder.
     * @param array $payload The trigger payload (token values).
     * @param bool  $dry_run True during "Test Recipe" — validate but don't act.
     * @return FlowPress_Action_Result
     */
    public function execute( array $config, array $payload, bool $dry_run ): FlowPress_Action_Result {
        // 1. Resolve {{tokens}} in config values.
        $message = FlowPress_Placeholder::resolve( $config['message'] ?? '', $payload );
        $channel = $config['channel'] ?? 'general';

        // 2. Validate required fields.
        if ( empty( $message ) ) {
            return FlowPress_Action_Result::failed( 'Message is required.' );
        }

        // 3. Skip execution on dry runs — return a skipped result.
        if ( $dry_run ) {
            return FlowPress_Action_Result::skipped( "Dry run — would post to #{$channel}." );
        }

        // 4. Perform the real work.
        $ok = my_plugin_post_to_channel( $channel, $message );

        // 5. Return success or failure.
        return $ok
            ? FlowPress_Action_Result::success( "Posted to #{$channel}." )
            : FlowPress_Action_Result::failed( "Failed to post to #{$channel}." );
    }
}
```

---

## Token System

Tokens are `{{snake_case}}` placeholders that get replaced with live values from the trigger payload at execution time.

- Declare available tokens in `get_tokens()` — these appear in the builder's token-insert dropdown.
- Token slugs must be lowercase alphanumeric + underscores (`[a-zA-Z0-9_]+`).
- Unknown tokens in a config string are left unchanged, so they won't cause errors.
- The helper `FlowPress_Placeholder::extract_tokens( $text )` returns an array of token slugs found in a string.

---

## Placeholder Resolution

```php
$text    = 'Hello, {{user_name}}! Your order #{{order_id}} is ready.';
$payload = [ 'user_name' => 'Jane', 'order_id' => '42' ];

$resolved = FlowPress_Placeholder::resolve( $text, $payload );
// → 'Hello, Jane! Your order #42 is ready.'
```

Call `FlowPress_Placeholder::resolve()` on every config value inside your `execute()` method before using it.

---

## Action Results

Always return one of the three static factories from `execute()`:

| Factory | When to use |
|---|---|
| `FlowPress_Action_Result::success( $message, $data = [] )` | Action completed successfully |
| `FlowPress_Action_Result::failed( $message )` | Action failed (will trigger retry if configured) |
| `FlowPress_Action_Result::skipped( $message )` | Action intentionally not run (e.g. dry run, missing data) |

The `$message` string is stored in the run log and shown in the Runs dashboard.

---

## Incoming Webhook Trigger

To fire a recipe from an external service, select the **Incoming Webhook** trigger in the builder and set a unique slug. The endpoint is:

```
POST /wp-json/flowpress/v1/webhook/{your-slug}
Content-Type: application/json

{ "event": "my_event", "value": "42" }
```

FlowPress will automatically make top-level scalar fields from the JSON body available as `body_*` tokens (e.g. `{{body_event}}`, `{{body_value}}`).

No authentication is enforced by default — use your server's firewall or a secret-in-URL approach if needed.

---

## Full Example Plugin

See `example-integration/flowpress-example-integration.php` in the FlowPress plugin directory for a complete, working example that registers a custom CF7 trigger and a Slack action.

---

## Filter Reference

| Hook | Type | Description |
|---|---|---|
| `flowpress_register_triggers` | `do_action( $registry_class )` | Register custom triggers |
| `flowpress_register_actions` | `do_action( $registry_class )` | Register custom actions |
| `flowpress_before_run` | *(planned)* | Fires before a recipe is executed |
| `flowpress_after_run` | *(planned)* | Fires after a recipe is executed |

---

*FlowPress is GPL-2.0-or-later. See [LICENSE](../LICENSE) for details.*
