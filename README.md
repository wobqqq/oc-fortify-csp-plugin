# CSP

[![CI](https://github.com/wobqqq/oc-fortify-csp-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/oc-fortify-csp-plugin/actions/workflows/ci.yml)
[![October CMS](https://img.shields.io/badge/October%20CMS-3.x%20%7C%204.x-e24848)](https://octobercms.com/plugin/wobqqq-fortifycsp)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](phpstan.neon.dist)
[![License](https://img.shields.io/badge/License-Commercial-orange)](LICENSE.md)

**CSP** adds Content Security Policy (CSP) headers to your application to prevent XSS and data injection attacks.

This plugin is part of the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) ecosystem and extends its security capabilities.

## 📊 Security Dashboard Widget

Fortify includes a built-in dashboard widget that gives you a real-time overview of your system’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Easy CSP configuration
- Protection against XSS attacks
- Control allowed sources (scripts, styles, images, etc.)
- Improves browser-side security

## 🔗 Related Plugins

- [Fortify](https://octobercms.com/plugin/wobqqq-fortify) – comprehensive security suite
- [Admin IP Access](https://octobercms.com/plugin/wobqqq-fortifyadminipaccess) – restrict admin panel access by IP
- [IP Blocker](https://octobercms.com/plugin/wobqqq-fortifyipblocker) – manually block specific IP addresses
- [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) – automatic IP blocking based on request rate
- [Input Sanitizer](https://octobercms.com/plugin/wobqqq-fortifyinputsanitizer) – block and sanitize malicious input

## 📦 Requirements

- PHP 8.2 or higher
- October CMS 3.x or 4.x
- [Fortify](https://octobercms.com/plugin/wobqqq-fortify)

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **CSP**. Configure allowed sources as needed.

**Console Commands:**

- Disable CSP module:
```bash
php artisan wobqqq.fortify:csp:disable
```

## ⬆️ Upgrading

- **1.0.3** — a directive value must be a single source expression (such as `'self'`, `https://cdn.example.com` or `data:`); a stored value containing `;`, `,`, spaces or control characters is no longer sent, since it could add directives or break the header. The directives are separated by `; `. Saved settings take effect at once.

## ⚠️ Good to know

- The default policy allows `'unsafe-inline'` and any `https:` source so that an existing site keeps working. Tighten it (nonces or hashes instead of `'unsafe-inline'`, the hosts you use instead of `https:`) and check the browser console for blocked resources before relying on it.
- Another middleware, the web server or a CDN may set or override `Content-Security-Policy`; keep one place in charge of the header.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. The module is tested together with the [Fortify core](https://github.com/wobqqq/oc-fortify-plugin), which Composer installs from its `main` branch.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, YAML lint, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
```

Every pull request runs the same checks on GitHub Actions, plus a syntax check on PHP 8.2 and a run against the latest core. Pushing a tag that matches the last version in `updates/version.yaml` releases it to the October CMS marketplace once CI has passed.

