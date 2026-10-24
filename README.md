# 🍔 Dishtansya — Back-End API (Laravel)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-17%20Passed-success?style=for-the-badge&logo=checkmarx&logoColor=white)](#-running-automated-tests)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](#-quick-start-with-docker)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)](LICENSE)

> Practical Examination Submission for **Multisys Back-End Practical Examination**.  
> **Dishtansya** is a high-performance food delivery API designed to provide seamless ordering from global food chains and restaurants.

---

## 📖 Table of Contents

- [Features Implemented](#-features-implemented)
- [Requirements Checklist](#-requirements-checklist)
- [Project Architecture](#-project-architecture)
- [Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Local Installation](#local-installation)
  - [Quick Start with Docker](#-quick-start-with-docker)
- [API Documentation](#-api-documentation)
- [Running Automated Tests](#-running-automated-tests)
- [Queue & Background Worker](#-queue--background-worker)
- [How to Upload to GitHub](#-how-to-upload-to-github)
- [License](#-license)

---

## 🚀 Features Implemented

1. **Registration API (`POST /register` & `POST /api/register`)**
   - Captures guest user credentials and registers the account (`HTTP 201`).
   - Rejects duplicate email registrations (`HTTP 400` with `"Email already taken"`).
   - **Plus Points**: Asynchronous welcome email dispatch via Laravel Queues and Jobs (`SendWelcomeEmailJob`).

2. **Authentication / Login API (`POST /login` & `POST /api/login`)**
   - Authenticates user credentials and issues a signed JSON Web Token (`access_token`) (`HTTP 201`).
   - Rejects invalid credentials (`HTTP 401` with `"Invalid credentials"`).
   - **Plus Points**: Account locking mechanism that locks the user out for 5 minutes after 5 consecutive failed attempts.

3. **Order API (`POST /order` & `POST /api/order`)**
   - Authenticated endpoint protected by JWT Bearer token.
   - Successfully creates orders and atomically deducts the ordered quantity from available product inventory (`HTTP 201`).
   - Rejects orders when stock is insufficient (`HTTP 400` with `"Failed to order this product due to unavailability of the stock"`).
   - Concurrency-safe implementation using database transactions and pessimistic row locking (`lockForUpdate()`) to prevent race conditions.
   - Pre-seeded products table with initial stock levels.

---

## ✅ Requirements Checklist

| Requirement | Status | Note |
|---|:---:|---|
| **Database-driven architecture** | ✅ | Migrations for `users`, `products`, `orders`, `jobs` |
| **Built with Laravel Framework** | ✅ | Clean MVC & Service architecture |
| **1. Registration API (2 scenarios)** | ✅ | HTTP 201 on success, HTTP 400 on duplicate |
| **2. Authentication API (2 scenarios)** | ✅ | HTTP 201 with JWT, HTTP 401 on failure |
| **3. Order API (2 scenarios)** | ✅ | HTTP 201 with stock deduction, HTTP 400 on low stock |
| **Product Seeder** | ✅ | `ProductSeeder` seeds Products (Id, Name, Available Stock) |
| **Deduct quantity from available stock** | ✅ | Atomic decrement inside DB transaction |
| **Bonus: Asynchronous Email Sending** | ✅ | Dispatches `SendWelcomeEmailJob` via queue worker |
| **Bonus: Account Locking (5 min / 5 attempts)** | ✅ | Enforced on `User` model & `AuthController` |
| **Bonus: Unit & Feature Tests for all scenarios** | ✅ | 17 tests passed (45 assertions) |
| **API Documentation** | ✅ | Complete `API_DOCUMENTATION.md`, `openapi.yaml`, & Postman collection |
| **Ready for GitHub** | ✅ | Git configured with clean commits and documentation |

---

## 🏛️ Project Architecture

```
dishtansya-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php      # Registration & Login endpoints
│   │   │   └── OrderController.php     # Product ordering & catalog endpoints
│   │   └── Middleware/
│   │       └── JwtMiddleware.php       # Validates Bearer JWT tokens
│   ├── Jobs/
│   │   └── SendWelcomeEmailJob.php     # Asynchronous welcome email queue job
│   ├── Mail/
│   │   └── WelcomeEmail.php            # Welcome email mailable definition
│   ├── Models/
│   │   ├── User.php                    # User entity with account lock logic
│   │   ├── Product.php                 # Product entity with inventory helpers
│   │   └── Order.php                   # Order entity with relationships
│   └── Services/
│       └── JwtService.php              # Signs & verifies JSON Web Tokens
├── database/
│   ├── migrations/                     # Schema definitions
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ProductSeeder.php           # Default products & stock seeder
├── resources/views/emails/             # Welcome email HTML Blade template
├── tests/
│   ├── Feature/
│   │   ├── RegistrationTest.php        # Feature tests for registration
│   │   ├── AuthenticationTest.php      # Feature tests for login & account lock
│   │   └── OrderTest.php               # Feature tests for ordering & stock logic
│   └── Unit/
│       └── ProductStockTest.php        # Unit tests for stock calculation
├── API_DOCUMENTATION.md                # Comprehensive endpoint reference
├── openapi.yaml                        # OpenAPI 3.0 specification
├── Dishtansya_API.postman_collection.json # Postman collection with test assertions
├── Dockerfile                          # Containerized runtime definition
└── docker-compose.yml                  # One-command orchestration
```

---

## 💻 Getting Started

### Prerequisites
- **PHP** >= 8.2 (with `pdo_sqlite` or `pdo_mysql`, `mbstring`, `curl`, `openssl`, `zip`)
- **Composer** >= 2.x
- *(Alternative)* **Docker** & **Docker Compose**

---

### Local Installation

1. **Clone the repository:**
   ```bash
   git clone <your-github-repo-url>
   cd dishtansya-api
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations and seed the database:**
   ```bash
   php artisan migrate --seed
   ```
   *This seeds the default products and an evaluation user (`backend@multisyscorp.com` / `test123`).*

5. **Start the development server:**
   ```bash
   php artisan serve
   ```
   The API will be available at: **`http://localhost:8000`**

---

### 🐳 Quick Start with Docker

You can spin up the entire application inside Docker with a single command without needing PHP or Composer installed on your host machine:

```bash
docker compose up -d --build
```

The container automatically installs dependencies, executes database migrations, seeds initial data, and exposes the API at **`http://localhost:8000`**.

To stop the container:
```bash
docker compose down
```

---

## 📑 API Documentation

Comprehensive API documentation is available in multiple formats:

1. **Markdown Documentation**: Read the full documentation at [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).
2. **OpenAPI / Swagger Spec**: Check [`openapi.yaml`](openapi.yaml) to load into Swagger Editor or Postman.
3. **Postman Collection**: Import [`Dishtansya_API.postman_collection.json`](Dishtansya_API.postman_collection.json) directly into Postman. It contains pre-configured requests, automated assertion tests, and environment token auto-extraction.

### Quick API Summary

| Method | Endpoint | Description | Auth Required |
|---|---|---|:---:|
| `POST` | `/register` | Register guest user (asynchronously sends email) | No |
| `POST` | `/login` | Authenticate user & retrieve JWT access token | No |
| `POST` | `/order` | Place an order and deduct stock atomically | **Yes (Bearer JWT)** |
| `GET` | `/products` | View available products and remaining stock | **Yes (Bearer JWT)** |
| `GET` | `/orders` | View user order history | **Yes (Bearer JWT)** |

*(Note: `/api/*` prefixes like `/api/register`, `/api/login`, and `/api/order` are also supported).*

---

## 🧪 Running Automated Tests

A comprehensive automated test suite covers all required scenarios and edge cases (including duplicate registrations, bad credentials, account lockouts, unauthorized requests, and stock race conditions):

```bash
php artisan test
```

### Test Suite Output
```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Unit\ProductStockTest
  ✓ product has sufficient stock check

   PASS  Tests\Feature\AuthenticationTest
  ✓ successful login
  ✓ successful login via api prefix
  ✓ unsuccessful login due to invalid credentials
  ✓ unsuccessful login with wrong password
  ✓ account locking after five failed attempts

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\OrderTest
  ✓ successfully created an order
  ✓ successfully created an order via api prefix
  ✓ unsuccessful order due to insufficient stock
  ✓ unauthorized order request without token
  ✓ unauthorized order request with invalid token

   PASS  Tests\Feature\RegistrationTest
  ✓ successful registration
  ✓ successful registration via api prefix
  ✓ unsuccessful registration due to email is already taken
  ✓ unsuccessful registration missing fields

  Tests:    17 passed (45 assertions)
  Duration: 0.80s
```

---

## 📬 Queue & Background Worker

The registration endpoint dispatches `SendWelcomeEmailJob` to a background queue for non-blocking execution.

In production or local testing with database/redis queues, start the queue worker:

```bash
php artisan queue:work
```

For immediate local dispatch, the default driver can be set to `sync` in `.env`:
```env
QUEUE_CONNECTION=sync
```

---

## 📤 How to Upload to GitHub

Follow these steps to upload this project to your GitHub account:

1. **Create a new repository on GitHub:**
   - Go to [GitHub](https://github.com/new) and create a new repository named `dishtansya-api` (or `dishtansya-backend`).
   - Leave it empty (do NOT initialize with README or .gitignore).

2. **From your terminal inside the project directory:**
   ```bash
   cd "C:\Users\Mary Grace Aurora\dishtansya-api"

   # Initialize git if not already initialized
   git init

   # Stage all files
   git add .

   # Commit
   git commit -m "feat: complete Dishtansya food delivery API with tests and docs"

   # Rename branch to main
   git branch -M main

   # Add your GitHub repository remote
   git remote add origin https://github.com/<YOUR_GITHUB_USERNAME>/dishtansya-api.git

   # Push to GitHub
   git push -u origin main
   ```

---

## 📄 License

This project was built for practical evaluation purposes and is open-sourced under the [MIT License](LICENSE).
