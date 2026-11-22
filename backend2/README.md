# Damas Guide — Backend (Laravel)

الباك اند الجديد لمشروع "دليل دمشق"، منقول من Django (`../backend`) إلى Laravel 13.
**قاعدة أساسية:** الـ API لازم يبقى مطابق للقديم (نفس المسارات ونفس شكل الـ JSON) حتى لا نعيد ربط الواجهات.

## المتطلبات

- PHP 8.3+ مع الامتدادات: `pdo_pgsql`, `pgsql`, `mbstring`, `intl`
- Composer
- Node.js + npm (للواجهة المدمجة)
- PostgreSQL

## التثبيت (أول مرة)

من مجلد `backend2`:

```
composer install
copy .env.example .env
php artisan key:generate
```

أنشئ قاعدة البيانات وعدّل بيانات `DB_*` بملف `.env`:

```
psql -U postgres -c "CREATE DATABASE damascus_guide_db;"
```

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=damascus_guide_db
DB_USERNAME=postgres
DB_PASSWORD=كلمة_السر
```

ثم:

```
php artisan migrate
php artisan db:seed --class=AdminAreaSeeder
npm install
```

> بديل بخطوة وحدة بعد تجهيز القاعدة: `composer setup`

## التشغيل

```
php artisan serve
npm run dev
```

- الـ API: `http://localhost:8000/api/...`
- توثيق الـ API (Swagger): `http://localhost:8000/docs/api`
- افتح التوثيق بنفس الـ host الموجود في `APP_URL` حتى لا يظهر خطأ CORS في "Try It".

## الـ Seeders

شغّلها يدوياً بالترتيب. كل Seeder بيستخدم `updateOrCreate` فإعادة تشغيله آمنة.

| الترتيب | الأمر | يملأ جدول | ملف البيانات | يعتمد على |
|---|---|---|---|---|
| 1 | `php artisan db:seed --class=AdminAreaSeeder` | `admin_areas` | `database/data/damascus_admin_areas.geojson` | — |


## الاختبارات وجودة الكود

```
php artisan test                      # الاختبارات (Pest + SQLite in-memory)
composer lint                         # تنسيق الكود (Pint)
composer test                         # lint + phpstan + الاختبارات
```

## هيكلة الكود

```
Controller (رفيع: يستقبل ويرد فقط)
   └── Service (المنطق)   app/Services
          └── Model       app/Models
Form Requests  → التحقق        app/Http/Requests
API Resources  → شكل الـ JSON  app/Http/Resources
Support        → دوال مساعدة   app/Support (SearchIndex, Geometry)
```

الـ Controller ما فيه منطق: كل شيء بالـ Service.

