---
name: Stronger Muscles Database AI Skills
description: Guidelines and best practices for developing the Stronger Muscles Laravel backend application.
---

# 🤖 AI Assistant Skills: Stronger Muscles Database (Laravel)

هذا الملف يعمل كمرجع لك عند العمل على تطبيق **Stronger Muscles Database** المبني بـ Laravel. التزم دائماً بالقواعد المعمارية ومبادئ التصميم التالية لضمان نظافة وحماية الكود.

## 🏗️ 1. هندسة التطبيق (Software Architecture)

- **Service Layer Pattern:** تجنب وضع المنطق المعقد (Business Logic) داخل الـ `Controllers`. يجب إنشاء طبقة خدمات (Services) لمعالجة البيانات.
- **Repository Pattern:** استخدم نمط المستودع (Repository Pattern) للتعامل مع قاعدة البيانات وفصل استعلامات `Eloquent` عن باقي أجزاء التطبيق.
- **Controllers:** يجب أن تكون وحدات التحكم بسيطة ومختصرة، وتقتصر مهمتها على استقبال الطلب، التحقق من صحته (Validation)، استدعاء الـ Service/Repository، وإرجاع الاستجابة (Response).

## 📡 2. تطوير واجهات برمجة التطبيقات (API Development)

- **API Resources:** لبناء استجابات موحدة واحترافية، استخدم **Laravel API Resources** بشكل دائم. تجنب إرجاع كائنات `Eloquent Models` بشكل مباشر للعميل (Frontend).
- **HTTP Status Codes:** التزم باستخدام أكواد حالة HTTP الصحيحة (مثلاً: `200` للنجاح، `201` للإنشاء، `404` عند عدم العثور على المورد، `422` لأخطاء التحقق، و `500` لأخطاء الخادم).
- **Form Requests:** استخدم `Form Requests` المخصصة (`php artisan make:request`) للتحقق من البيانات (Validation) بدلاً من التحقق المباشر داخل الـ `Controller`.

## 🗄️ 3. قاعدة البيانات والأداء (Database & Performance)

- **N+1 Query Problem:** انتبه لمشكلة (N+1) عند جلب البيانات المرتبطة (Relations). استخدم `Eager Loading` بواسطة دوال مثل `with()` أو `load()` بدلاً من `Lazy Loading`.
- **التخزين المؤقت (Caching):** استخدم التخزين المؤقت لتقليل الضغط على قاعدة البيانات للبيانات الثابتة نسبياً، مع تفضيل استخدام `Redis` كـ Cache Driver متى ما أمكن.
- **الطوابير (Queues):** للعمليات الثقيلة أو المهام التي تأخذ وقتاً (مثل إرسال البريد الإلكتروني، أو معالجة الصور)، استخدم Laravel Queues.

## 🔐 4. الأمان والمصادقة (Security & Authentication)

- استخدم **Laravel Sanctum** (أو Passport حسب إعداد المشروع) لمصادقة الـ APIs (Token-Based Authentication).
- **Sanitization:** قم بتنظيف المدخلات لمنع الثغرات الأمنية مثل XSS.
- **Mass Assignment:** احذر من تعيين البيانات بشكل عشوائي، قم بحماية الحقول غير المرغوب في تعديلها عبر خاصية `$fillable` أو `$guarded` في የ `Eloquent Model`.

## 🧪 5. الاختبار (Testing)

- دائمًا قم بكتابة أو تحديث اختبارات الميزات (**Feature Tests**) عند إضافة أو تعديل النهايات (Endpoints) لضمان سلامة العمليات وعدم كسر وظائف موجودة مسبقاً.

## 📦 6. إدارة حالة قاعدة البيانات (Database Schema Overview)

- المشروع يدير جداول متعلقة بمسار متجر ومبيعات: `Categories` (التصنيفات)، `Products` (المنتجات)، `Orders` (الطلبات ومشتملاتها)، و `Users` (المستخدمون).
- أي تعديلات هيكلية يجب أن تتم حصرياً عبر الـ **Migrations**.

---

**تذكر دائمًا:** الدّقة والموثوقية هما الأساس. لا تقدم افتراضات بدلاً من قراءة الكود والمشروع. التزم بتوجيهات المستخدم، وفي حال الشك اسأل سؤالاً واحداً محدداً ولا تخترع الإجابات.
