<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


# Laravel MVC Notes Application

A simple Laravel 13 Notes application built to understand and demonstrate **MVC architecture, routing, controllers, Eloquent ORM, migrations, Blade views, form validation, and SQLite database integration**.

> **Note:** The initial project structure and core configuration were generated using Laravel's built-in project scaffolding and libraries. The application-specific MVC functionality was implemented on top of this structure.

## Features

- Create and display notes
- SQLite database integration
- Form validation
- Automatic date and time tracking
- MVC architecture
- Eloquent ORM
- Blade templating
- CSRF protection
- Success feedback after creating a note

## Tech Stack

| Technology | Purpose |
|---|---|
| PHP 8.4 | Backend programming language |
| Laravel 13 | PHP web framework |
| Composer | PHP dependency management |
| SQLite | Database |
| Eloquent ORM | Database interaction |
| Blade | Server-side templating |
| HTML/CSS | User interface |
| Git/GitHub | Version control |

## MVC Flow

```mermaid
flowchart TD
    A[User] -->|GET /notes| B[Route<br/>routes/web.php]
    B --> C[Controller<br/>NoteController.php]
    C -->|Note::all / Note::create| D[Model<br/>Note.php]
    D -->|Eloquent ORM| E[(SQLite Database<br/>database.sqlite)]
    E -->|Data| D
    D --> C
    C -->|Pass data| F[Blade View<br/>notes.blade.php]
    F --> G[User]

    H[Migration<br/>database/migrations] -->|Defines table structure| E

## Project Structure

```text
mvc-demo/
├── app/
│   ├── Http/Controllers/       # Application controllers
│   └── Models/                 # Eloquent models
├── bootstrap/                  # Laravel application bootstrapping
├── config/                     # Application configuration
├── database/
│   ├── migrations/             # Database structure
│   └── database.sqlite         # SQLite database
├── public/                     # Public entry point
├── resources/
│   └── views/                  # Blade views
├── routes/
│   └── web.php                 # Web routes
├── storage/                    # Logs and generated files
├── tests/                      # Application tests
├── .env                        # Environment configuration
├── artisan                     # Laravel CLI
├── composer.json               # PHP dependencies
├── package.json                # Frontend dependencies
└── README.md

