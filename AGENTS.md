# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Junie, Cursor) working in this repository.

## What this is

**Fortify CSP** (`Wobqqq.FortifyCsp`) is a free module of the Fortify security suite for October CMS 3.x/4.x (built and tested against 4.4 on Laravel 12, PHP 8.2+). It sends a `Content-Security-Policy` header, built from twelve directives the administrator edits on the settings page, with every response of the site's front end (`cms.middleware_group`); the backend is left alone.

It requires the core plugin [`Wobqqq.Fortify`](https://github.com/wobqqq/oc-fortify-plugin): the settings live in the core's `Wobqqq\Fortify\Models\Fortify` record under the `csp` key and appear on **Settings → Fortify**, and the module draws its own item on the core's dashboard widget.

This is a **security product installed on production sites**. A bug here locks administrators or visitors out, or silently leaves a site unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP.

```bash
make install        # composer install inside the php container (the core comes from Packagist, as `wobqqq/fortify-plugin`)
make code.fix       # composer normalize, rector, php-cs-fixer
make code.check     # validate, normalize --dry-run, audit, php -l, yaml-lint, cs, rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, fails below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories reported by `composer audit` are fixed by updating the package, never ignored.

## How the code is laid out

| Path | Holds |
|------|-------|
| `Plugin.php` | Wiring: the console command, the listener, the middleware. |
| `services/CspService.php` | The directives, the source-expression rule every value must match, the middleware registration, `disable()`. |
| `http/middlewares/CspMiddleware.php` | Sets the header on every front-end response while the policy is enabled and not empty. |
| `transformers/FortifyTransformer.php` | Builds the header from the stored directives, dropping empty, repeated and invalid values. |
| `cache/, instances/` | The cached policy (cleared on every settings save) and its per-request memo. |
| `listeners/FortifyListener.php` | The settings form fields, their validation rules, the default policy, the dashboard item, the cache clearing. |
| `controllers/fortify/_csp_description.htm` | The explanation shown above the directives on the settings page. |
| `updates/version.yaml` | The version history the marketplace reads from `main`. |

### Working with the core

- The core is a separate plugin that sites update on their own schedule. Use only the core's public API (listed in the core's AGENTS.md: the `Fortify` settings model, `FortifyEvent`, `View`, `WidgetItemColor`, the widget DTOs and `FortifyTransformer::widget*Dto()`, `BasicCache::cacheKey()`/`TTL`). A new core API is used only behind a check (`method_exists`, `enum_exists`) with a fallback, so the module keeps working on every released core.
- Settings are validated by rules the module adds to the core model. Add them in `Fortify::extend()` **and** when the settings form is built: the settings instance may exist before the module extends the model.
- Caches are cleared on the `eloquent.saved` / `eloquent.deleted` events of the core model, never with `bindEvent()` on an instance, for the same reason.

## Upgrading installed sites safely

Read the `plugin-upgrades` skill before changing anything that reaches a site that already runs the module: a new version in `updates/version.yaml` for every shipped change, an update script for every change to what is stored, a new cache key for every change to a cached object's shape, and defaults that cannot lock anyone out.

## Security rules (always)

Read the `fortify-security` skill for the full checklist. For this module in particular:

- A directive value is a single source expression: `CspService::SOURCE_PATTERN` refuses `;`, `,`, whitespace and control characters, which would add a directive or split the header. The rule is checked when the settings are saved **and** by the transformer, since a stored record may predate it.
- The header replaces any policy the response already has, on every front-end response (pages, files, streams). Only the front end: the backend needs its own scripts.
- The default policy allows `'unsafe-inline'` and `https:` so that a site keeps working when the module is switched on. Never make it looser; a stricter default is an update script, not a changed default.
- Every request runs the middleware: it reads the cached header, nothing else.

Recovery from the console, for an administrator who locked themselves out:

- `php artisan wobqqq.fortify:csp:disable` — turns the policy off, for a site whose pages it broke.

## Tests

Pest 4 on Orchestra Testbench (Laravel 12) with the real `october/rain` and the core plugin from Composer. The licensed October modules are not installable in CI, so `tests/Stubs/October.php` reproduces the classes the plugins touch with October 4.4's behaviour (keep it identical to the core's copy). `tests/TestCase.php` boots the core and the module like October does. Read the `plugin-testing` skill.

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for sites that upgrade);
  4. merge only once CI is green, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (the `plugin-upgrades` skill says how); the tag is the only thing pushed outside a pull request.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via php-cs-fixer (`(int)$x` without a space, imported classes).
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- DTOs are `final readonly`; services and transformers are `final`.
- October patterns over Laravel ones: model validation, form fields added in `backend.form.extendFields`, `Plugin.php` registration, the core's `lang` keys (read the `octobercms-*` skills).
- Commits: imperative subject saying what the change does for the site, a body with the why.
