# Products API — Backend

RESTful API built with Laravel 11 for managing a product catalog with categories, soft deletes, audit logging, and Bearer Token authentication.

---

## Tech Stack

- **Laravel 11** — PHP framework
- **MySQL 8** — relational database
- **Eloquent ORM** — models, relationships, soft deletes
- **Laravel Observer** — automatic audit logging
- **PHPUnit** — unit testing

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── ProductController.php    # CRUD endpoints
│   │   └── CategoryController.php  # Category listing
│   ├── Middleware/
│   │   └── BearerTokenAuth.php     # Simple Bearer token guard
│   └── Requests/
│       ├── StoreProductRequest.php  # Validation rules for create
│       └── UpdateProductRequest.php # Validation rules for update
├── Models/
│   ├── Product.php                  # SoftDeletes, category relationship
│   ├── Category.php
│   └── AuditLog.php
├── Observers/
│   └── ProductObserver.php         # Logs created/updated/deleted
└── Providers/
    └── AppServiceProvider.php      # Registers observer
database/
├── migrations/                     # categories, products, audit_logs
└── seeders/
    └── DatabaseSeeder.php          # Sample data
tests/
└── Unit/
    └── ProductTest.php             # 4 unit tests
```

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8+

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### Environment Variables

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=products_db
DB_USERNAME=root
DB_PASSWORD=

BEARER_TOKEN=mytoken123
```

### Database Setup

```bash
php artisan migrate --seed
```

### Run Server

```bash
php artisan serve
```

API available at: `http://localhost:8000/api`

---

## Authentication

All endpoints require a Bearer token in the request header:

```
Authorization: Bearer mytoken123
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | Paginated product list |
| GET | `/api/products?search=burger` | Search by name or SKU |
| GET | `/api/products?category_id=1` | Filter by category |
| GET | `/api/products/{id}` | Single product with category |
| POST | `/api/products` | Create product |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Soft delete product |
| GET | `/api/categories` | List all categories |

### Example Response — GET /api/products

```json
{
  "data": [
    {
      "id": 1,
      "name": "Coca Cola 500ml",
      "sku": "BEV-001",
      "price": "1.50",
      "stock": 200,
      "category": {
        "id": 1,
        "name": "Beverages",
        "slug": "beverages"
      },
      "created_at": "2024-01-01 00:00:00"
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 5
  }
}
```

---

## Running Tests

```bash
php artisan test
```

Covers: product creation, unique SKU constraint, soft delete, category relationship.

---

## Design Decisions

**Soft Deletes** — products are never permanently removed. Deleted records remain in the database with a `deleted_at` timestamp, preserving historical data and audit trails.

**Observer pattern for audit logging** — `ProductObserver` automatically logs every create, update, and delete to `audit_logs` without polluting controller logic. Controllers stay focused on HTTP concerns only.

**Form Request classes** — validation lives in `StoreProductRequest` and `UpdateProductRequest`, not in the controller. The update request correctly ignores the current product's own SKU when checking uniqueness.

**Bearer Token middleware** — simple token auth applied at the route group level. Token is stored in `.env` and never hardcoded.

**`category_id` as foreign key with cascade** — ensures referential integrity at the database level, not just at the application level.

---
