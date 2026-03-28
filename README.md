# CSP

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
- October CMS 3.0 or higher

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **CSP**. Configure allowed sources as needed.

**Console Commands:**

- Disable CSP module:
```bash
php artisan wobqqq.fortify:csp:disable
