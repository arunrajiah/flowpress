# Contributing to FlowPress

Thank you for taking the time to contribute! FlowPress is a community project and every contribution — from a typo fix to a new feature — makes a difference.

Please read this guide before opening a pull request or issue. Following these guidelines helps maintainers and the community review and accept your contributions quickly.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [I have a question](#i-have-a-question)
3. [Reporting bugs](#reporting-bugs)
4. [Suggesting features](#suggesting-features)
5. [Your first contribution](#your-first-contribution)
6. [Pull request process](#pull-request-process)
7. [Coding standards](#coding-standards)
8. [Running tests locally](#running-tests-locally)
9. [Commit message style](#commit-message-style)

---

## Code of Conduct

This project is governed by the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating you agree to uphold it. Please report unacceptable behaviour to the project maintainers via a private GitHub message or at the email address listed in CODE_OF_CONDUCT.md.

---

## I have a question

Before opening an issue please:

1. Read the [README](README.md) and [readme.txt](readme.txt).
2. Search [existing issues](https://github.com/flowpress/flowpress/issues) — your question may already be answered.
3. Check the [GitHub Discussions](https://github.com/flowpress/flowpress/discussions) board.

If you still need help, open a new Discussion rather than an issue.

---

## Reporting Bugs

Use the **Bug Report** issue template. Please include:

- A **clear and descriptive title**.
- **Steps to reproduce** the problem — be as specific as possible.
- **Expected behaviour** — what you expected to happen.
- **Actual behaviour** — what actually happened, including any error messages.
- **Environment:** WordPress version, PHP version, active theme, list of active plugins.
- **Screenshots or screen recordings** if they help illustrate the problem.

> **Security vulnerabilities** should _not_ be reported via public GitHub issues.  
> Please email the maintainers privately or use [GitHub Security Advisories](https://github.com/flowpress/flowpress/security/advisories/new).

---

## Suggesting Features

Use the **Feature Request** issue template. Please:

- Check whether the feature has already been requested or is on the [roadmap](README.md#planned-feature-phases).
- Describe the problem your feature solves (not just the solution).
- Provide example use-cases so maintainers can evaluate impact.

---

## Your First Contribution

Looking for somewhere to start? Check the issues labelled:

- [`good first issue`](https://github.com/flowpress/flowpress/labels/good%20first%20issue) — well-scoped, beginner-friendly tasks.
- [`help wanted`](https://github.com/flowpress/flowpress/labels/help%20wanted) — tasks where maintainer bandwidth is limited.

You do not need to ask permission before working on a `good first issue`. Just comment on it to signal your intent so two people do not duplicate effort.

---

## Pull Request Process

1. **Fork** the repository and create your branch from `main`:
   ```bash
   git checkout -b feature/my-feature-name
   # or
   git checkout -b fix/issue-123-short-description
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Make your changes**, following the [coding standards](#coding-standards) below.

4. **Add or update tests** for any new behaviour. PRs that reduce test coverage will not be merged.

5. **Run checks locally** before pushing (see [Running tests locally](#running-tests-locally)):
   ```bash
   composer run cs
   composer run test
   ```

6. **Commit** with a [clear message](#commit-message-style).

7. **Push** your branch and **open a pull request** against `main`. Fill in the PR template — explain *what* changed and *why*.

8. Maintainers will review your PR. Please be patient and responsive to feedback. A review may take a few days.

9. Once approved, a maintainer will merge your PR. Squash merging is preferred to keep `git log` clean.

### PR acceptance criteria

- All CI checks pass (PHPCS + PHPUnit matrix).
- New public functions / classes have PHPDoc docblocks.
- No decrease in test coverage.
- No new third-party PHP dependencies introduced without prior discussion in an issue.
- `CHANGELOG.md` updated under `## [Unreleased]` if the change is user-facing.

---

## Coding Standards

FlowPress follows the **[WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)**.

### PHP

- **Indentation:** tabs (not spaces) — WordPress PHP standard.
- **Line endings:** Unix (`\n`).
- **PHP opening tag:** always `<?php` — never short tags.
- **Yoda conditions:** `if ( 'value' === $var )` not `if ( $var === 'value' )`.
- **Spaces inside parentheses:** `if ( $condition )` not `if($condition)`.
- **Docblocks:** every class, method, and property must have a PHPDoc comment with `@since`, `@param`, and `@return` as appropriate.
- **Escaping output:** always use `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`, etc. Never echo raw variables.
- **Sanitise input:** always use `sanitize_text_field()`, `absint()`, etc. when reading `$_GET`, `$_POST`, or options.
- **Nonces:** all forms and AJAX handlers must verify a nonce.
- **Prefix everything:** functions, classes, hooks, options, and globals must be prefixed with `flowpress_` or `FlowPress_` / `FlowPress\`.

Run the coding-standards checker:

```bash
composer run cs
```

To automatically fix fixable errors:

```bash
./vendor/bin/phpcbf --standard=phpcs.xml .
```

### File naming

- Class files: `class-{classname-in-lowercase-hyphenated}.php` (WordPress convention).
- No underscores in file names — use hyphens.

---

## Running Tests Locally

### Prerequisites

- PHP 7.4+
- Composer
- MySQL / MariaDB
- WP-CLI (optional but recommended)

### 1. Install Composer dependencies

```bash
composer install
```

### 2. Set up the WordPress test suite

The WordPress core test library must be cloned separately. Use the bundled install script (modelled on WP-CLI's scaffold):

```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Arguments:

| Argument | Description | Example |
|----------|-------------|---------|
| `$1` | Test database name | `wordpress_test` |
| `$2` | DB user | `root` |
| `$3` | DB password | `''` (empty) |
| `$4` | DB host | `localhost` |
| `$5` | WP version | `latest` |

> **Warning:** This script creates and drops the test database on every run. Do not point it at a database containing real data.

The script clones WordPress core and the test library into `/tmp/wordpress-tests-lib` and `/tmp/wordpress/` by default. Set `$WP_TESTS_DIR` to override.

### 3. Run PHPUnit

```bash
composer run test
# or directly:
./vendor/bin/phpunit
```

### 4. Run PHPCS

```bash
composer run cs
```

### Running a single test file

```bash
./vendor/bin/phpunit tests/test-plugin-activation.php
```

### Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `WP_TESTS_DIR` | `/tmp/wordpress-tests-lib` | Path to WP test library |
| `WP_CORE_DIR` | `/tmp/wordpress/` | Path to WP core install |
| `DB_NAME` | `wordpress_test` | Test database name |
| `DB_USER` | `root` | DB user |
| `DB_PASS` | _(empty)_ | DB password |
| `DB_HOST` | `localhost` | DB host |

---

## Commit Message Style

Follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) spec:

```
<type>(<scope>): <short summary>

[optional body]

[optional footer(s)]
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `ci`, `build`.

**Examples:**

```
feat(activator): enforce minimum PHP 7.4 on activation
fix(i18n): load text domain on plugins_loaded instead of init
docs(readme): add Composer installation instructions
test(activation): assert FLOWPRESS_VERSION constant is defined
chore(ci): add PHP 8.2 to PHPUnit matrix
```

- Keep the summary line under 72 characters.
- Use the imperative mood: "add feature" not "added feature".
- Reference issues in the footer: `Closes #42` or `Fixes #17`.
