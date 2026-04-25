# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 0.1.x   | ✅ Yes    |

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email privately at **arunrajiah@gmail.com** with:

- A description of the vulnerability and its potential impact.
- Steps to reproduce or proof-of-concept code.
- Any suggested remediation.

### Response SLA

| Severity | Acknowledge | Fix target   |
|----------|-------------|--------------|
| Critical | 24 hours    | 7 days       |
| High     | 72 hours    | 30 days      |
| Medium   | 7 days      | 90 days      |
| Low      | 14 days     | Next release |

We will coordinate disclosure with you before publishing a fix.

## Security model

| Area | Implementation |
|---|---|
| Nonce verification | All form submissions verified with `check_admin_referer()` or `check_ajax_referer()` |
| Capability checks | Every admin action gated by `current_user_can( 'manage_options' )` |
| Input sanitisation | `sanitize_text_field()`, `sanitize_key()`, `absint()`, `sanitize_textarea_field()` per context |
| Output escaping | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()` used throughout |
| AJAX handlers | All endpoints verify nonce and capability before processing |
| Incoming webhooks | Unique per-recipe slugs; payload sanitised before processing |
| Post meta | Never writes raw `$_POST` values — all data passes through sanitisation |
