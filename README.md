# ownCloud Gallery

<!-- OSPO-managed README | Generated: 2026-04-16 | v2 -->

[![License](https://img.shields.io/badge/License-AGPL--3.0-blue.svg)](COPYING) [![ownCloud OSPO](https://img.shields.io/badge/OSPO-ownCloud-blue)](https://kiteworks.com/opensource) [![Docker Hub](https://img.shields.io/docker/pulls/owncloud)](https://hub.docker.com/r/owncloud/server)

A media gallery app for ownCloud Classic (OC10) that provides a dedicated grid view of all images, adds image viewing capabilities to the files app, and adds a gallery view to public links. It supports a wide range of media types (depending on server configuration), album-level customization with per-folder design, descriptions, and copyright statements, and features such as drag-and-drop upload, zoomable fullscreen previews, and sorting by name or date.

## Getting Started

Enable the app in the ownCloud admin panel:

```bash
sudo -u www-data php occ app:enable gallery
```

Navigate to the Gallery view from the ownCloud navigation menu. The app supports per-album configuration via a `.gallery` YAML file in each folder.

## Documentation

- [ownCloud Server Admin Manual](https://doc.owncloud.com/server/latest/admin_manual/)
- [Gallery Changelog](CHANGELOG.md)

## Features

- Support for a wide selection of media types (depending on server setup)
- Upload and organise images and albums directly from the app
- Large, zoomable previews with fullscreen mode
- Sort images by name or date added
- Per-album design, description, and copyright statement via `.gallery` YAML files
- Image download from slideshow or gallery view
- Switch between Gallery and Files views from any folder
- Ignore folders containing a `.nomedia` file
- Browser rendering of SVG images (disabled by default)
- Mobile support

### Enabling Additional Media Types

Install ImageMagick with the imagick PECL extension, then add preview providers to `config/config.php`:

```php
'preview_max_scale_factor' => 1,
'enabledPreviewProviders' => [
    'OC\\Preview\\PNG',
    'OC\\Preview\\JPEG',
    'OC\\Preview\\GIF',
    'OC\\Preview\\Illustrator',
    'OC\\Preview\\Postscript',
    'OC\\Preview\\Photoshop',
    'OC\\Preview\\TIFF',
],
```

### Performance Tips

- **Redis for file locking** -- improves album loading performance by up to 10x. See the [ownCloud Admin Manual](https://doc.owncloud.com/server/next/admin_manual/configuration/files/files_locking_transactional.html).
- **Asset pipelining** -- enable to combine JS and CSS resources, reducing load time. See the [ownCloud Admin Manual](https://doc.owncloud.com/server/next/admin_manual/configuration/server/).

### Uninstalling

When disabling/uninstalling, existing gallery link shares will stop working. Add an `.htaccess` rewrite rule to redirect gallery-style links to regular public links:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteRule ^/apps/gallery/s/(.*)$ /s/$1 [L,R=301]
</IfModule>
```

## Part of ownCloud Classic (OC10)

This app extends [ownCloud Server](https://github.com/owncloud/core) with gallery functionality. It is shipped as part of the [ownCloud Server Docker image](https://hub.docker.com/r/owncloud/server).

## Community & Support

**[Star](https://github.com/owncloud/gallery)** this repo and **Watch** for release notifications!

- [ownCloud Website](https://owncloud.com)
- [Community Discussions](https://github.com/orgs/owncloud/discussions)
- [Matrix Chat](https://app.element.io/#/room/#owncloud:matrix.org)
- [Documentation](https://doc.owncloud.com)
- [Enterprise Support](https://owncloud.com/contact-us/)
- [OSPO Home](https://kiteworks.com/opensource)

## Contributing

We welcome contributions! Please read the [Contributing Guidelines](CONTRIBUTING.md)
and our [Code of Conduct](CODE_OF_CONDUCT.md) before getting started.

### Workflow

- **Rebase Early, Rebase Often!** We use a rebase workflow. Always rebase on the target branch before submitting a PR.
- **Dependabot**: Automated dependency updates are managed via Dependabot. Review and merge dependency PRs promptly.
- **Signed Commits**: All commits **must** be PGP/GPG signed. See [GitHub's signing guide](https://docs.github.com/en/authentication/managing-commit-signature-verification).
- **DCO Sign-off**: Every commit must carry a `Signed-off-by` line:
  ```
  git commit -s -S -m "your commit message"
  ```
- **GitHub Actions Policy**: Workflows may only use actions that are (a) owned by `owncloud`, (b) created by GitHub (`actions/*`), or (c) verified in the GitHub Marketplace.

## Security

**Do not open a public GitHub issue for security vulnerabilities.**

Report vulnerabilities at **<https://security.owncloud.com>** -- see [SECURITY.md](SECURITY.md).

Bug bounty: [YesWeHack ownCloud Program](https://yeswehack.com/programs/owncloud-bug-bounty-program)

## License

This project is licensed under the [AGPL-3.0](COPYING).

## About the ownCloud OSPO

The [Kiteworks Open Source Program Office](https://kiteworks.com/opensource), operating under
the [ownCloud](https://owncloud.com) brand, launched on May 5, 2026, to steward the open source
ecosystem around ownCloud's products. The OSPO ensures transparent governance, license compliance,
community health, and sustainable collaboration between the open source community and
[Kiteworks](https://www.kiteworks.com), which acquired ownCloud in 2023.

- **OSPO Home**: <https://kiteworks.com/opensource>
- **GitHub**: <https://github.com/owncloud>
- **ownCloud**: <https://owncloud.com>

For questions about the OSPO or licensing, contact ospo@kiteworks.com.

### License Migration to Apache 2.0

The OSPO is driving a strategic relicensing of ownCloud repositories toward the
[Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0), following
the [Apache Software Foundation's third-party license policy](https://www.apache.org/legal/resolved.html).

Individual repositories will migrate as their audit is completed. The LICENSE file
in each repo reflects its **current** license status (not the target).

**Current license: AGPL-3.0** (Category X per Apache policy -- cannot be included in Apache-2.0 works).

Migration prerequisites for this repository:

- **CLA/DCO coverage**: All past contributors must have signed agreements permitting relicensing
- **Copyleft dependency audit**: All AGPL/GPL dependencies must be replaced or isolated
- **KDE heritage review**: Any code with KDE-era copyrights requires legal analysis
- **Complete relicensing**: AGPL-3.0 is a strong copyleft license; migration requires full relicensing of all files, not just a header change
