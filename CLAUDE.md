# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the CeylonRover server-side application, built with Laravel 12 and PHP 8.2. CeylonRover appears to be a travel-focused platform featuring blogs and travsnaps (travel snapshots) with user authentication, moderation workflows, and admin functionality.

## Development Commands

### Core Laravel Commands
- `php artisan serve` - Start the Laravel development server
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeding
- `php artisan queue:work` - Process background jobs
- `php artisan pail` - View application logs

### Integrated Development Environment
- `composer dev` - Runs a complete development environment with:
  - Laravel server (`php artisan serve`)
  - Queue worker (`php artisan queue:listen --tries=1`)
  - Log viewer (`php artisan pail --timeout=0`)
  - Vite development server (`npm run dev`)

### Frontend Assets
- `npm run dev` - Start Vite development server
- `npm run build` - Build production assets

### Code Quality
- `./vendor/bin/pint` - Run Laravel Pint code formatter
- `php artisan test` - Run PHPUnit tests

## Architecture Overview

### Core Entities
- **Users**: Standard users with email verification, profiles, and role-based access
- **Blogs**: Travel blog posts with rich metadata (location, categories, operating hours, entry fees, etc.)
- **Travsnaps**: Travel snapshots/photos with location and gallery support
- **Admin Users**: Separate admin authentication system
- **Moderation System**: Content moderation workflow for both blogs and travsnaps

### Key Models and Relationships
- `Blog` - Main content model with moderation, media, likes, and bookmarks
- `Travsnap` - Travel snapshot model with moderation and media relations
- `User` - Standard user model with profile details and verification
- `BlogModeration`/`TravsnapModeration` - Content moderation tracking
- `Media` - File attachments for blogs and travsnaps
- `ModeratorAssignment` - Moderator task assignment system

### Authentication & Authorization
- Laravel Sanctum for API authentication
- Dual authentication system (users + admin users)
- Email verification workflow with OTP
- Token expiry middleware
- Role-based access control

### API Architecture
- RESTful API endpoints in `routes/api.php`
- Public endpoints for content browsing
- Protected routes requiring authentication
- Admin-only routes with `AdminAccessMiddleware`
- Comprehensive moderation endpoints

### Database Structure
- Standard Laravel migrations in `database/migrations/`
- Key tables: users, blogs, travsnaps, media, likes, bookmarks, moderations
- JSON casting for arrays (categories, location, gallery)
- Soft deletes and active status flags

### File Storage
- Media files managed through `Media` model
- Image galleries stored as JSON arrays
- Profile images supported

## Key Features

### Content Management
- Blogs with categories, locations, reviews, and extensive metadata
- Travsnaps with location and gallery support
- Content filtering and search functionality
- Featured content system

### Moderation Workflow
- Pending/Approved/Rejected status system
- Moderator assignment and task management
- Admin approval process for content
- Moderation notes and feedback

### User Engagement
- Like and bookmark system
- User profiles with detailed information
- Blog highlights/trending posts
- User-specific content views

### Admin Features
- Separate admin authentication
- User role management
- Content moderation dashboard
- Featured content management

## Development Notes

- Uses Laravel Sanctum tokens with expiry checking
- Email verification required for certain features
- Comprehensive middleware stack for security
- JSON API responses throughout
- File uploads handled through proper Laravel patterns