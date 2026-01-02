# 📊 مخطط قاعدة البيانات - تطبيق Stronger Muscles

## 📋 نظرة عامة

هذا المستند يشرح بالتفصيل هيكل قاعدة البيانات لتطبيق **Stronger Muscles**، وهو تطبيق للتجارة الإلكترونية متخصص في بيع المكملات الغذائية ومنتجات بناء العضلات.

---

## 🎯 ملخص الجداول

التطبيق يحتوي على **8 جداول رئيسية**:

| الجدول | الوصف | العلاقات |
|--------|-------|---------|
| `users` | بيانات المستخدمين والحسابات | له علاقة مع Orders, Cart Items |
| `categories` | تصنيفات المنتجات | له علاقة مع Products |
| `products` | معلومات المنتجات | ينتمي لـ Category |
| `cart_items` | سلة التسوق | ينتمي لـ User & Product |
| `orders` | الطلبات | ينتمي لـ User |
| `order_items` | عناصر الطلب | ينتمي لـ Order & Product |
| `password_reset_tokens` | رموز إعادة تعيين كلمة المرور | مرتبط بـ Users |
| `sessions` | جلسات المستخدمين | مرتبط بـ Users |

---

## 📑 تفاصيل الجداول

### 1️⃣ جدول المستخدمين (Users)

**الغرض**: تخزين معلومات المستخدمين وحساباتهم.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    photo_url VARCHAR(255) NULL,
    phone_number VARCHAR(255) NULL,
    default_address_id VARCHAR(255) NULL,
    preferred_language VARCHAR(255) DEFAULT 'ar',
    notifications_enabled BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف المستخدم الفريد (رقم تلقائي)
- **name**: اسم المستخدم الكامل
- **email**: البريد الإلكتروني (فريد)
- **email_verified_at**: تاريخ تأكيد البريد الإلكتروني
- **password**: كلمة المرور المشفرة (bcrypt)
- **photo_url**: رابط صورة الملف الشخصي
- **phone_number**: رقم الهاتف
- **default_address_id**: معرف العنوان الافتراضي للشحن
- **preferred_language**: اللغة المفضلة (افتراضياً: العربية)
- **notifications_enabled**: تفعيل الإشعارات
- **is_active**: حالة الحساب (نشط/غير نشط)

#### **العلاقات**:
- **Has Many**: Orders (الطلبات)
- **Has Many**: Cart Items (عناصر السلة)

#### **Indexes**:
- `PRIMARY KEY`: id
- `UNIQUE INDEX`: email

---

### 2️⃣ جدول التصنيفات (Categories)

**الغرض**: تنظيم المنتجات في تصنيفات (مثل: بروتين، أحماض أمينية، فيتامينات).

```sql
CREATE TABLE categories (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(255) NULL,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف التصنيف الفريد (نصي - UUID أو Slug)
- **name**: اسم التصنيف (مثل: "بروتين واي")
- **description**: وصف التصنيف
- **image_url**: صورة التصنيف
- **sort_order**: ترتيب العرض (للفرز)
- **is_active**: هل التصنيف نشط؟

#### **العلاقات**:
- **Has Many**: Products (المنتجات)

#### **Indexes**:
- `PRIMARY KEY`: id

---

### 3️⃣ جدول المنتجات (Products)

**الغرض**: تخزين معلومات المنتجات المتاحة للبيع.

```sql
CREATE TABLE products (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    discount_price DECIMAL(10, 2) NULL,
    image_urls TEXT NULL,
    description TEXT NOT NULL,
    category_id VARCHAR(255) NOT NULL,
    stock_quantity INTEGER DEFAULT 0,
    average_rating DECIMAL(3, 2) DEFAULT 0,
    review_count INTEGER DEFAULT 0,
    brand VARCHAR(255) NULL,
    serving_size VARCHAR(255) NULL,
    servings_per_container INTEGER NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف المنتج الفريد
- **name**: اسم المنتج
- **price**: السعر الأصلي
- **discount_price**: السعر بعد الخصم (إن وجد)
- **image_urls**: روابط الصور (JSON أو نص منفصل بفواصل)
- **description**: وصف المنتج التفصيلي
- **category_id**: معرف التصنيف
- **stock_quantity**: الكمية المتوفرة في المخزون
- **average_rating**: متوسط التقييم (من 0 إلى 5)
- **review_count**: عدد المراجعات
- **brand**: العلامة التجارية
- **serving_size**: حجم الحصة (مثل: "30g")
- **servings_per_container**: عدد الحصص في الحاوية
- **is_active**: هل المنتج نشط ومتاح؟

#### **العلاقات**:
- **Belongs To**: Category (التصنيف)
- **Has Many**: Cart Items (عناصر السلة)
- **Has Many**: Order Items (عناصر الطلب)

#### **Indexes**:
- `PRIMARY KEY`: id
- `INDEX`: category_id

---

### 4️⃣ جدول سلة التسوق (Cart Items)

**الغرض**: تخزين المنتجات المضافة إلى سلة التسوق لكل مستخدم.

```sql
CREATE TABLE cart_items (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    product_id VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_urls TEXT NULL,
    quantity INTEGER DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف العنصر في السلة
- **user_id**: معرف المستخدم
- **product_id**: معرف المنتج
- **product_name**: اسم المنتج (نسخة مخزنة)
- **price**: السعر وقت الإضافة
- **image_urls**: روابط صور المنتج
- **quantity**: الكمية المطلوبة
- **added_at**: تاريخ الإضافة للسلة

#### **العلاقات**:
- **Belongs To**: User (المستخدم)
- **Belongs To**: Product (المنتج)

#### **Indexes**:
- `PRIMARY KEY`: id
- `INDEX`: user_id

#### **ملاحظات**:
- يتم تخزين `product_name` و `price` كنسخة ثابتة لتجنب التأثر بتغييرات المنتج
- يمكن حذف العناصر عند إتمام الطلب أو يدوياً من المستخدم

---

### 5️⃣ جدول الطلبات (Orders)

**الغرض**: تتبع الطلبات المكتملة وحالتها.

```sql
CREATE TABLE orders (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    order_date DATETIME NOT NULL,
    status VARCHAR(255) DEFAULT 'pending',
    payment_status VARCHAR(255) DEFAULT 'pending',
    payment_method VARCHAR(255) DEFAULT 'card',
    address_id VARCHAR(255) NOT NULL,
    shipping_address_snapshot TEXT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_cost DECIMAL(10, 2) DEFAULT 0,
    discount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    tracking_number VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف الطلب الفريد
- **user_id**: معرف المستخدم صاحب الطلب
- **order_date**: تاريخ الطلب
- **status**: حالة الطلب (pending, processing, shipped, delivered, cancelled)
- **payment_status**: حالة الدفع (pending, paid, failed, refunded)
- **payment_method**: طريقة الدفع (card, cash, wallet)
- **address_id**: معرف عنوان الشحن
- **shipping_address_snapshot**: نسخة من عنوان الشحن (JSON/TEXT)
- **subtotal**: المجموع الفرعي (قبل الشحن والخصم)
- **shipping_cost**: تكلفة الشحن
- **discount**: قيمة الخصم
- **total_amount**: المبلغ الإجمالي النهائي
- **tracking_number**: رقم التتبع للشحن
- **notes**: ملاحظات إضافية

#### **حالات الطلب (Status)**:
- `pending`: قيد الانتظار
- `processing`: قيد المعالجة
- `shipped`: تم الشحن
- `delivered`: تم التوصيل
- `cancelled`: ملغى

#### **حالات الدفع (Payment Status)**:
- `pending`: قيد الانتظار
- `paid`: مدفوع
- `failed`: فشل
- `refunded`: مسترد

#### **العلاقات**:
- **Belongs To**: User (المستخدم)
- **Has Many**: Order Items (عناصر الطلب)

#### **Indexes**:
- `PRIMARY KEY`: id
- `INDEX`: user_id

---

### 6️⃣ جدول عناصر الطلب (Order Items)

**الغرض**: تخزين المنتجات المطلوبة ضمن كل طلب.

```sql
CREATE TABLE order_items (
    id VARCHAR(255) PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL,
    product_id VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity INTEGER NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **id**: معرف العنصر
- **order_id**: معرف الطلب
- **product_id**: معرف المنتج
- **product_name**: اسم المنتج (نسخة مخزنة)
- **unit_price**: سعر الوحدة وقت الطلب
- **quantity**: الكمية المطلوبة
- **subtotal**: المجموع الفرعي (unit_price × quantity)
- **image_url**: صورة المنتج

#### **العلاقات**:
- **Belongs To**: Order (الطلب)
- **Belongs To**: Product (المنتج)

#### **Indexes**:
- `PRIMARY KEY`: id
- `INDEX`: order_id

#### **ملاحظات**:
- يتم تخزين `product_name` و `unit_price` كنسخة ثابتة لضمان عدم تغير تفاصيل الطلب
- `subtotal` يُحسب تلقائياً: `unit_price * quantity`

---

### 7️⃣ جدول رموز إعادة تعيين كلمة المرور (Password Reset Tokens)

**الغرض**: إدارة عملية إعادة تعيين كلمة المرور.

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

#### **الحقول**:
- **email**: البريد الإلكتروني للمستخدم
- **token**: الرمز المؤقت
- **created_at**: تاريخ الإنشاء

---

### 8️⃣ جدول الجلسات (Sessions)

**الغرض**: إدارة جلسات المستخدمين.

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
```

#### **الحقول**:
- **id**: معرف الجلسة
- **user_id**: معرف المستخدم (إذا كان مسجل دخول)
- **ip_address**: عنوان IP
- **user_agent**: معلومات المتصفح
- **payload**: بيانات الجلسة
- **last_activity**: آخر نشاط

---

## 🔗 العلاقات بين الجداول (ERD Relationships)

```
Users (1) ──────── (*) Cart_Items
  │
  └── (1) ──────── (*) Orders
                      │
                      └── (1) ──────── (*) Order_Items
                                           │
                                           └── (*) ──────── (1) Products
                                                                │
                                                                └── (*) ──────── (1) Categories
```

### **تفصيل العلاقات**:

1. **User → Cart Items**: علاقة واحد لمتعدد (One-to-Many)
   - مستخدم واحد يمكنه إضافة عدة منتجات للسلة

2. **User → Orders**: علاقة واحد لمتعدد (One-to-Many)
   - مستخدم واحد يمكنه إنشاء عدة طلبات

3. **Order → Order Items**: علاقة واحد لمتعدد (One-to-Many)
   - طلب واحد يحتوي على عدة منتجات

4. **Product → Order Items & Cart Items**: علاقة واحد لمتعدد (One-to-Many)
   - منتج واحد يمكن أن يكون في عدة طلبات وسلال

5. **Category → Products**: علاقة واحد لمتعدد (One-to-Many)
   - تصنيف واحد يحتوي على عدة منتجات

---

## 📊 أمثلة على البيانات (Sample Data)

### **Categories** (التصنيفات):
```json
[
  {
    "id": "protein",
    "name": "بروتين",
    "description": "مكملات البروتين لبناء العضلات",
    "image_url": "https://example.com/categories/protein.jpg",
    "sort_order": 1,
    "is_active": true
  },
  {
    "id": "bcaa",
    "name": "أحماض أمينية BCAA",
    "description": "أحماض أمينية متفرعة السلسلة",
    "image_url": "https://example.com/categories/bcaa.jpg",
    "sort_order": 2,
    "is_active": true
  }
]
```

### **Products** (المنتجات):
```json
[
  {
    "id": "whey-gold-5lb",
    "name": "Optimum Nutrition Gold Standard Whey",
    "price": 250.00,
    "discount_price": 225.00,
    "image_urls": "[\"https://example.com/products/whey1.jpg\", \"https://example.com/products/whey2.jpg\"]",
    "description": "بروتين واي ممتاز لبناء العضلات",
    "category_id": "protein",
    "stock_quantity": 50,
    "average_rating": 4.8,
    "review_count": 127,
    "brand": "Optimum Nutrition",
    "serving_size": "30g",
    "servings_per_container": 74,
    "is_active": true
  }
]
```

### **Users** (المستخدمين):
```json
{
  "id": 1,
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "password": "$2y$12$...", // bcrypt hash
  "phone_number": "+966501234567",
  "preferred_language": "ar",
  "notifications_enabled": true,
  "is_active": true
}
```

### **Orders** (الطلبات):
```json
{
  "id": "ORD-2025-001",
  "user_id": "1",
  "order_date": "2025-12-31 10:30:00",
  "status": "processing",
  "payment_status": "paid",
  "payment_method": "card",
  "address_id": "addr-123",
  "shipping_address_snapshot": "{\"street\": \"شارع الملك فهد\", \"city\": \"الرياض\"}",
  "subtotal": 225.00,
  "shipping_cost": 25.00,
  "discount": 0.00,
  "total_amount": 250.00,
  "tracking_number": "TRACK-123456"
}
```

---

## 🛠️ ملاحظات تقنية

### **1. أنواع المعرفات (IDs)**:
- **Users**: `BIGINT` (أرقام تلقائية)
- **Products, Categories, Orders, Cart Items, Order Items**: `VARCHAR(255)` (UUID أو Slugs)

### **2. تخزين الصور**:
- **image_urls**: يُخزن كـ JSON أو نص منفصل بفواصل
- مثال: `["url1.jpg", "url2.jpg"]`

### **3. الحقول النقدية**:
- جميع الأسعار: `DECIMAL(10, 2)` (10 أرقام، منها 2 بعد الفاصلة)
- مثال: `250.75`

### **4. التقييمات**:
- **average_rating**: `DECIMAL(3, 2)` (من 0.00 إلى 5.00)

### **5. Soft Deletes** (اختياري):
- يمكن إضافة `deleted_at TIMESTAMP NULL` للجداول الرئيسية
- يسمح بالحذف "الناعم" بدلاً من الحذف النهائي

### **6. Foreign Keys** (المفاتيح الأجنبية):
بسبب استخدام `VARCHAR` للمعرفات، لم يتم تفعيل Foreign Keys في الـ migrations الحالية.

**لإضافة Foreign Keys (اختياري)**:
```php
// في migration Products
$table->foreign('category_id')
      ->references('id')
      ->on('categories')
      ->onDelete('cascade');

// في migration Cart Items
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade');
```

---

## 🔐 الأمان والخصوصية

### **1. حماية كلمات المرور**:
- يجب استخدام `bcrypt` أو `argon2` لتشفير كلمات المرور
- عدم تخزين كلمات مرور بشكل نصي أبداً

### **2. التحقق من البريد الإلكتروني**:
- استخدام حقل `email_verified_at`
- إرسال رابط تفعيل عند التسجيل

### **3. حماية البيانات الحساسة**:
- تشفير بيانات الدفع
- عدم تخزين معلومات بطاقات الائتمان

### **4. Indexes للأداء**:
```sql
-- Indexes موصى بها
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_cart_items_user ON cart_items(user_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_products_active ON products(is_active);
```

---

## 📈 الأداء والتحسينات

### **1. Eager Loading**:
```php
// تجنب N+1 Query Problem
$orders = Order::with(['orderItems.product', 'user'])->get();
```

### **2. Caching**:
```php
// تخزين مؤقت للمنتجات والتصنيفات
Cache::remember('categories', 3600, function () {
    return Category::where('is_active', true)->get();
});
```

### **3. Pagination**:
```php
// تقسيم النتائج لتحسين الأداء
$products = Product::paginate(20);
```

---

## 🚀 خطوات التنفيذ

### **1. تشغيل الـ Migrations**:
```bash
php artisan migrate
```

### **2. إنشاء الـ Seeders**:
```bash
php artisan make:seeder CategorySeeder
php artisan make:seeder ProductSeeder
```

### **3. ملء قاعدة البيانات بالبيانات التجريبية**:
```bash
php artisan db:seed
```

### **4. التحقق من الجداول**:
```bash
php artisan tinker
> DB::table('products')->count();
> DB::table('categories')->count();
```

---

## 📚 الموارد الإضافية

- [Laravel 12 Database Documentation](https://laravel.com/docs/12.x/database)
- [Laravel Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships)
- [Database Normalization Best Practices](https://en.wikipedia.org/wiki/Database_normalization)

---

## ✅ Checklist

- [x] جداول المستخدمين (Users)
- [x] جداول المنتجات والتصنيفات (Products & Categories)
- [x] جداول السلة والطلبات (Cart & Orders)
- [ ] جداول العناوين (Addresses) - **يُنصح بإضافتها**
- [ ] جداول المراجعات (Reviews) - **يُنصح بإضافتها**
- [ ] جداول القسائم (Coupons) - **اختياري**
- [ ] جداول المفضلة (Wishlist) - **اختياري**

---

## 📝 ملاحظات ختامية

- قاعدة البيانات الحالية **تغطي الوظائف الأساسية** للتطبيق
- يُنصح بإضافة جدول **Addresses** لتخزين عناوين الشحن بشكل منفصل
- يمكن إضافة جدول **Reviews** لتمكين المستخدمين من تقييم المنتجات
- جميع الأسعار بالريال السعودي (أو العملة المحلية)
- التطبيق يدعم **اللغة العربية** بشكل افتراضي

---

**آخر تحديث**: 31 ديسمبر 2025  
**الإصدار**: 1.0.0  
**المطور**: Stronger Muscles Team
