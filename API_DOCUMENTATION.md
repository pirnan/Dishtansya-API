# 🍽️ Dishtansya Food Delivery — API Documentation

> **Dishtansya** is a food delivery application providing delivery service from food chains and restaurants around the globe.  
> Built with **Laravel**, database-driven architecture, signed JWT authentication, asynchronous queued emails, and atomic inventory management.

---

## 📌 Table of Contents
1. [Overview & Architecture](#overview--architecture)
2. [Base URL & Headers](#base-url--headers)
3. [Authentication Scheme](#authentication-scheme)
4. [Endpoint Reference](#endpoint-reference)
   - [1. Registration API (`POST /register`)](#1-registration-api)
   - [2. Authentication / Login API (`POST /login`)](#2-authentication--login-api)
   - [3. Order API (`POST /order`)](#3-order-api)
   - [4. Product Catalog API (`GET /products`)](#4-product-catalog-api)
   - [5. User Orders API (`GET /orders`)](#5-user-orders-api)
5. [Bonus Features & Engineering Highlights](#bonus-features--engineering-highlights)
6. [Postman Collection & OpenAPI Guide](#postman-collection--openapi-guide)
7. [Automated Testing](#automated-testing)

---

## 🏗️ Overview & Architecture

- **Framework**: Laravel 12.x / 11.x (PHP 8.2+)
- **Database Engine**: Database-driven with SQLite (default for zero-configuration portability) and full MySQL / PostgreSQL support
- **Authentication**: Stateless JSON Web Token (JWT) via HMAC-SHA256
- **Queue System**: Asynchronous background jobs via Laravel Queues for notifications
- **Concurrency Control**: Pessimistic database locking (`SELECT ... FOR UPDATE` via `lockForUpdate()`) and atomic transactions (`DB::transaction`) to avoid race conditions or negative inventory

---

## 🌐 Base URL & Headers

### Base URLs
- **Local Dev Server**: `http://localhost:8000`
- **Docker Compose**: `http://localhost:8000`

> **Note on Route Prefixing**: Both direct routes (`/register`, `/login`, `/order`) and standard API prefix routes (`/api/register`, `/api/login`, `/api/order`) are fully supported.

### Common Request Headers
| Header | Value | Description |
|---|---|---|
| `Content-Type` | `application/json` | Required for POST request payloads |
| `Accept` | `application/json` | Enforces JSON response envelope |
| `Authorization` | `Bearer <access_token>` | Required on protected endpoints |

---

## 🔐 Authentication Scheme

Protected endpoints require a JSON Web Token (JWT) provided in the HTTP `Authorization` request header:

```http
Authorization: Bearer <access_token>
```

Tokens are valid for **24 hours** from issuance and contain signed claims (`iss`, `sub`, `email`, `iat`, `exp`).

---

## 📚 Endpoint Reference

### 1. Registration API

**Feature:** Guest user can register  
**Route:** `POST /register` *(or `POST /api/register`)*  
**Access:** Public (Guest)

Captures guest user credentials, securely hashes the password using Bcrypt, stores the record in the database, and asynchronously dispatches a welcome email to the background worker queue.

#### Request Body
```json
{
  "email": "backend@multisyscorp.com",
  "password": "test123"
}
```

#### Fields
| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | **Yes** | Valid, unique email address |
| `password` | string | **Yes** | User password (minimum 6 characters) |

---

#### Scenario Outline 1.1: Successful registration
- **HTTP Status Code:** `201 Created`
- **Response Body:**
```json
{
  "message": "User successfully registered"
}
```

#### Scenario Outline 1.2: Unsuccessful registration due to email is already taken
- **HTTP Status Code:** `400 Bad Request`
- **Response Body:**
```json
{
  "message": "Email already taken"
}
```

#### Scenario Outline 1.3: Validation error (missing fields)
- **HTTP Status Code:** `400 Bad Request`
- **Response Body:**
```json
{
  "message": "The email field is required."
}
```

#### Asynchronous Queue Execution:
Upon successful registration, `App\Jobs\SendWelcomeEmailJob` is pushed to the queue. When processed by `php artisan queue:work`, it renders and delivers `App\Mail\WelcomeEmail` without blocking the HTTP response thread.

---

### 2. Authentication / Login API

**Feature:** Registered user can login  
**Route:** `POST /login` *(or `POST /api/login`)*  
**Access:** Public

Verifies provided credentials against database records and issues a signed JSON Web Token upon success. Implements security rate-limiting and temporary account locking.

#### Request Body
```json
{
  "email": "backend@multisyscorp.com",
  "password": "test123"
}
```

#### Fields
| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | **Yes** | User account email |
| `password` | string | **Yes** | User account password |

---

#### Scenario Outline 2.1: Successful login
- **HTTP Status Code:** `201 Created` *(per practical examination requirement)*
- **Response Body:**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAiLCJzdWIiOjEsImVtYWlsIjoiYmFja2VuZEBtdWx0aXN5c2NvcnAuY29tIiwiaWF0IjoxNzg0OTI4MDAwLCJleHAiOjE3ODUwMTQ0MDB9..."
}
```

#### Scenario Outline 2.2: Unsuccessful login due to invalid credentials
- **HTTP Status Code:** `401 Unauthorized`
- **Response Body:**
```json
{
  "message": "Invalid credentials"
}
```

#### Scenario Outline 2.3: Account locking after 5 failed attempts (Bonus Feature)
- If 5 consecutive invalid login attempts occur for an account, the account is locked for **5 minutes**.
- **HTTP Status Code:** `401 Unauthorized`
- **Response Body:**
```json
{
  "message": "Account is locked. Please try again after 5 minutes."
}
```

---

### 3. Order API

**Feature:** Order a specific product  
**Route:** `POST /order` *(or `POST /api/order`)*  
**Access:** Authenticated (`Authorization: Bearer <access_token>`)

Places an order for a specified product. Checks available inventory inside an atomic transaction using pessimistic row locking (`lockForUpdate()`), prevents negative inventory, deducts quantity from `products.available_stock`, and records the order in `orders`.

#### Headers
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

#### Request Body
```json
{
  "product_id": "1",
  "quantity": "2"
}
```

#### Fields
| Field | Type | Required | Description |
|---|---|---|---|
| `product_id` | string \| integer | **Yes** | ID of the product to purchase |
| `quantity` | string \| integer | **Yes** | Number of items to order (min: 1) |

---

#### Scenario Outline 3.1: Successfully created an order
- **HTTP Status Code:** `201 Created`
- **Response Body:**
```json
{
  "message": "You have successfully ordered this product."
}
```
- **Database Effect:**
  - Specified product's `available_stock` is atomically decremented by `quantity`.
  - A record is inserted into `orders` with `user_id`, `product_id`, `quantity`, and timestamps.

#### Scenario Outline 3.2: Unsuccessful order due to insufficient stock of a product
- **Request Example:**
```json
{
  "product_id": "2",
  "quantity": "9999"
}
```
- **HTTP Status Code:** `400 Bad Request`
- **Response Body:**
```json
{
  "message": "Failed to order this product due to unavailability of the stock"
}
```
- **Database Effect:**
  - Transaction rolls back. Stock remains intact. No order record is created.

#### Scenario Outline 3.3: Unauthorized request (missing or invalid token)
- **HTTP Status Code:** `401 Unauthorized`
- **Response Body:**
```json
{
  "message": "Unauthorized"
}
```

---

### 4. Product Catalog API

**Feature:** View all available products and real-time stock levels  
**Route:** `GET /products` *(or `GET /api/products`)*  
**Access:** Authenticated (`Authorization: Bearer <access_token>`)

#### Request Headers
```http
Authorization: Bearer <access_token>
```

#### Response
- **HTTP Status Code:** `200 OK`
- **Response Body:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Double Cheeseburger Meal",
      "available_stock": 48,
      "created_at": "2026-10-24T13:45:33.000000Z",
      "updated_at": "2026-10-24T13:47:40.000000Z"
    },
    {
      "id": 2,
      "name": "Crispy Fried Chicken Bucket",
      "available_stock": 10,
      "created_at": "2026-10-24T13:45:33.000000Z",
      "updated_at": "2026-10-24T13:45:33.000000Z"
    },
    {
      "id": 3,
      "name": "Pepperoni Pizza Deluxe",
      "available_stock": 30,
      "created_at": "2026-10-24T13:45:33.000000Z",
      "updated_at": "2026-10-24T13:45:33.000000Z"
    }
  ]
}
```

---

### 5. User Orders API

**Feature:** Retrieve past orders of the authenticated user  
**Route:** `GET /orders` *(or `GET /api/orders`)*  
**Access:** Authenticated (`Authorization: Bearer <access_token>`)

#### Response
- **HTTP Status Code:** `200 OK`
- **Response Body:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "product_id": 1,
      "quantity": 2,
      "created_at": "2026-10-24T13:47:40.000000Z",
      "updated_at": "2026-10-24T13:47:40.000000Z",
      "product": {
        "id": 1,
        "name": "Double Cheeseburger Meal",
        "available_stock": 48
      }
    }
  ]
}
```

---

## ⚡ Bonus Features & Engineering Highlights

| Feature | Implementation Detail | Location |
|---|---|---|
| **Asynchronous Email** | `SendWelcomeEmailJob` queued with `WelcomeEmail` mailable | [`app/Jobs/SendWelcomeEmailJob.php`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/app/Jobs/SendWelcomeEmailJob.php) |
| **Account Locking** | Locked for 5 mins after 5 consecutive failed logins | [`app/Models/User.php`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/app/Models/User.php) |
| **Concurrency Safety** | `DB::transaction()` + `lockForUpdate()` avoids stock race conditions | [`app/Http/Controllers/OrderController.php`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/app/Http/Controllers/OrderController.php) |
| **JWT Authentication** | Signed HS256 stateless tokens via `firebase/php-jwt` | [`app/Services/JwtService.php`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/app/Services/JwtService.php) |
| **Complete Unit & Feature Tests** | 17 automated tests covering all examination scenarios | [`tests/Feature/`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/tests/Feature/) |

---

## 📮 Postman Collection & OpenAPI Guide

### Importing Postman Collection
1. Open **Postman**.
2. Click **Import** in the top-left corner.
3. Select the file:
   [`Dishtansya_API.postman_collection.json`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/Dishtansya_API.postman_collection.json)
4. All 3 examination scenarios and edge cases will be imported with automated test assertions and token auto-population!

### OpenAPI / Swagger Specification
A complete OpenAPI 3.0.3 specification is available at:
[`openapi.yaml`](file:///C:/Users/Mary%20Grace%20Aurora/dishtansya-api/openapi.yaml)  
You can view it directly in **Swagger Editor** (`https://editor.swagger.io`) or import it into your API client of choice.

---

## 🧪 Automated Testing

Run the comprehensive test suite directly:

```bash
php artisan test
```

Or via PHPUnit:

```bash
./vendor/bin/phpunit
```

Test Results Summary:
```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Unit\ProductStockTest
PASS  Tests\Feature\AuthenticationTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\OrderTest
PASS  Tests\Feature\RegistrationTest

Tests:    17 passed (45 assertions)
Duration: ~0.80s
```
