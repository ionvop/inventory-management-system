# Inventory Management API Documentation

A Laravel-based REST API for managing inventory items, stock transactions, users, and generating reports.

---

## Table of Contents

- [Authentication](#authentication)
- [Standard Response Formats](#standard-response-formats)
- [Error Handling](#error-handling)
- [API Endpoints](#api-endpoints)
  - [Auth / Users List](#auth--users-list)
  - [Users](#users)
  - [Items](#items)
  - [Transactions](#transactions)
  - [Dashboard](#dashboard)
  - [Reports](#reports)
  - [Settings](#settings)
- [Data Models](#data-models)

---

## Authentication

All endpoints (except `GET /api/auth/users`) require the **`X-User-Id`** header to be present on every request. This header identifies the current user performing the action.

```
X-User-Id: <user_id>
```

**Missing header** → `400` error with code `MISSING_USER_ID`  
**Invalid user ID** → `404` error with code `USER_NOT_FOUND`

> The middleware `ResolveCurrentUser` handles this for all routes within the `resolve.user` middleware group.

---

## Standard Response Formats

### Single Resource / Data Response

```json
{
  "data": { ... }
}
```

Status codes: `200` (success), `201` (created).

### Paginated Collection Response

```json
{
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "limit": 25,
    "total": 150,
    "total_pages": 6
  }
}
```

Status code: `200`.

### Empty Response

`204 No Content` — returned on successful `DELETE` operations.

---

## Error Handling

The API uses a custom `ApiException` class. All errors follow this structure:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable description.",
    "details": { ... }
  }
}
```

### Known Error Codes

| HTTP Status | Code | Description |
|---|---|---|
| 400 | `MISSING_USER_ID` | `X-User-Id` header not provided |
| 404 | `USER_NOT_FOUND` | No user exists with the given ID |
| 409 | `USER_HAS_TRANSACTIONS` | Cannot delete a user who has transactions |
| 422 | `INSUFFICIENT_STOCK` | Stock insufficient for an `out` transaction or update |

Laravel validation errors (422) return the default Laravel format with `message` and `errors` keys.

---

## API Endpoints

---

### Auth / Users List

#### `GET /api/auth/users`

Returns all users (id and username only) for authentication/dropdown purposes. This endpoint does **not** require the `X-User-Id` header.

**Response** `200`

```json
{
  "data": [
    { "id": 1, "username": "john" },
    { "id": 2, "username": "jane" }
  ]
}
```

---

### Users

Base path: `/api/users`

#### `GET /api/users`

List all users with optional filtering, sorting, and pagination.

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | — | Filter by username (partial match, case-insensitive) |
| `sort` | string | `username` | Column to sort by. Allowed: `username`, `time` |
| `order` | string | `asc` | Sort direction: `asc` or `desc` |
| `limit` | int | `25` | Items per page |
| `page` | int | `1` | Page number |

**Response** `200` — Paginated

```json
{
  "data": [
    {
      "id": 1,
      "username": "john",
      "time": 1753545600
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 25,
    "total": 1,
    "total_pages": 1
  }
}
```

---

#### `POST /api/users`

Create a new user.

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `username` | string | Yes | Max 255 chars, unique |

**Request Example**

```json
{
  "username": "john"
}
```

**Response** `201`

```json
{
  "data": {
    "id": 1,
    "username": "john",
    "time": 1753545600
  }
}
```

---

#### `GET /api/users/{user}`

Get a single user by ID.

**Response** `200`

```json
{
  "data": {
    "id": 1,
    "username": "john",
    "time": 1753545600
  }
}
```

---

#### `PUT /api/users/{user}`

Update an existing user. (Use `PUT` for full replacement.)

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `username` | string | Yes | Max 255 chars, unique (ignores current user's username) |

**Request Example**

```json
{
  "username": "john_updated"
}
```

**Response** `200`

```json
{
  "data": {
    "id": 1,
    "username": "john_updated",
    "time": 1753545600
  }
}
```

---

#### `DELETE /api/users/{user}`

Delete a user.

**Constraints:** The user must have no associated transactions. If they do, a `409` error with code `USER_HAS_TRANSACTIONS` is returned.

**Response** `204 No Content`

---

### Items

Base path: `/api/items`

#### `GET /api/items`

List all items with computed stock levels, optional filtering, sorting, and manual pagination.

> **Note:** Items include computed attributes `current_stock` and `is_low_stock`. These are calculated in-memory, so pagination is applied after all items are retrieved and filtered. This is suitable for local/small-scale inventories.

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | — | Filter by item name (partial match, case-insensitive) |
| `low_stock` | int | — | Set to `1` to return only items where `current_stock < minimum_stock` |
| `sort` | string | `name` | Column to sort by. Any item attribute (e.g., `name`, `current_stock`, `minimum_stock`) |
| `order` | string | `asc` | Sort direction: `asc` or `desc` |
| `limit` | int | `25` | Items per page |
| `page` | int | `1` | Page number |

**Response** `200`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Widget",
      "unit": "pcs",
      "minimum_stock": 10,
      "time": 1753545600,
      "current_stock": 25,
      "is_low_stock": false
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 25,
    "total": 1,
    "total_pages": 1
  }
}
```

---

#### `GET /api/items/low-stock`

Shorthand for `GET /api/items?low_stock=1`. Returns only items below their minimum stock threshold.

**Supports the same query parameters as** `GET /api/items`.

**Response** `200` — Same format as `GET /api/items`.

---

#### `POST /api/items`

Create a new inventory item.

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | Yes | Max 255 chars, unique |
| `unit` | string | Yes | Max 50 chars |
| `minimum_stock` | int | No | Min 0 (defaults to 0) |

**Request Example**

```json
{
  "name": "Widget",
  "unit": "pcs",
  "minimum_stock": 10
}
```

**Response** `201`

```json
{
  "data": {
    "id": 1,
    "name": "Widget",
    "unit": "pcs",
    "minimum_stock": 10,
    "time": 1753545600,
    "current_stock": 0,
    "is_low_stock": true
  }
}
```

---

#### `GET /api/items/{item}`

Get a single item by ID, including computed stock attributes.

**Response** `200`

```json
{
  "data": {
    "id": 1,
    "name": "Widget",
    "unit": "pcs",
    "minimum_stock": 10,
    "time": 1753545600,
    "current_stock": 25,
    "is_low_stock": false
  }
}
```

---

#### `PUT /api/items/{item}`

Update an existing item.

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | No | Max 255 chars, unique (ignores current item) |
| `unit` | string | No | Max 50 chars |
| `minimum_stock` | int | No | Min 0 |

> The `UpdateItemRequest` rules are empty in the current implementation (all fields are optional). Validation is effectively bypassed at the request level.

**Response** `200` — Updated item with computed stock.

---

#### `DELETE /api/items/{item}`

Delete an item. **All associated transactions are cascade-deleted.**

**Response** `204 No Content`

---

#### `GET /api/items/{item}/transactions`

Get all transactions for a specific item. Supports the same query parameters as `GET /api/transactions`.

**Response** `200` — Paginated (same format as transactions list).

---

### Transactions

Base path: `/api/transactions`

#### `GET /api/transactions`

List transactions with optional filters, sorting, and pagination. Includes related `item` (id, name) and `user` (id, username).

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `item_id` | int | — | Filter by item |
| `user_id` | int | — | Filter by user |
| `movement` | string | — | Filter by movement type: `in` or `out` |
| `date_from` | int (timestamp) | — | Filter transactions with `posted_time >= date_from` |
| `date_to` | int (timestamp) | — | Filter transactions with `posted_time <= date_to` |
| `sort` | string | `posted_time` | Column to sort by |
| `order` | string | `desc` | Sort direction: `asc` or `desc` |
| `limit` | int | `25` | Items per page |
| `page` | int | `1` | Page number |

**Response** `200` — Paginated

```json
{
  "data": [
    {
      "id": 1,
      "item_id": 1,
      "user_id": 1,
      "movement": "in",
      "quantity": 50,
      "posted_time": 1753545600,
      "time": 1753545600,
      "item": { "id": 1, "name": "Widget" },
      "user": { "id": 1, "username": "john" }
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 25,
    "total": 1,
    "total_pages": 1
  }
}
```

---

#### `POST /api/transactions`

Record a new stock transaction (incoming or outgoing).

> **Stock validation:** For `out` transactions, the API checks that sufficient stock exists. If not, a `422` error with code `INSUFFICIENT_STOCK` is returned.

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `item_id` | int | Yes | Must exist in the `items` table |
| `movement` | string | Yes | `in` or `out` |
| `quantity` | int | Yes | Minimum 1 |
| `posted_time` | int (timestamp) | No | Unix timestamp. Defaults to start of current day (`00:00:00`) |

**Request Example**

```json
{
  "item_id": 1,
  "movement": "in",
  "quantity": 50,
  "posted_time": 1753545600
}
```

**Response** `201`

```json
{
  "data": {
    "id": 1,
    "item_id": 1,
    "user_id": 1,
    "movement": "in",
    "quantity": 50,
    "posted_time": 1753545600,
    "time": 1753545600,
    "item": { "id": 1, "name": "Widget" },
    "user": { "id": 1, "username": "john" }
  }
}
```

---

#### `GET /api/transactions/{transaction}`

Get a single transaction by ID, with related item and user.

**Response** `200`

```json
{
  "data": {
    "id": 1,
    "item_id": 1,
    "user_id": 1,
    "movement": "in",
    "quantity": 50,
    "posted_time": 1753545600,
    "time": 1753545600,
    "item": { "id": 1, "name": "Widget" },
    "user": { "id": 1, "username": "john" }
  }
}
```

---

#### `PUT /api/transactions/{transaction}`

Update an existing transaction. Only the fields provided will be updated.

> **Stock validation on update:** When updating an `out` transaction or changing to `out`, the API excludes the current transaction's effect from the stock calculation before re-validating. This prevents false negatives when simply adjusting the quantity of an existing `out` transaction.

**Request Body**

| Field | Type | Required | Rules |
|---|---|---|---|
| `movement` | string | No | `in` or `out` |
| `quantity` | int | No | Minimum 1 |
| `posted_time` | int (timestamp) | No | Unix timestamp |

**Response** `200`

```json
{
  "data": {
    "id": 1,
    "item_id": 1,
    "user_id": 1,
    "movement": "out",
    "quantity": 10,
    "posted_time": 1753545600,
    "time": 1753545600,
    "item": { "id": 1, "name": "Widget" },
    "user": { "id": 1, "username": "john" }
  }
}
```

---

#### `DELETE /api/transactions/{transaction}`

Delete a transaction. Stock will be recalculated accordingly.

**Response** `204 No Content`

---

### Dashboard

Base path: `/api/dashboard`

#### `GET /api/dashboard/summary`

Returns a dashboard summary including total items, low stock items, today's transaction counts, and recent transactions.

**Response** `200`

```json
{
  "data": {
    "total_items": 42,
    "low_stock_count": 3,
    "low_stock_items": [
      {
        "id": 5,
        "name": "Screws",
        "unit": "box",
        "minimum_stock": 20,
        "current_stock": 5,
        "is_low_stock": true
      }
    ],
    "today_transactions": {
      "in_count": 12,
      "out_count": 8
    },
    "recent_transactions": [
      {
        "id": 100,
        "item_id": 1,
        "user_id": 1,
        "movement": "out",
        "quantity": 5,
        "posted_time": 1753545600,
        "time": 1753545600,
        "item": { "id": 1, "name": "Widget" },
        "user": { "id": 1, "username": "john" }
      }
    ]
  }
}
```

| Field | Description |
|---|---|
| `total_items` | Total number of items in inventory |
| `low_stock_count` | Count of items below their minimum stock |
| `low_stock_items` | Up to 10 low stock items (with computed `current_stock` and `is_low_stock`) |
| `today_transactions.in_count` | Number of `in` transactions posted today |
| `today_transactions.out_count` | Number of `out` transactions posted today |
| `recent_transactions` | 10 most recent transactions (with related item and user) |

---

### Reports

Base path: `/api/reports`

#### `GET /api/reports/inventory`

Generate an inventory report in JSON, CSV, or PDF format.

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `date_from` | int (timestamp) | — | Filter transactions from this date (based on `posted_time`) |
| `date_to` | int (timestamp) | — | Filter transactions up to this date (based on `posted_time`) |
| `format` | string | `json` | Output format: `json`, `csv`, or `pdf` |

**Response** (JSON format, `200`)

```json
{
  "data": {
    "generated_time": 1753545600,
    "date_from": "1753460000",
    "date_to": "1753545600",
    "items": [
      {
        "id": 1,
        "name": "Widget",
        "unit": "pcs",
        "minimum_stock": 10,
        "current_stock": 25,
        "is_low_stock": false,
        "transactions": [
          {
            "id": 1,
            "item_id": 1,
            "user_id": 1,
            "movement": "in",
            "quantity": 50,
            "posted_time": 1753545600,
            "time": 1753545600,
            "user": { "id": 1, "username": "john" }
          }
        ]
      }
    ]
  }
}
```

**CSV Format:** Returns a `text/csv` file download with columns: `Item`, `Unit`, `Current Stock`, `Minimum Stock`, `Transaction Date`, `Movement`, `Quantity`, `User`.

**PDF Format:** Returns a PDF file download generated from the `reports.inventory` Blade view.

---

### Settings

Base path: `/api/settings`

#### `GET /api/settings`

Get all key-value application settings.

**Response** `200`

```json
{
  "data": {
    "app_name": "My Inventory",
    "low_stock_threshold": "10"
  }
}
```

---

#### `PUT /api/settings`

Bulk update application settings. Any keys sent will be created or updated; keys not sent remain unchanged.

**Request Body** — A JSON object of key-value pairs.

**Request Example**

```json
{
  "app_name": "My Inventory",
  "low_stock_threshold": "10"
}
```

**Response** `200` — Returns all current settings after update.

```json
{
  "data": {
    "app_name": "My Inventory",
    "low_stock_threshold": "10"
  }
}
```

---

## Data Models

### User

| Field | Type | Description |
|---|---|---|
| `id` | bigint (PK) | Auto-incrementing ID |
| `username` | string | Unique username |
| `time` | bigint (timestamp) | Creation/modification timestamp |

### Item

| Field | Type | Description |
|---|---|---|
| `id` | bigint (PK) | Auto-incrementing ID |
| `name` | string | Unique item name |
| `unit` | string | Unit of measurement (e.g., pcs, kg, box) |
| `minimum_stock` | int | Minimum stock threshold (default 0) |
| `time` | bigint (timestamp) | Creation/modification timestamp |

**Computed Attributes** (not stored in DB):

| Attribute | Type | Description |
|---|---|---|
| `current_stock` | int | Total `in` quantity minus total `out` quantity across all transactions |
| `is_low_stock` | bool | `true` when `current_stock < minimum_stock` |

**Relationships:**

- `transactions`: Has many `Transaction` (cascade on delete)

### Transaction

| Field | Type | Description |
|---|---|---|
| `id` | bigint (PK) | Auto-incrementing ID |
| `item_id` | bigint (FK) | References `items.id` (cascade on delete) |
| `user_id` | bigint (FK) | References `users.id` (restrict on delete) |
| `movement` | enum(`in`, `out`) | Stock movement direction |
| `quantity` | int | Quantity moved |
| `posted_time` | bigint (timestamp) | Date the transaction is posted for (defaults to start of day) |
| `time` | bigint (timestamp) | Actual creation timestamp |

**Relationships:**

- `item`: Belongs to `Item`
- `user`: Belongs to `User`

### Setting

| Field | Type | Description |
|---|---|---|
| `key` | string (PK) | Setting key |
| `value` | text (nullable) | Setting value |

---

## HTTP Status Code Summary

| Code | Meaning |
|---|---|
| `200` | Success (GET, PUT) |
| `201` | Created (POST) |
| `204` | No Content (DELETE) |
| `400` | Bad Request (missing X-User-Id header) |
| `404` | Not Found (resource or user not found) |
| `409` | Conflict (e.g., deleting user with transactions) |
| `422` | Unprocessable Entity (validation failure or business rule violation) |

---

## Rate Limiting

This API does not implement rate limiting.

---

## Base URL

```
http://<host>/api