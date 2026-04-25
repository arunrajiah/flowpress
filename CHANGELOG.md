# Changelog

All notable changes to FlowPress will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

_(Nothing yet.)_

---

## [0.1.0] — 2026-04-24

Full initial release across 8 development phases.

### Added

**Foundation (Phase 1)**
- Plugin header, constants (`FLOWPRESS_VERSION`, `FLOWPRESS_PLUGIN_DIR`, etc.)
- Activation/deactivation/uninstall hooks with environment requirement checks (PHP 7.4+, WP 5.9+)
- Singleton orchestrator class, i18n loader, POT file
- Composer config, PHPCS ruleset, PHPUnit config, GitHub Actions CI
- Issue/PR templates, EditorConfig, `.gitignore`

**Recipe Management (Phase 2)**
- `flowpress_recipe` custom post type with `fp_enabled` / `fp_disabled` statuses
- Recipe model (`FlowPress_Recipe`) wrapping WP_Post with `create()`, `update()`, `duplicate()`, `delete()`
- `FlowPress_Audit_Log` custom table — per-recipe history of every admin action
- Admin list table (`FlowPress_Recipes_List_Table`) with status filters, row actions, bulk enable/disable/delete

**Automation Engine (Phase 3)**
- `FlowPress_Runner` — finds matching recipes, evaluates conditions, executes actions in order
- `FlowPress_Placeholder` — resolves `{{token}}` placeholders from trigger payload
- `FlowPress_Action_Result` — value object with `success`, `failed`, `skipped` statuses
- `FlowPress_Run_Log` custom table — stores full payload and per-action results
- Abstract base classes for triggers and actions; trigger + action registries with WordPress hooks

**Visual Builder (Phase 4)**
- Searchable trigger catalogue and multi-action blocks in a two-column layout
- Token-insert dropdown with keyboard navigation
- Live recipe summary sidebar, inline validation, dry-run test button
- `FlowPress_Trigger_Post_Published` and `FlowPress_Action_Send_Email` as the first bundled pair

**Conditions & Reliability (Phase 5)**
- `FlowPress_Condition_Evaluator` — AND/OR logic with 10 operators across any trigger token
- `FlowPress_Retry_Queue` — WP-Cron based exponential back-off (60 s → 5 min → 30 min, max 4 attempts)
- Conditions UI in builder (field/operator/value rows, logic selector)
- Runs dashboard with status filters and per-row re-run button

**Core Integration Pack (Phase 6)**
- Triggers: Comment Posted, User Registered, User Role Changed, WooCommerce Order Placed, Incoming Webhook (REST endpoint)
- Actions: Outbound Webhook (HMAC-signed), WooCommerce Create Coupon, WooCommerce Add Order Note
- `FlowPress_Incoming_Webhook` REST handler — `POST /wp-json/flowpress/v1/webhook/{slug}`
- WooCommerce integrations conditionally registered only when WooCommerce is active

**Extensibility (Phase 7)**
- `flowpress_register_triggers` and `flowpress_register_actions` action hooks documented in `flowpress-hooks.php`
- `docs/DEVELOPERS.md` — full extension API guide
- `example-integration/` — standalone example plugin with CF7 trigger and Slack action

**Release (Phase 8)**
- `readme.txt` in WordPress.org format
- `README.md` for GitHub
- `.distignore` for release ZIP generation
- `CHANGELOG.md` updated to reflect all phases

[Unreleased]: https://github.com/flowpress/flowpress/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/flowpress/flowpress/releases/tag/v0.1.0
