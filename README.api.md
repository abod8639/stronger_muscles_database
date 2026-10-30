# Stronger Muscles API Documentation 🚀

This document is the definitive API reference for integrating the **Stronger Muscles Mobile App (Flutter)** and the **Admin Dashboard** with the Laravel 12 backend.

---

## General Information

### Base URLs
- **Local Development:** `http://localhost:8000/api/v1` (or your local IP / Android emulator `http://10.0.2.2:8000/api/v1`)
- **Staging / Production:** `https://api.strongermuscles.com/api/v1`

### Authentication Architecture
The API utilizes **Laravel Sanctum** with dual guards:
- **Customer / App Guard:** `auth:sanctum` (User model)
- **Admin / Dashboard Guard:** `auth:admin-api` + `isAdmin` middleware (Admin model)

### Standard Request Headers
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
Accept-Language: ar
```
*(Use `Accept-Language: ar` or `Accept-Language: en` for localized promo, product and validation responses).*

---

## Table of Contents
1. [Authentication & Profile (App)](#1-authentication--profile-app)
2. [Public Shop & Catalog](#2-public-shop--catalog)
3. [Customer APIs (Protected)](#3-customer-apis-protected)
   - [Profile & FCM](#profile--fcm-token)
   - [Cart Management](#shopping-cart)
   - [Wishlist](#wishlist)
   - [Saved Addresses](#address-management)
   - [Checkout & Orders](#orders--checkout)
   - [Online Payments](#payment-gateway)
   - [Reviews & Ratings](#product-reviews)
   - [Customer Notifications](#customer-notifications)
   - [Support Tickets & Chat](#support-tickets--complaints)
4. [Payment Webhooks](#4-payment-webhooks)
5. [Admin Dashboard APIs (Protected)](#5-admin-dashboard-apis-protected)
   - [Admin Authentication & Profile](#admin-auth)
   - [Theme Mode Synchronization](#admin-theme-mode)
   - [Dashboard Metrics & Navigation Badges](#dashboard-metrics--sidebar-badges)
   - [Store Settings](#store-settings)
   - [Products Management](#products-management)
   - [Brands Management](#brands-management)
   - [Categories Management](#categories-management)
   - [Promotions Management](#promotions-management)
   - [Orders Management & Alerts](#orders-management)
   - [Customer Accounts](#customer-accounts-management)
   - [Support Tickets & Complaints](#support-tickets-management)
   - [Push Notifications Broadcast](#notification-broadcasting)
   - [Media & Image Uploads](#media-services)
6. [Standard Error Responses](#6-standard-error-responses)

---

## 1. Authentication & Profile (App)

All endpoints prefixed with `/api/v1/auth`.

### 1.1 Register
- **Endpoint:** `POST /api/v1/auth/register`
- **Rate Limit:** 5 requests / min
- **Request Body:**
  ```json
  {
    "name": "Ahmed Mohamed",
    "email": "ahmed@example.com",
    "password": "StrongPassword123"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Account created successfully",
    "token": "1|abc12345...",
    "user": {
      "id": "uuid-here",
      "name": "Ahmed Mohamed",
      "email": "ahmed@example.com",
      "phone": null,
      "photo_url": null,
      "default_address_id": null,
      "preferred_language": "ar",
      "notifications_enabled": true,
      "is_active": true,
      "role": "user",
      "total_spent": 0,
      "orders_count": 0,
      "addresses": [],
      "created_at": "2026-10-30T10:00:00.000000Z"
    }
  }
  ```

### 1.2 Login
- **Endpoint:** `POST /api/v1/auth/login`
- **Rate Limit:** 5 requests / min
- **Request Body:**
  ```json
  {
    "email": "ahmed@example.com",
    "password": "StrongPassword123"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "token": "2|xyz98765...",
    "user": { ... }
  }
  ```

### 1.3 Google Sign-In
- **Endpoint:** `POST /api/v1/auth/google-signin`
- **Request Body:**
  ```json
  {
    "email": "ahmed@gmail.com",
    "name": "Ahmed Google",
    "photo_url": "https://lh3.googleusercontent.com/..."
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "access_token": "3|googleToken...",
    "token_type": "Bearer",
    "user": { ... }
  }
  ```

### 1.4 Update Profile
- **Endpoint:** `POST /api/v1/auth/update-profile`
- **Headers:** `Authorization: Bearer {token}`
- **Request Body:**
  ```json
  {
    "name": "Ahmed M.",
    "phone": "+201012345678",
    "photo_url": "https://storage.strongermuscles.com/avatars/user.jpg",
    "preferred_language": "ar",
    "notifications_enabled": true
  }
  ```

### 1.5 Update FCM Token
- **Endpoint:** `POST /api/v1/auth/fcm-token` (also available as `POST /api/v1/customer/fcm-token`)
- **Headers:** `Authorization: Bearer {token}`
- **Request Body:**
  ```json
  {
    "fcm_token": "eY7...fcm_device_token...8Q"
  }
  ```

### 1.6 Logout
- **Endpoint:** `POST /api/v1/auth/logout`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Logged out successfully"
  }
  ```

### 1.7 Current User
- **Endpoint:** `GET /api/v1/user`
- **Headers:** `Authorization: Bearer {token}`

---

## 2. Public Shop & Catalog

Public read-only endpoints accessible without authentication. Prefixed with `/api/v1/shop`.

### 2.1 Banners & Promotions
- **Endpoint:** `GET /api/v1/shop/promos`
- **Headers:** `Accept-Language: ar` (or `en`)
- **Response (200 OK):** Returns active promotional slides and discounts.

### 2.2 Categories List
- **Endpoint:** `GET /api/v1/shop/categories`
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": "proteins",
        "name": { "ar": "بروتينات", "en": "Proteins" },
        "description": { "ar": "أفضل مكملات البروتين", "en": "Top protein supplements" },
        "imageUrl": "https://storage.strongermuscles.com/categories/proteins.png",
        "icon": "fitness_center",
        "sortOrder": 1,
        "isActive": true,
        "productsCount": 14,
        "createdAt": "2026-01-01T00:00:00.000000Z"
      }
    ]
  }
  ```

### 2.3 Category Details
- **Endpoint:** `GET /api/v1/shop/categories/{id}`

### 2.4 Products Catalog
- **Endpoint:** `GET /api/v1/shop/products`
- **Query Parameters:**
  - `category` *(string, optional)*: Filter by category slug or ID.
  - `search` *(string, optional)*: Search in title and description.
  - `sort_by` *(string, optional)*: `latest` (default), `price_low`, `price_high`, `best_seller`, `rating`, `new`.
  - `page` *(int, optional)*: Page number (default: 1, 20 items/page).
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "data": {
      "data": [
        {
          "id": "gold-standard-whey",
          "name": { "ar": "جولد ستاندرد واي بروتين", "en": "Gold Standard 100% Whey" },
          "description": { "ar": "...", "en": "..." },
          "brand": "Optimum Nutrition",
          "category": { "id": "proteins", "name": { "ar": "بروتينات", "en": "Proteins" } },
          "categoryName": "Gold Standard 100% Whey",
          "imageUrls": [
            {
              "thumbnail": "https://.../thumb.jpg",
              "medium": "https://.../medium.jpg",
              "original": "https://.../orig.jpg"
            }
          ],
          "has_variants": true,
          "product_variants": [
            {
              "id": "variant-uuid-1",
              "sku": "GS-WHEY-2KG-CHOC",
              "price": 2500.00,
              "discount_price": 2250.00,
              "effective_price": 2250.00,
              "stock_quantity": 45,
              "attributes": { "size": "2kg", "flavor": "Double Rich Chocolate" },
              "is_active": true
            }
          ],
          "price": 2500.00,
          "discount_price": 2250.00,
          "stock_quantity": 45,
          "average_rating": 4.8,
          "review_count": 120,
          "flavors": ["Double Rich Chocolate", "Vanilla Ice Cream", "Strawberry"],
          "product_sizes": [
            { "size": "2kg", "price": 2500.00, "discount_price": 2250.00 },
            { "size": "4.5kg", "price": 5000.00, "discount_price": 4700.00 }
          ],
          "is_active": true,
          "is_background_white": true,
          "featured": true,
          "new_arrival": false,
          "best_seller": true
        }
      ],
      "current_page": 1,
      "last_page": 4,
      "total": 75
    }
  }
  ```

### 2.5 Product Details
- **Endpoint:** `GET /api/v1/shop/products/{id}`

### 2.6 Product Reviews List
- **Endpoint:** `GET /api/v1/shop/products/{id}/reviews`
- **Query Parameters:** `per_page` (default: 10)

### 2.7 Guest Support Ticket
- **Endpoint:** `POST /api/v1/shop/support/tickets`
- Allows visitors without accounts to submit inquiries or complaints.

---

## 3. Customer APIs (Protected)

Requires `Authorization: Bearer {token}` with Customer guard (`auth:sanctum`). Prefixed with `/api/v1/customer`.

### Profile & FCM Token
- `GET /api/v1/customer/profile`: Fetches authenticated user profile, saved addresses, total spent and orders count.
- `POST /api/v1/customer/fcm-token`: Updates the user's Firebase Cloud Messaging token.

---

### Shopping Cart
Each unique combination of `product_id`, `flavor`, `size`, and `variant_id` is stored as an independent line item. Prices are strictly determined by the server.

- **Get Cart:** `GET /api/v1/customer/cart`
  - **Response (200 OK):**
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": 15,
          "product_id": "gold-standard-whey",
          "quantity": 2,
          "unit_price": 2250.00,
          "subtotal": 4500.00,
          "selected_flavor": "Double Rich Chocolate",
          "selected_size": "2kg",
          "product": { "id": "gold-standard-whey", "name": { ... }, "imageUrls": [...] }
        }
      ],
      "grand_total": 4500.00
    }
    ```

- **Add / Increment Item in Cart:** `POST /api/v1/customer/cart`
  - **Payload:**
    ```json
    {
      "product_id": "gold-standard-whey",
      "quantity": 1,
      "selected_flavor": "Double Rich Chocolate",
      "selected_size": "2kg"
    }
    ```

- **Update Quantity:** `PUT /api/v1/customer/cart/{id}`
  - **Payload:** `{"quantity": 3}`

- **Remove Item:** `DELETE /api/v1/customer/cart/{id}` (Returns 204 No Content).

---

### Wishlist
- **Get Wishlist:** `GET /api/v1/customer/wishlist`
- **Add to Wishlist:** `POST /api/v1/customer/wishlist`
  - **Payload:** `{"product_id": "gold-standard-whey"}`
- **Remove from Wishlist:** `DELETE /api/v1/customer/wishlist/{productId}`

---

### Address Management
- **List Addresses:** `GET /api/v1/customer/addresses`
- **Create Address:** `POST /api/v1/customer/addresses`
  - **Payload:**
    ```json
    {
      "label": "Home",
      "full_name": "John Doe",
      "phone": "+201012345678",
      "street": "15 Al-Nasr St",
      "city": "Cairo",
      "state": "Nasr City",
      "country": "Egypt",
      "postal_code": "11765",
      "is_default": true
    }
    ```
- **Get Address Details:** `GET /api/v1/customer/addresses/{id}`
- **Update Address:** `PUT /api/v1/customer/addresses/{id}`
- **Delete Address:** `DELETE /api/v1/customer/addresses/{id}`
- **Set as Default:** `POST /api/v1/customer/addresses/{id}/set-default`

---

### Orders & Checkout

#### 1. Price Quote (Live Pricing Engine)
Calculates exact subtotal, shipping fee, tax rate, and free delivery thresholds before order creation.
- **Endpoint:** `POST /api/v1/customer/orders/quote`
- **Request Body:**
  ```json
  {
    "items": [
      {
        "product_id": "gold-standard-whey",
        "quantity": 2,
        "selected_size": "2kg"
      }
    ]
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "data": {
      "subtotal": 4500.00,
      "shipping_cost": 0.00,
      "tax_amount": 630.00,
      "discount": 0.00,
      "total_amount": 5130.00,
      "tax_rate": 14.0,
      "free_delivery_threshold": 2000.0,
      "currency": "EGP",
      "is_accepting_orders": true
    }
  }
  ```

#### 2. Place Order
- **Endpoint:** `POST /api/v1/customer/orders`
- **Request Body:**
  ```json
  {
    "address_id": 4,
    "payment_method": "cash",
    "notes": "Please call before arrival",
    "items": [
      {
        "product_id": "gold-standard-whey",
        "variant_id": "variant-uuid-1",
        "quantity": 2,
        "selected_flavor": "Double Rich Chocolate",
        "selected_size": "2kg"
      }
    ]
  }
  ```
- **Response (201 Created):** Returns the completed `OrderResource` with unique UUID order ID and auto-clears the customer's cart.

#### 3. View Orders History
- **Endpoint:** `GET /api/v1/customer/orders` (Optional: `?limit=10`)

#### 4. Order Details
- **Endpoint:** `GET /api/v1/customer/orders/{id}`

---

### Payment Gateway
- **Initiate Payment:** `POST /api/v1/customer/orders/{id}/pay`
- **Request Body:**
  ```json
  {
    "gateway": "paymob",
    "first_name": "Ahmed",
    "last_name": "Mohamed"
  }
  ```
- **Response (200 OK):** Returns payment URL or client secret (e.g. Paymob iframe URL or Stripe intent).

---

### Product Reviews
- **Add or Update Review:** `POST /api/v1/customer/products/{productId}/reviews`
  - **Payload:** `{"rating": 5, "comment": "Excellent product and super fast delivery!"}`
- **Get Authenticated User Review:** `GET /api/v1/customer/products/{productId}/user-review`
- **Delete Review:** `DELETE /api/v1/customer/reviews/{id}`

---

### Customer Notifications
- `GET /api/v1/customer/notifications`: Paginated list of notifications with `unread_count`.
- `GET /api/v1/customer/notifications/unread-count`: Returns `{"unread_count": 3}`.
- `POST /api/v1/customer/notifications/{id}/read`: Marks single notification as read.
- `POST /api/v1/customer/notifications/read-all`: Marks all notifications as read.

---

### Support Tickets & Complaints
- `GET /api/v1/customer/support/tickets`: List user's support tickets.
- `POST /api/v1/customer/support/tickets`: Submit a new ticket.
  - **Payload:**
    ```json
    {
      "subject": "Inquiry about delivery schedule",
      "category": "shipping",
      "priority": "normal",
      "order_id": "9d41b52a-9f5b-419b-a486-2a62886f4a30",
      "message": "When will this order be shipped?",
      "attachments": ["https://storage.strongermuscles.com/tickets/screen.jpg"]
    }
    ```
- `GET /api/v1/customer/support/tickets/{id}`: Detailed ticket with full chat conversation thread.
- `POST /api/v1/customer/support/tickets/{id}/messages`: Add reply message.
- `POST /api/v1/customer/support/tickets/{id}/close`: Close ticket.

---

## 4. Payment Webhooks

- **Endpoint:** `POST /api/v1/webhooks/payment/{gateway}`
  - Supported gateways: `paymob`, `stripe`.
  - Automatically verifies signature, updates order status to `processing`, updates `payment_status` to `paid`, and records transaction log.

---

## 5. Admin Dashboard APIs (Protected)

Requires `Authorization: Bearer {admin_token}` with Admin guard (`auth:admin-api` + `isAdmin`).

### Admin Auth
- **Login:** `POST /api/v1/admin/login`
  - **Payload:** `{"email": "admin@strongermuscles.com", "password": "..."}`
  - **Response (200 OK):** Returns `{ "status": "success", "token": "...", "user": { "role": "super_admin" } }`.
- **Register:** `POST /api/v1/admin/register`
- **Profile:** `GET /api/v1/admin/profile`
- **Logout:** `POST /api/v1/admin/logout`

### Admin Theme Mode
Syncs theme preferences (dark / light) across the admin's devices.
- **Get Theme:** `GET /api/v1/admin/profile/theme`
- **Update Theme:** `PUT /api/v1/admin/profile/theme`
  - **Payload:** `{"mode": "dark"}`

### Dashboard Metrics & Sidebar Badges
Calculated directly inside database queries for speed and efficiency:
- **Dashboard Home Stats:** `GET /api/v1/admin/dashboard`
  - Returns: `total_revenue`, `total_orders`, `total_users`, `total_products`, `pending_orders`, `processing_orders`, `shipped_orders`, `delivered_orders`, `cancelled_orders`, `in_stock`, `low_stock`, `out_of_stock`.
- **Navigation Badges:** `GET /api/v1/admin/navigation/badges`
  - Returns counts for sidebar badge pills: `pending_orders`, `open_tickets`, `low_stock_products`, `unread_notifications`.

### Store Settings
- **Get Store Settings:** `GET /api/v1/admin/settings`
- **Update Store Settings:** `PUT /api/v1/admin/settings` *(Super Admin & Admin)*
  - **Payload:**
    ```json
    {
      "store_name": "Stronger Muscles Egypt",
      "support_email": "support@strongermuscles.com",
      "support_phone": "+201000000000",
      "currency": "EGP",
      "delivery_fee": 50.00,
      "free_delivery_threshold": 1000.00,
      "tax_rate": 14.0,
      "is_store_open": true,
      "order_notifications_enabled": true,
      "maintenance_mode": false
    }
    ```

### Products Management
- **List All Products:** `GET /api/v1/admin/products`
- **Create Product:** `POST /api/v1/admin/products`
  - **Payload:**
    ```json
    {
      "id": "c4-pre-workout",
      "name": { "ar": "سي فور بري ورك اوت", "en": "C4 Pre-Workout" },
      "description": { "ar": "طاقة وتركيز فائق للتمرين", "en": "Explosive energy and performance" },
      "category_id": "pre-workout",
      "brand_id": "cellucor",
      "price": 1400.00,
      "discount_price": 1250.00,
      "stock_quantity": 30,
      "flavors": ["Fruit Punch", "Blue Razz", "Watermelon"],
      "product_sizes": [
        { "size": "30 Servings", "price": 1400.00, "discount_price": 1250.00 },
        { "size": "60 Servings", "price": 2400.00, "discount_price": 2200.00 }
      ],
      "product_variants": [
        {
          "sku": "C4-30-PUNCH",
          "price": 1400.00,
          "discount_price": 1250.00,
          "stock_quantity": 15,
          "attributes": { "size": "30 Servings", "flavor": "Fruit Punch" },
          "is_active": true
        }
      ],
      "image_urls": ["https://.../img1.jpg"],
      "is_active": true,
      "featured": true,
      "best_seller": false,
      "new_arrival": true
    }
    ```
- **Show Product:** `GET /api/v1/admin/products/{id}`
- **Update Product:** `PUT /api/v1/admin/products/{id}`
- **Delete Product:** `DELETE /api/v1/admin/products/{id}` *(Super Admin & Admin)*

### Brands Management
- **List Brands:** `GET /api/v1/admin/brands`
- **Create Brand:** `POST /api/v1/admin/brands`
  - **Payload:**
    ```json
    {
      "name": { "ar": "أوبتيموم نيوترشن", "en": "Optimum Nutrition" },
      "image_url": "https://storage.strongermuscles.com/brands/on.png",
      "is_active": true
    }
    ```
- **Show Brand:** `GET /api/v1/admin/brands/{id}`
- **Update Brand:** `PUT /api/v1/admin/brands/{id}`
- **Delete Brand:** `DELETE /api/v1/admin/brands/{id}` *(Super Admin & Admin)*

### Categories Management
- **List Categories:** `GET /api/v1/admin/categories`
- **Create Category:** `POST /api/v1/admin/categories`
- **Show Category:** `GET /api/v1/admin/categories/{id}`
- **Update Category:** `PUT /api/v1/admin/categories/{id}`
- **Delete Category:** `DELETE /api/v1/admin/categories/{id}` *(Super Admin & Admin)*

### Promotions Management
- **List Promos:** `GET /api/v1/admin/promos`
- **Create Promo:** `POST /api/v1/admin/promos`
- **Show Promo:** `GET /api/v1/admin/promos/{id}`
- **Update Promo:** `PUT /api/v1/admin/promos/{id}`
- **Delete Promo:** `DELETE /api/v1/admin/promos/{id}` *(Super Admin & Admin)*

### Orders Management
- **Order Real-time Alerts Feed:** `GET /api/v1/admin/orders/alerts`
  - Returns count of pending/processing orders and recent 24h order notifications.
- **List Orders:** `GET /api/v1/admin/orders` (Supports `?status=pending&limit=20`)
- **Show Order Details:** `GET /api/v1/admin/orders/{id}`
- **Update Order:** `PATCH /api/v1/admin/orders/{id}`
- **Update Status Specifically:** `PATCH /api/v1/admin/orders/{id}/status`
  - **Payload:**
    ```json
    {
      "status": "shipped",
      "payment_status": "paid",
      "tracking_number": "TRK-98721342"
    }
    ```

### Customer Accounts Management
- **List Users:** `GET /api/v1/admin/users` *(Super Admin & Admin)*
  - Returns total users, active/inactive counts, spend per user, order counts, and address history.

### Support Tickets Management
- **Tickets Summary & Metrics:** `GET /api/v1/admin/support/tickets/stats`
- **List Tickets:** `GET /api/v1/admin/support/tickets` (Filters: `status`, `priority`, `category`, `admin_id`, `search`, `per_page`)
- **Ticket Thread:** `GET /api/v1/admin/support/tickets/{id}`
- **Admin Reply / Internal Note:** `POST /api/v1/admin/support/tickets/{id}/messages`
  - **Payload:**
    ```json
    {
      "message": "We contacted the shipping carrier and delivery will occur tomorrow.",
      "is_internal_note": false,
      "status": "in_progress"
    }
    ```
- **Update Status:** `PATCH /api/v1/admin/support/tickets/{id}/status`
- **Update Priority:** `PATCH /api/v1/admin/support/tickets/{id}/priority`
- **Assign Admin:** `PATCH /api/v1/admin/support/tickets/{id}/assign`
- **Delete Ticket:** `DELETE /api/v1/admin/support/tickets/{id}`

### Notification Broadcasting
- **List Broadcasts:** `GET /api/v1/admin/notifications`
- **Send Broadcast:** `POST /api/v1/admin/notifications`
  - **Payload:**
    ```json
    {
      "title": "Weekend Deal",
      "body": "20% off all protein supplements for a limited time!",
      "type": "promo",
      "target_type": "all",
      "image_url": "https://storage.strongermuscles.com/promos/weekend.jpg"
    }
    ```
- **Delete Broadcast:** `DELETE /api/v1/admin/notifications/{id}`

### Media Services
- **Product Image:** `POST /api/v1/admin/upload/product-image` (Multipart `image`)
- **Category Image:** `POST /api/v1/admin/upload/category-image` (Multipart `image`)
- **Promo Image:** `POST /api/v1/admin/upload/promo-image` (Multipart `image`)
- **Generic Image:** `POST /api/v1/admin/upload/image` (Multipart `image`)
- **Delete Stored Image:** `POST /api/v1/admin/upload/delete`
  - **Payload:** `{"path": "products/sample.jpg"}`

---

## 6. Standard Error Responses

Errors are consistently formatted across all endpoints with standard HTTP status codes:

### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "message": "You do not have permission to perform this action."
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "Product not found or inactive"
}
```

### 422 Unprocessable Entity (Validation Error)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 500 Internal Server Error
```json
{
  "status": "error",
  "message": "An unexpected error occurred"
}
```
