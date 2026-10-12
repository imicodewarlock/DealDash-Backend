<div align="center">

# 🛒 DealDash - Backend API (Laravel)

> **DealDash-Backend** is the robust backend RESTful API service built with **Laravel** for the **DealDash** mobile application — a feature-rich e-commerce / deal-finding platform created as our **Graduation Project**.

📱 **Mobile App Repository:** [DealDash Mobile App (Flutter)](https://github.com/karimelsayed24/DealDash)

</div>

---

## 📌 Overview

DealDash connects users with real-time deals, local stores, and interactive shopping features. This Laravel backend powers the complete application lifecycle, handling secure user authentication (Laravel Sanctum), comprehensive administrative resource management with soft-deletes, location-based store and offer discovery, real-time notifications, and advanced search functionality.

---

## ✨ Key Features

* 🔐 **Authentication & Session Management:** Secure token-based authentication via **Laravel Sanctum**, supporting registration, login, password resets, email verification, and profile management.
* 🛡️ **Role-Based Administrative Dashboard:** Protected admin endpoints providing full CRUD operations and soft-delete capabilities (trash, restore, force delete) for:
  * Users
  * Categories
  * Stores
  * Offers
* 📍 **Location-Based Discovery:** Advanced querying for nearby stores and offers, including category-filtered proximity searches.
* ⭐ **User Interactions:** Favorite stores tracking and management.
* 🔔 **Notification System:** Complete notification center supporting read/unread statuses, filtering, and bulk updates.
* 🔍 **Unified Search Engine:** Powerful global search endpoints for mobile clients.

---

## 🛠️ Tech Stack & Architecture

* **Framework:** [Laravel](https://laravel.com/) (PHP Framework)
* **Authentication:** Laravel Sanctum (API Token Authentication)
* **Database:** MySQL / PostgreSQL (Managed via Eloquent ORM & Migrations)
* **Routing:** RESTful API Route Groups (`/api/auth`, `/api/admin`, `/api/v1/user`)

---

## 🚀 Getting Started & Local Setup

Follow these steps to set up and run the Laravel backend locally.

### Prerequisites

* **PHP:** v8.2 or higher
* **Composer:** Latest version
* **Database:** MySQL, PostgreSQL, or SQLite
* **Node.js & NPM** (Optional, if frontend assets are compiled)

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/imicodewarlock/DealDash-Backend.git
   cd DealDash-Backend
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment File:**
   Copy the example environment file and configure your database and app credentials:
   ```bash
   cp .env.example .env
   ```
   Open `.env` and update your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=deal_dash_v2
   DB_USERNAME=root
   DB_PASSWORD=your_database_password
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Run Database Migrations:**
   ```bash
   php artisan migrate
   ```
   *(Optional: Seed your database if seeders are available)* `php artisan db:seed`

6. **Serve the Application:**
   ```bash
   php artisan serve
   ```
   The backend API will be running locally at `http://127.0.0.1:8000`.

---

## 🔌 API Endpoints Reference

All API requests operate under the `/api` prefix. Protected routes require a valid Bearer Token obtained via login, passed in the header: `Authorization: Bearer <your_token>`.

### 1. Authentication Routes (`/api/auth`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/auth/register` | Register a new user | ❌ Public |
| `POST` | `/api/auth/login` | Authenticate user & return token | ❌ Public |
| `POST` | `/api/auth/forgot-password` | Request password reset link | ❌ Public |
| `POST` | `/api/auth/reset-password` | Reset account password | ❌ Public |
| `GET` | `/api/auth/email/verify/{token}` | Verify user email address | ❌ Public |
| `POST` | `/api/auth/email/resend` | Resend verification email | ❌ Public |
| `GET` | `/api/auth/me` | Get authenticated user profile | 🔒 Sanctum |
| `POST` | `/api/auth/change-password` | Change user password | 🔒 Sanctum |
| `POST` | `/api/auth/logout` | Revoke current token / logout | 🔒 Sanctum |

---

### 2. Admin Management Routes (`/api/admin`)
*Protected by `auth:sanctum`. These endpoints support full resource management, soft deletes, restores, and permanent deletions.*

#### **Users Management**
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/admin/users/list` | List all active users |
| `POST` | `/api/admin/users/create` | Create a new user |
| `GET` | `/api/admin/users/{id}/show` | Show specific user details |
| `PUT` | `/api/admin/users/{id}/edit` | Update user details |
| `DELETE` | `/api/admin/users/{id}/disable` | Soft-delete / disable user |
| `GET` | `/api/admin/users/trashed` | View soft-deleted users |
| `POST` | `/api/admin/users/{id}/restore` | Restore soft-deleted user |
| `DELETE` | `/api/admin/users/{id}/force-delete` | Permanently delete user |

#### **Categories Management**
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/admin/categories/list` | List all categories |
| `POST` | `/api/admin/categories/create` | Create a category |
| `GET` | `/api/admin/categories/{id}/show` | Show category details |
| `PUT` | `/api/admin/categories/{id}/edit` | Update category |
| `DELETE` | `/api/admin/categories/{id}/disable` | Soft-delete category |
| `GET` | `/api/admin/categories/trashed` | View soft-deleted categories |
| `POST` | `/api/admin/categories/{id}/restore` | Restore soft-deleted category |
| `DELETE` | `/api/admin/categories/{id}/force-delete` | Permanently delete category |

#### **Stores Management**
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/admin/stores/list` | List all stores |
| `POST` | `/api/admin/stores/create` | Create a store |
| `GET` | `/api/admin/stores/{id}/show` | Show store details |
| `PUT` | `/api/admin/stores/{id}/edit` | Update store |
| `DELETE` | `/api/admin/stores/{id}/disable` | Soft-delete store |
| `GET` | `/api/admin/stores/trashed` | View soft-deleted stores |
| `POST` | `/api/admin/stores/{id}/restore` | Restore soft-deleted store |
| `DELETE` | `/api/admin/stores/{id}/force-delete` | Permanently delete store |

#### **Offers Management**
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/admin/offers/list` | List all offers |
| `POST` | `/api/admin/offers/create` | Create an offer |
| `GET` | `/api/admin/offers/{id}/show` | Show offer details |
| `PUT` | `/api/admin/offers/{id}/edit` | Update offer |
| `DELETE` | `/api/admin/offers/{id}/disable` | Soft-delete offer |
| `GET` | `/api/admin/offers/trashed` | View soft-deleted offers |
| `POST` | `/api/admin/offers/{id}/restore` | Restore soft-deleted offer |
| `DELETE` | `/api/admin/offers/{id}/force-delete` | Permanently delete offer |

---

### 3. User Mobile API Routes (`/api/v1/user`)
*Protected by `auth:sanctum`. Designed for client-side consumption by the Flutter mobile app.*

#### **Categories**
* `GET /api/v1/user/categories/list-all` — List all available categories.
* `GET /api/v1/user/categories/get-category/{id}` — Get single category details.

#### **Stores & Favorites**
* `GET /api/v1/user/stores/list-all` — List all available stores.
* `GET /api/v1/user/stores/get-store/{id}` — Get single store details.
* `GET /api/v1/user/stores/nearby-stores` — Find nearby stores based on coordinates.
* `GET /api/v1/user/stores/category/{category}/nearby-stores` — Find nearby stores filtered by category.
* `POST /api/v1/user/stores/{id}/favorite` — Toggle store favorite status.
* `GET /api/v1/user/stores/favorite-stores` — Get user's favorite stores.

#### **Offers**
* `GET /api/v1/user/offers/list-all` — List all available offers.
* `GET /api/v1/user/offers/get-offer/{id}` — Get single offer details.
* `GET /api/v1/user/offers/nearby-offers` — Find nearby offers.
* `GET /api/v1/user/offers/category/{id}/nearby-offers` — Find nearby offers filtered by category.

#### **Notifications**
* `GET /api/v1/user/notifications/list-all` — Retrieve all user notifications.
* `GET /api/v1/user/notifications/list-unread` — Retrieve unread notifications.
* `PUT /api/v1/user/notifications/{id}/read` — Mark notification as read.
* `PUT /api/v1/user/notifications/{id}/unread` — Mark notification as unread.
* `PUT /api/v1/user/notifications/read-all` — Mark all notifications as read.
* `PUT /api/v1/user/notifications/unread-all` — Mark all notifications as unread.

#### **Search**
* `GET /api/v1/user/search` — Perform global search across system entities.

---

## 🔗 Related Repositories

* **Frontend Mobile Client:** [DealDash Mobile App (Flutter)](https://github.com/karimelsayed24/DealDash)

---

## 🎓 Graduation Project Context

This project was engineered as part of our Digital Egypt Pioneer Initiative (DEPI) graduation requirements to demonstrate a comprehensive full-stack mobile architecture, robust API design with Laravel, secure token authentication, and location-based discovery services.

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
