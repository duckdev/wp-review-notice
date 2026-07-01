<p align="center">
<a href="http://duckdev.com" target="_blank">
    <img width="200px" src="https://duckdev.com/wp-content/uploads/2020/12/cropped-duckdev-logo-mid.png">
</a>
</p>

# WP Review Notice

[![Tests](https://github.com/duckdev/wp-review-notice/actions/workflows/tests.yml/badge.svg)](https://github.com/duckdev/wp-review-notice/actions/workflows/tests.yml)
[![PHPCS](https://github.com/duckdev/wp-review-notice/actions/workflows/phpcs.yml/badge.svg)](https://github.com/duckdev/wp-review-notice/actions/workflows/phpcs.yml)

A small, opinionated WordPress library that gently asks for a wp.org plugin review after a few days of usage. Built around a tiny set of focused, swappable collaborators so it stays trivially testable and easy to extend.

## Installation

```bash
composer require duckdev/wp-review-notice
```

Requires **PHP 7.4+** and WordPress **6.0+**.

## Quick start

```php
add_action( 'plugins_loaded', function () {
    \DuckDev\Reviews\Notice::create(
        'my-plugin', // wp.org plugin slug (e.g. "hello-dolly").
        'My Plugin', // Display name shown in the notice copy.
        array(
            'days'    => 7,
            'cap'     => 'manage_options',
            'screens' => array( 'dashboard', 'plugins' ), // empty = all admin screens.
        )
    )->register();
} );
```

`register()` is what hooks `admin_notices` + `admin_init` and seeds the show-time schedule. Calling `create()` without `register()` does nothing — useful for tests or for configuring a notice you want to render manually.

## Options

| Key             | Type     | Default               | Description                                                              |
| --------------- | -------- | --------------------- | ------------------------------------------------------------------------ |
| `days`          | `int`    | `7`                   | Days to wait before showing the notice for the first time.               |
| `screens`       | `array`  | `[]`                  | Allowed admin screen IDs. Empty = every admin screen.                    |
| `cap`           | `string` | `manage_options`      | Capability required to see and act on the notice.                        |
| `classes`       | `array`  | `[]`                  | Extra CSS classes appended to the `notice notice-info` wrapper.          |
| `domain`        | `string` | `duckdev`             | Text domain used for the bundled copy.                                   |
| `message`       | `string` | `''` (auto-generated) | Custom HTML message. Not escaped — sanitise it yourself.                 |
| `action_labels` | `array`  | bundled labels        | Keys: `review`, `later`, `dismiss`. Set any to `''` to hide that link.   |
| `prefix`        | `string` | slug with `-` → `_`   | Storage namespace. Keys are written as `{prefix}_review_{key}`.          |

## Filters

```php
add_filter( 'duckdev_reviews_notice_message', function ( $message, $days ) {
    return "We're glad you've been with us for {$days}+ days!";
}, 10, 2 );
```

## Architecture

The library is split into small collaborators, each behind an interface:

```
Notice (facade, DI container)
 ├── KeyPrefixer           — "{prefix}_review_{key}" namespacing
 ├── TimerStoreInterface   — when the next show is due  (default: SiteOptionTimerStore)
 ├── DismissalStoreInterface — per-user dismissal flag  (default: UserMetaDismissalStore)
 ├── ScreenResolverInterface  — current admin screen check
 ├── CapabilityCheckerInterface — capability gate
 ├── ActionRouter          — handles later/dismiss GET dispatch
 └── RendererInterface     — emits the notice HTML       (default: DefaultRenderer)
```

Every collaborator is constructor-injectable, so tests (and unusual integrations) can swap any single piece without forking the library.

### Custom storage / rendering

```php
use DuckDev\Reviews\Notice;
use DuckDev\Reviews\Support\Config;

$notice = new Notice(
    Config::fromArray( 'my-plugin', 'My Plugin' ),
    new MyRedisTimerStore(),       // implements TimerStoreInterface
    null,                          // default user-meta dismissal
    null,                          // default admin-screen resolver
    null,                          // default capability checker
    new MyBlockEditorRenderer()    // implements RendererInterface
);
$notice->register();
```

## Upgrading from v1

v2 is a clean break — there is no compatibility shim.

| v1                                              | v2                                                          |
| ----------------------------------------------- | ----------------------------------------------------------- |
| `Notice::get( $slug, $name, $opts )`            | `Notice::create( $slug, $name, $opts )->register()`         |
| Constructor auto-registered on `is_admin()`     | `register()` is explicit                                    |
| Storage keys `{prefix}_reviews_time` etc.       | `{prefix}_review_time`, `{prefix}_review_dismissed`, `{prefix}_review_action` |
| Message echoed raw                              | Message run through `wp_kses_post()`                        |
| `is_time()` mutated storage                     | `isDue()` is a pure read; `start()` seeds on `register()`   |

Existing v1 schedules will be ignored after upgrade (different option key), so users will see the prompt again on the new schedule. If that matters, write a tiny migration in your plugin to rename the option once.

## Development

```bash
composer install
composer test    # PHPUnit
composer phpcs   # WPCS lint
```

CI runs PHPUnit against PHP 7.4 – 8.3 and WPCS on every push.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
