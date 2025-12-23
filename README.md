<p align="center">
  <h1 align="center">WOCMS</h1>
  <p align="center">WebOrange Content Management System</p>
  <p align="center">
    <strong>A modern, flexible Laravel CMS built for agencies and developers</strong>
  </p>
</p>

<p align="center">
  <a href="https://github.com/SakkoulasGiannis/wocms/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel" alt="Laravel 11"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-3-4E56A6?logo=livewire" alt="Livewire 3"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind-3-38B2AC?logo=tailwind-css" alt="Tailwind CSS"></a>
  <img src="https://img.shields.io/badge/status-beta-orange.svg" alt="Beta">
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#installation">Installation</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#documentation">Documentation</a> •
  <a href="#contributing">Contributing</a>
</p>

---

> **⚠️ BETA SOFTWARE**
> WOCMS is currently in **beta** and under active development. While the core features are stable and functional, you may encounter bugs or incomplete features. We welcome feedback and contributions!
> - Report issues: [GitHub Issues](https://github.com/SakkoulasGiannis/wocms/issues)
> - Production use: Recommended for testing environments only at this stage

---

## About WOCMS

WOCMS is a powerful, modern content management system built on Laravel 11, designed specifically for agencies and developers who need a flexible, feature-rich solution for building dynamic websites and web applications.

Unlike traditional CMSs, WOCMS offers a unique **dynamic template system** that allows you to create custom content types with a visual field builder, eliminating the need to write migrations or models manually.

## Features

### Core Features

- **Dynamic Template Builder** - Create custom content types with a visual interface
- **GrapeJS Page Builder** - Drag-and-drop visual page editor
- **Advanced Media Library** - Automatic image size generation with Spatie Media Library
- **SEO Tools** - Comprehensive SEO fields for every content type
- **Module System** - Extendable architecture with custom modules
- **Multi-Language Ready** - Built-in support for multi-language content
- **Role-Based Access Control** - Granular permissions using Spatie Permissions

### Template System

- **Field Types**:
  - Text, Email, URL, Number, Decimal
  - Textarea, WYSIWYG Editor, Markdown, Code Editor
  - GrapeJS (full page builder)
  - Image, Gallery (with automatic sizes)
  - Date, DateTime, Time
  - Checkbox, Radio, Select (manual or Eloquent-based)
  - **Relation** - Define relationships with other models
  - **Group** - Nested field groups
  - **Repeater** - Repeatable field sets
  - JSON Editor, Color Picker, Icon Picker

- **Auto-Generated**:
  - Database migrations
  - Eloquent models with relationships
  - Admin CRUD interfaces
  - Frontend routes

### Page Building

- **Render Modes**:
  - Full Page GrapeJS - Complete visual page builder
  - Page Sections - Multiple flexible sections
  - Simple Content - Basic WYSIWYG editor

### Built With

- **Laravel 11** - Latest PHP framework
- **Livewire 3** - Full-stack reactive components
- **Alpine.js** - Minimal JavaScript framework
- **Tailwind CSS 3** - Utility-first CSS
- **GrapeJS** - Web page builder
- **Spatie Packages** - Media Library, Permissions, and more

## Installation

### Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+ / MariaDB 10.3+ / PostgreSQL 13+ / SQLite 3.35+

### Quick Install

```bash
# Clone the repository
git clone https://github.com/SakkoulasGiannis/wocms.git
cd wocms

# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# DB_CONNECTION=mysql
# DB_DATABASE=wocms
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed the database (creates admin user and sample data)
php artisan db:seed

# Create storage link
php artisan storage:link

# Build assets
npm run build

# Start the development server
php artisan serve
```

Visit `http://localhost:8000` in your browser.

**Default Admin Credentials:**
- Email: `admin@example.com`
- Password: `password`

> **Important:** Change these credentials immediately after first login!

## Quick Start

### Creating Your First Template

1. Navigate to **Admin > Templates**
2. Click **Create Template**
3. Fill in the basic information:
   - Name: "Blog Post"
   - Slug: "blog-post"
   - Description: "Blog articles"
4. Add fields:
   - Title (text, required)
   - Slug (text, URL identifier)
   - Featured Image (image)
   - Content (wysiwyg or grapejs)
   - Author (text)
   - Published Date (datetime)
5. Enable SEO fields
6. Save template

WOCMS will automatically:
- Create database table `blog_posts`
- Generate `BlogPost` model
- Create admin CRUD interface
- Set up frontend routes

### Creating Content

1. Navigate to **Admin > Blog Posts** (your new menu item)
2. Click **Create New**
3. Fill in your content
4. Choose render mode (GrapeJS, Sections, or Simple)
5. Configure SEO settings
6. Publish!

## Documentation

### Template System

Templates are the core of WOCMS. They define custom content types with:

- **Fields** - Define what data your content type stores
- **Relationships** - Connect content types together
- **Render Modes** - How content is edited (GrapeJS, Sections, Simple)
- **SEO Settings** - Built-in SEO for every content type
- **Permissions** - Who can create/edit content

### Field Types Reference

**Basic Fields:**
- `text` - Single line text input
- `textarea` - Multi-line text
- `wysiwyg` - Rich text editor
- `grapejs` - Full page builder

**Advanced Fields:**
- `select` - Dropdown (manual options or Eloquent)
- `relation` - Link to other content (belongsTo, hasMany, belongsToMany)
- `group` - Nested field groups (stored as JSON)
- `repeater` - Repeatable field sets

**Media:**
- `image` - Single image upload with automatic size generation
- `gallery` - Multiple images

**Date/Time:**
- `date`, `datetime`, `time`

### Module System

Create custom functionality with modules:

```bash
php artisan module:make Blog
```

Modules are self-contained packages with their own:
- Controllers
- Models
- Views
- Routes
- Migrations

## Configuration

### Environment Variables

```env
# Application
APP_NAME=WOCMS
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_DATABASE=wocms

# Mail
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@your-domain.com

# Media Library
MEDIA_DISK=public
```

### Image Sizes

Configure automatic image sizes in **Admin > Settings > Media**:

- Thumbnail: 150x150 (crop)
- Medium: 600x400 (fit)
- Large: 1200x800 (resize)
- Custom sizes as needed

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone for development
git clone https://github.com/SakkoulasGiannis/wocms.git
cd wocms

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations with seeders
php artisan migrate --seed

# Watch for changes
npm run dev
```

### Running Tests

```bash
php artisan test
```

## Security

If you discover a security vulnerability, please email security@weborange.gr. All security vulnerabilities will be promptly addressed.

## Roadmap

- [ ] RESTful API (auto-generated endpoints for templates)
- [ ] Multi-language support (in progress)
- [ ] GraphQL API
- [ ] Advanced caching system
- [ ] Page versioning
- [ ] Workflow/approval system
- [ ] Advanced form builder
- [ ] E-commerce module
- [ ] Analytics dashboard
- [ ] Import/Export tools for content migration

## Credits

**WOCMS** is created and maintained by [WebOrange.gr](https://weborange.gr)

### Built With

- [Laravel](https://laravel.com) - The PHP Framework
- [Livewire](https://livewire.laravel.com) - Full-stack framework for Laravel
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [GrapeJS](https://grapesjs.com) - Web page builder
- [Spatie Laravel Packages](https://spatie.be/open-source) - Media Library, Permissions
- [Alpine.js](https://alpinejs.dev) - Lightweight JavaScript framework

## License

WOCMS is open-source software licensed under the [MIT license](LICENSE).

---

<p align="center">
  <strong>Built with passion by <a href="https://weborange.gr">WebOrange.gr</a></strong>
</p>

<p align="center">
  <a href="https://weborange.gr">Website</a> •
  <a href="https://github.com/SakkoulasGiannis/wocms/issues">Report Bug</a> •
  <a href="https://github.com/SakkoulasGiannis/wocms/issues">Request Feature</a>
</p>
