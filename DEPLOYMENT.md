# Deployment Guide

## Quick Deployment Steps

After pulling/deploying code to live server, run these commands:

```bash
# 1. Install/update dependencies
composer install --no-dev --optimize-autoloader

# 2. Ensure editable files exist (IMPORTANT!)
php artisan ensure-editable-files

# 3. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations if needed
php artisan migrate --force
```

## Important Notes

### Editable Files (.gitignore)

The following files are **NOT** tracked in git (they are in `.gitignore`):
- `resources/views/frontend/layout.blade.php`
- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- `*.backup-*` (backup files created by Code Editor)

These files are user-editable via the **Code Editor** (`/admin/code-editor`).

On deployment, these files won't exist, so you **MUST** run:
```bash
php artisan ensure-editable-files
```

This command will create them from the default templates:
- `default-layout.blade.php` → `layout.blade.php`
- `default-header.blade.php` → `header.blade.php`
- `default-footer.blade.php` → `footer.blade.php`

### System Architecture

**System Templates (tracked in git):**
- `default-layout.blade.php`
- `default-header.blade.php`
- `default-footer.blade.php`

These files are never modified by users and serve as the base templates.

**Editable Files (NOT tracked in git):**
- `layout.blade.php`
- `header.blade.php`
- `footer.blade.php`

These files are created from system templates and can be edited by users via Code Editor.

### Fresh Start

To completely reset the system (database + files):
```bash
php artisan fresh-start --force
```

This will:
- Reset database and seed
- Clear uploaded files
- Reset editable files from defaults
- Clear all caches

## Troubleshooting

### Error: "View [frontend.layout] not found"

**Cause:** Editable files don't exist on the server.

**Fix:**
```bash
php artisan ensure-editable-files
```

### Error: "View [default-layout] not found"

**Cause:** System template files are missing from repository.

**Fix:** Pull latest code from repository - these files should be tracked in git.

### Changes Not Appearing

**Fix:** Clear view cache
```bash
php artisan view:clear
```

## Deployment Checklist

- [ ] Pull latest code from repository
- [ ] Run `composer install`
- [ ] Run `php artisan ensure-editable-files`
- [ ] Run migrations if needed
- [ ] Clear all caches
- [ ] Test frontend pages
- [ ] Test Code Editor functionality

## Environment Variables

Make sure these are set correctly in `.env`:
```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=database  # or redis
SESSION_DRIVER=database  # or redis
SESSION_LIFETIME=480
```
