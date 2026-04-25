=== FlowPress — Automation for WordPress ===
Contributors:      flowpresscontributors
Tags:              automation, workflow, triggers, actions, email
Requires at least: 5.9
Tested up to:      6.7
Requires PHP:      7.4
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Free, open-source, on-site automation for WordPress. Build "when X happens, do Y" workflows — no third-party cloud required.

== Description ==

**FlowPress** brings native automation to WordPress. Build recipes that say "when X happens, do Y" — entirely on your own server. No monthly fees, no data leaving your site, no vendor lock-in.

= What is a Recipe? =

A **recipe** connects a **trigger** (something that happens on your site) to one or more **actions** (things FlowPress does in response). You can also add optional **conditions** to run the recipe only when certain criteria are met.

**Example:**
> When a **post is published** → if `post_title` **contains** "announcement" → **send an email** to `admin@example.com`

= Included Triggers =

* Post Published
* Comment Posted
* User Registered
* User Role Changed
* Incoming Webhook (fire a recipe from any external service)
* WooCommerce Order Placed *(requires WooCommerce)*

= Included Actions =

* Send Email (via `wp_mail`)
* Send Outbound Webhook (HTTP POST/GET/PUT with optional HMAC signature)
* WooCommerce Create Coupon *(requires WooCommerce)*
* WooCommerce Add Order Note *(requires WooCommerce)*

= Conditions =

Filter when a recipe fires using AND/OR logic across any token the trigger exposes. Supported operators: equals, not equals, contains, not contains, starts with, ends with, greater than, less than, is set, is not set.

= Token Placeholders =

Use `{{token}}` syntax inside any action field to insert live values from the trigger. Click the **{ }** button in the builder to browse and insert available tokens.

= Reliability =

Failed actions are automatically retried up to 4 times using exponential back-off (60 s → 5 min → 30 min). Every run is logged with its full payload and results.

= Extensibility =

Add your own triggers and actions from any plugin using two WordPress action hooks:

`add_action( 'flowpress_register_triggers', function( $r ) { $r::register( new My_Trigger() ); } );`
`add_action( 'flowpress_register_actions',  function( $r ) { $r::register( new My_Action() ); } );`

See `example-integration/` and `docs/DEVELOPERS.md` for a complete walkthrough.

= Privacy =

FlowPress stores run logs and recipe data exclusively in your WordPress database. No data is sent to external servers except by actions you explicitly configure (e.g. "Send Outbound Webhook").

== Installation ==

1. Upload the `flowpress` folder to `/wp-content/plugins/`, or install via **Plugins > Add New**.
2. Activate the plugin through the **Plugins** menu.
3. Navigate to **FlowPress** in the WordPress admin sidebar.
4. Click **Add Recipe** and build your first automation.

== Frequently Asked Questions ==

= Does FlowPress require an account or API key? =

No. FlowPress is entirely self-hosted. There is no cloud service, no account, and no API key required.

= Will it slow down my site? =

Recipes run asynchronously via WP-Cron retries. The initial execution happens inline on the triggering request, but is designed to be fast. For high-traffic sites, consider using a real cron job instead of WP-Cron.

= Can I use FlowPress without WooCommerce? =

Yes. WooCommerce triggers and actions are only registered when WooCommerce is active. All other functionality works independently.

= Can I undo a run? =

Recipe runs are not automatically reversible (for example, an email that was sent cannot be unsent). You can review all runs in **FlowPress > Runs** and re-run failed executions.

= How do I add my own trigger or action? =

See `docs/DEVELOPERS.md` or the `example-integration/` directory bundled with the plugin for a fully annotated example.

= Is my data removed when I uninstall the plugin? =

Yes. Uninstalling via **Plugins > Delete** removes all FlowPress database tables and options. Deactivating without deleting preserves your data so you can reactivate later.

= Where are bugs tracked? =

Please open an issue on [GitHub](https://github.com/flowpress/flowpress/issues).

== Screenshots ==

1. The visual recipe builder — choose a trigger, add conditions, then configure actions.
2. The runs dashboard — view status, payload, and results for every execution.
3. The recipe list — enable, disable, duplicate, or delete recipes at a glance.

== Changelog ==

= 0.1.0 =
* Initial release.
* Visual recipe builder with trigger catalogue and multi-action blocks.
* Triggers: Post Published, Comment Posted, User Registered, User Role Changed, Incoming Webhook, WooCommerce Order Placed.
* Actions: Send Email, Outbound Webhook, WooCommerce Create Coupon, WooCommerce Add Order Note.
* AND/OR condition evaluator with 10 operators.
* Automatic retry queue with exponential back-off (up to 4 attempts).
* Full run log and audit history per recipe.
* Public extension API with documented hooks and example integration plugin.

== Upgrade Notice ==

= 0.1.0 =
Initial release — no upgrade steps required.
