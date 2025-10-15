# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 + Vue 3 + Inertia.js application for managing church bulletins and content. The project uses a Domain-Driven Design approach with organized business logic in the `app/Domain` directory.

**Tech Stack:**
- Backend: Laravel 12, PHP 8.2+
- Frontend: Vue 3 (TypeScript), Inertia.js
- Styling: TailwindCSS 4, Reka UI components
- Build: Vite, Laravel Wayfinder
- Database: SQLite (default), supports MySQL/PostgreSQL
- Testing: Pest PHP

## Development Commands

### Running the Application

**Full development stack (recommended):**
```bash
composer dev
```
This runs concurrently:
- Laravel server (php artisan serve)
- Queue worker (php artisan queue:listen)
- Log viewer (php artisan pail)
- Vite dev server (npm run dev)

**Individual services:**
```bash
# Backend only
php artisan serve

# Frontend only
npm run dev

# Queue worker
php artisan queue:listen --tries=1

# Log viewer
php artisan pail --timeout=0
```

**SSR mode:**
```bash
composer dev:ssr
```

### Testing

```bash
# Run all tests
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with filter
php artisan test --filter=test_name
```

### Code Quality

```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Lint and fix frontend code
npm run lint

# Format frontend code
npm run format

# Check formatting without changes
npm run format:check
```

### Building for Production

```bash
# Build frontend assets
npm run build

# Build with SSR support
npm run build:ssr
```

### Database

```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name
```

## Architecture

### Domain-Driven Design Structure

The application uses DDD principles with business logic organized in `app/Domain`:

```
app/Domain/
├── church/          # Church-related domain logic
│   ├── ChurchInterface.php
│   ├── ChurchNewsInterface.php
│   └── msch/       # MSCH church implementation
│       ├── MSCH.php
│       ├── MSCHContentsType.php
│       └── crwal/  # Web crawling services
├── department/     # Department-specific implementations
│   ├── NewsongJ/   # NewSong church bulletin crawler
│   ├── BrightSound/
│   ├── MschYoutube/
│   └── MschJubo/
├── contents/       # Content management services
│   ├── ContentsFileType.php
│   ├── ThumbnailService.php
│   └── ContentsImageService.php
└── ogimage/        # OG image generation
    ├── Jobs/
    └── Events/
```

**Key Domain Patterns:**
- Each church/department implements specific interfaces for standardization
- Crawling services fetch and parse bulletin data from external sources
- Content services handle file processing, thumbnails, and image optimization
- Domain logic is separated from HTTP controllers

### Frontend Architecture

```
resources/js/
├── pages/          # Inertia.js page components (route endpoints)
│   ├── auth/
│   ├── church/
│   ├── department/
│   ├── contents/
│   └── settings/
├── components/     # Reusable Vue components
├── layouts/        # Page layout templates
├── routes/         # Generated Wayfinder routes (auto-generated)
├── actions/        # Frontend API actions
├── composables/    # Vue composables
├── types/          # TypeScript type definitions
└── app.ts          # Inertia app initialization
```

**Routing:**
- Backend: Laravel routes in `routes/web.php`, `routes/auth.php`, `routes/settings.php`
- Frontend: Wayfinder auto-generates type-safe route helpers in `resources/js/routes/`
- Use Wayfinder routes for navigation: `import { home } from '@/routes'`

### Data Flow

1. **Request Flow:** Browser → Inertia.js → Laravel Controller → Domain Service → Model → Database
2. **Response Flow:** Database → Model → Domain Service → Controller → Inertia Response → Vue Component
3. **Background Jobs:** Controllers dispatch jobs to queue for async processing (e.g., OG image generation)

### Key Integrations

- **Google API:** Used for content processing (requires GOOGLE_APPLICATION_CREDENTIALS)
- **Spatie PDF to Image:** PDF thumbnail generation
- **Intervention Image:** Image manipulation and optimization
- **Imagick extension:** Required for image processing

## Important Conventions

### PHP/Laravel

- Follow PSR-12 coding standards (enforced by Pint)
- Domain logic belongs in `app/Domain`, not controllers
- Controllers should be thin, delegating to domain services
- Use interface contracts for church/department implementations
- Queue jobs for heavy operations (image processing, crawling)

### Vue/TypeScript

- Use TypeScript for all new Vue components
- Use Composition API with `<script setup lang="ts">`
- Leverage Wayfinder for type-safe routing instead of manual route strings
- Follow component naming: PascalCase for components, kebab-case for files
- Use Reka UI components before building custom UI components

### File Organization

- Page components go in `resources/js/pages/` matching route structure
- Shared components in `resources/js/components/`
- Domain services in `app/Domain/[domain]/`
- HTTP controllers in `app/Http/Controllers/`
- Models in `app/Models/`

### Testing

- Use Pest for all tests (not PHPUnit syntax)
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Test database uses SQLite in-memory
- Tests run with `APP_ENV=testing`

## Configuration Notes

- Default database is SQLite (`database/database.sqlite`)
- Queue connection uses database driver
- Session stored in database
- File storage uses local disk by default
- Vite config includes Wayfinder with form variants enabled

## Dependencies to Know

**Critical PHP Extensions:**
- `ext-dom`: Required for web crawling
- `ext-imagick`: Required for image processing

**Key Laravel Packages:**
- `inertiajs/inertia-laravel`: SPA-like experience without API
- `laravel/wayfinder`: Type-safe route generation
- `tightenco/ziggy`: Route helpers for JavaScript

**Key Frontend Packages:**
- `@inertiajs/vue3`: Inertia Vue 3 adapter
- `reka-ui`: Headless UI component library
- `lucide-vue-next`: Icon library
- `ziggy-js`: Laravel route integration
