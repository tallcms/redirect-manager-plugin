# TallCMS Redirect Manager

A [TallCMS](https://tallcms.com) plugin for managing 301/302 redirects directly from the admin panel. Essential for site migrations, fixing broken links, and SEO hygiene.

## Features
- Create, edit, and delete redirects from the TallCMS admin panel
- 301 (permanent) and 302 (temporary) status codes
- Toggle redirects active/inactive without deleting
- Hit counter and last-hit timestamp for each redirect
- Bulk activate, deactivate, and delete
- Cached redirect map for fast lookups (1-hour TTL, auto-invalidated on changes)
- Global middleware — intercepts requests before route resolution

## Requirements

- TallCMS Core or Pro
- PHP 8.2+
- Laravel 11+

## Installation

### Via Plugin Package Command (recommended)

If you have the plugin source in your `plugins/` directory (e.g., from cloning the repo):

```bash
# Package the plugin into a clean ZIP
php artisan plugin:package redirect-manager

# Then upload the ZIP via Admin > System > Plugins
```

This creates a flat, validator-compliant ZIP — no `.DS_Store`, no nested directories, no development files. Migrations run automatically on install.

> **Note:** GitHub release ZIPs won't work directly because they nest files inside a subdirectory. Always use `plugin:package` or `git archive` to create uploadable ZIPs.

### Via Manual Copy

Copy (or symlink) the plugin into your TallCMS `plugins/tallcms/redirect-manager/` directory and clear the plugin cache:

```bash
php artisan cache:clear
php artisan migrate
```

TallCMS discovers the plugin automatically — no Composer require or service provider registration needed. The migration creates the `tallcms_redirects` table.

### Via `git archive` (from this repo)

```bash
git clone https://github.com/tallcms/redirect-manager-plugin.git
cd redirect-manager-plugin
git archive --format=zip HEAD -o redirect-manager.zip
```

The resulting ZIP can be uploaded through **Admin > System > Plugins**.

## Usage

Navigate to **Admin > Configuration > Redirects** to manage redirects.

| Field | Description |
|-------|-------------|
| Source Path | The path to redirect FROM (e.g., `/old-page`). Must start with `/`. |
| Destination URL | The URL to redirect TO — can be a relative path or full URL. |
| Status Code | 301 (permanent, SEO-friendly) or 302 (temporary). |
| Active | Toggle the redirect on or off. |
| Note | Optional admin note explaining why the redirect exists. |

## How It Works

This plugin registers a global TallCMS middleware that runs before route resolution. On every GET/HEAD request:

1. The request path is normalized (leading slash, trailing slash stripped)
2. Looked up against a cached map of active redirects
3. If matched, the user is redirected with the configured status code
4. Hit count and last-hit timestamp are updated

The cache is automatically invalidated whenever a redirect is created, updated, or deleted.

## Path Matching

- **Exact match only** — `/old-page` matches `/old-page`, not `/old-page/subpage`
- **Query strings are ignored** — `/old?utm=source` matches a redirect on `/old`. The query string is not forwarded.
- **Trailing slashes are normalized** — `/old-page/` and `/old-page` are treated as the same path. You cannot create separate redirects for both.
- **Case-sensitive** — `/Old-Page` and `/old-page` are different paths
- **POST/PUT/DELETE requests are not redirected** — only GET and HEAD

## Performance

Active redirects are loaded into a cached array (keyed by source path) with a 1-hour TTL. The cache is invalidated automatically on any CRUD operation. For sites with thousands of redirects, this means a single cache miss per hour, with O(1) hash lookups on every request.

If the `tallcms_redirects` table doesn't exist yet (e.g., migration hasn't run), the middleware gracefully returns an empty map.

## License

MIT — see [LICENSE](LICENSE).
