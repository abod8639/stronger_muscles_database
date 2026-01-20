# Stronger Muscles API Documentation 🚀

This document provides definitive information for integrating the **Stronger Muscles Mobile App (Flutter)** and the **Admin Dashboard** with the backend API.

---
/home/dexter/datebase/stronger_muscles_dateabase
## 📌 General Information

### 🌐 Base URLs
- **Development Server:** `http://localhost:8080/api/v1` (or your local IP)
- **Production Server:** `https://api.strongermuscles.com/api/v1`

### 🔑 Authentication
The API utilizes **Laravel Sanctum** for secure authentication.
- All protected routes require a `Bearer {token}` in the `Authorization` header.
- Required Headers:
  ```http
  Accept: application/json
  Authorization: Bearer {your_token}
  ```

---

## 🛠 Authentication Endpoints

### 1. Login
- **URL:** `POST /api/v1/auth/login`
- **Body:**
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **Success Response (200 OK):**
  ```json
  {
    "status": "success",
    "token": "12|v8...token...v8",
    "user": { "id": "uuid", "name": "John Doe", "email": "...", "photo_url": "...", "created_at": "..." }
  }
  ```

### 2. User Registration
- **URL:** `POST /api/v1/auth/register`
- **Body:**
  ```json
  {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "securepassword123"
  }
  ```

### 3. Google Sign-In
- **URL:** `POST /api/v1/auth/google-signin`
- **Body:**
  ```json
  {
    "email": "john@gmail.com",
    "name": "John Google",
    "photo_url": "https://url-to-photo.com"
  }
  ```
- **Note:** Returns an `access_token` and `token_type: Bearer`.

---

## 🍎 Shop & Catalog (Public)

### Categories
- `GET /api/v1/shop/categories`: List all categories.
- `GET /api/v1/shop/categories/{id}`: View specific category details.

### Products
- `GET /api/v1/shop/products`: List active products.
  - **Query Params:**
    - `category`: Filter by category ID.
    - `search`: Search by name or description.
    - `page`: Pagination page number (default: 1).
- `GET /api/v1/shop/products/{id}`: Detailed product view including flavors, nutrition facts, and stock.

---

## 👤 Customer API (Protected - Bearer Token Required)

### Profile & Identity
- `GET /api/v1/customer/profile`: Get authenticated user profile and all saved addresses.
- `POST /api/v1/auth/update-profile`: Update personal details (name, email, phone, photo) and manage addresses.
  - **Addresses Payload:** Supports an array of address objects.

### Shopping Cart
- `GET /api/v1/customer/cart`: Retrieve the user's current shopping cart.
- `POST /api/v1/customer/cart`: Add or update an item in the cart.
- `PUT /api/v1/customer/cart/{id}`: Update specific cart item details.
- `DELETE /api/v1/customer/cart/{id}`: Remove item from cart.

### Orders
- `GET /api/v1/customer/orders`: View order history with status and items.
- `POST /api/v1/customer/orders`: Place a new order.
  - **Required Fields:** `id` (Client-side UUID), `order_items`, `subtotal`, `shippingCost`, `total_amount`.

---

## ⚙️ Admin Dashboard API (Protected + Admin Middleware)

### Inventory Management
- `GET /api/v1/admin/products`: List all products (includes inactive).
- `POST /api/v1/admin/products`: Create a new product.
- `PUT /api/v1/admin/products/{id}`: Update product details.
- `DELETE /api/v1/admin/products/{id}`: Remove a product.

### Category Management
- `GET /api/v1/admin/categories`: Manage all categories.
- `POST /api/v1/admin/categories`: Create category.
- `PUT /api/v1/admin/categories/{id}`: Update category.

### Order Fulfillment
- `GET /api/v1/admin/orders`: View all platform orders.
- `PATCH /api/v1/admin/orders/{id}`: Update status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`).

### User Management
- `GET /api/v1/admin/users`: View all registered users and their statistics.

### 🖼 Media Services
- `POST /api/v1/admin/upload/image`: General image upload.
- `POST /api/v1/admin/upload/product-image`: Optimized product image upload.
- `POST /api/v1/admin/upload/category-image`: Optimized category image upload.
- `POST /api/v1/admin/upload/delete`: Delete a file from storage.

---

## ⚠️ Standard Error Responses

Errors are returned as JSON with descriptive messages and validation details.

- `401 Unauthorized`: Authentication token is missing or has expired.
- `403 Forbidden`: User profile lacks the `admin` role required for this endpoint.
- `404 Not Found`: The requested resource (product, order, etc.) does not exist.
- `422 Unprocessable Entity`: Validation failure.
  ```json
  {
    "errors": {
      "email": ["The email has already been taken."],
      "password": ["The password must be at least 8 characters."]
    }
  }
  ```
