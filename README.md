# DIEMS — Damascus International Exhibition Management System
### نظام إدارة معرض دمشق الدولي

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=for-the-badge&logo=react)](https://react.dev)
[![Flutter](https://img.shields.io/badge/Flutter-3.x-02569B?style=for-the-badge&logo=flutter)](https://flutter.dev)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql)](https://www.postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis)](https://redis.io)

---

## 📌 نبذة عن المشروع (Overview)

نظام **DIEMS** هو منصة رقمية متكاملة ومؤتمتة أُعدت لـ **مؤسسة المعارض والأسواق الدولية السورية (SEIFE)** للتحول الرقمي الكامل في إدارة وتشغيل **معرض دمشق الدولي**[cite: 1]. يقدم النظام بدائل رقمية للعمليات اليدوية والورقية التقليدية، ويوفر بيئة تنظيمية وتفاعلية تخدم أربعة أطراف رئيسية: الزوار، الشركات العارضة، أصحاب الفعاليات، وإدارة المعرض[cite: 1].

---

## 🎯 أهداف المشروع (Key Objectives)

* **أتمتة إدارة المعرض بالكامل**: الاستغناء عن التعاملات الورقية في حجز المعارض وتخصيص الأجنحة والتذاكر[cite: 1].
* **تسريع حركة الزوار بالبوابات**: إصدار تذاكر رقمية مشفرة بـ QR Code والتحقق منها فورياً لمنع الازدحام والتزوير[cite: 1].
* **حل مشكلات التضارب والحجز المزدوج**: استخدام آليات قفل ذرية برمجية لمنع تعارض مواعيد الفعاليات والأجنحة[cite: 1].
* **توفير تحليلات لحظية لمصممي القرار**: تزويد الإدارة ببيانات وإحصاءات لحظية حول أعداد الزوار وإشغال القاعات المتاحة[cite: 1].

---

## 🏗️ بنية المنظومة والأنظمة الفرعية (Subsystems Architecture)

يتكون نظام **DIEMS** من أربعة أنظمة فرعية متكاملة ترتبط عبر واجهة خلفية مركزية (RESTful API)[cite: 1]:

1. **تطبيق الزوار (Visitor Mobile App - Flutter)**[cite: 1]:
   * تصفح المعرض ودليل الشركات بدون الحاجة لتسجيل دخول[cite: 1].
   * خريطة تفاعلية قابلة للتحريك والتكبير لعرض القاعات والتنقل إليها[cite: 1].
   * حجز التذاكر المدفوعة عبر بوابة دفع إلكترونية وتوليد رمز QR فريد[cite: 1].
   * تخزين التذاكر المحجوزة محلياً (SQLite) للوصول إليها بدون إنترنت[cite: 1].
   * استعراض جدول الفعاليات اليومي ومعلومات خطوط النقل والمواصلات[cite: 1].

2. **بوابة ويب الشركات وأصحاب الفعاليات (Exhibitors Portal - React.js)**[cite: 1]:
   * تقديم طلبات مشاركة الشركات عبر نموذج متعدد الخطوات مع إرفاق رخص العمل والمستندات[cite: 1].
   * إدارة الملف الشخصي للشركة وإدخال المنتجات والعروض الترويجية[cite: 1].
   * استكمال عمليات الدفع المالي الإلكتروني لمستحقات الأجنحة المحجوزة[cite: 1].
   * تقديم مقترحات الفعاليات مع تزامن التقويم في الوقت الفعلي عبر WebSockets[cite: 1].

3. **لوحة تحكم الإدارة (Admin Dashboard - React.js)**[cite: 1]:
   * مراجعة وإدارة طلبات مشاركة الشركات وتخصيص الأجنحة الآلي والتجميعي[cite: 1].
   * موافقة/رفض الفعاليات مع **الرفض التلقائي للطلبات المتعارضة** باستخدام **Redis Atomic Locks**[cite: 1].
   * إدارة أنواع التذاكر وأسعارها ومراقبة مبيعاتها وإيراداتها لحظياً[cite: 1].
   * نظام CMS كامل لتحرير محتوى المعرض، وإدارة الشكاوى، والمستخدمين والصلاحيات[cite: 1].
   * إدارة النسخ الاحتياطي التلقائي المشفر وتخزينه على **AWS S3**[cite: 1].

4. **واجهة بوابة الدخول والتحقق (Gate Entry Interface - Flutter / React.js)**[cite: 1]:
   * شاشة مسح بملء الشاشة لرموز QR مخصصة لموظفي البوابات[cite: 1].
   * مصادقة رقمية رمزية تتجدد يومياً لضمان سلامة وأمان المنظومة[cite: 1].
   * استجابة مرئية فورية (أخضر: مسموح | أصفر: مستخدمة مسبقاً | أحمر: غير صالحة)[cite: 1].
   * إعادة تعيين تلقائية خلال 3 ثوانٍ لمعالجة التدفق المرتفع للزوار[cite: 1].

---

## 🛠️ التقنيات المستخدمة (Tech Stack)

| المكون (Component) | التقنية (Technology) |
| :--- | :--- |
| **Backend API Framework** | Laravel 11.x (PHP 8.2+)[cite: 1] |
| **Authentication & Auth** | Laravel Sanctum[cite: 1] |
| **Primary Database** | PostgreSQL 16[cite: 1] |
| **Caching & Queue Processing** | Redis 7.x & Laravel Horizon[cite: 1] |
| **Real-time WebSockets** | Laravel Reverb[cite: 1] |
| **Cloud Storage & Backups** | AWS S3 (Encrypted Backups)[cite: 1] |
| **Web Frontends** | React.js 18.x (Vite)[cite: 1] |
| **Mobile & Gate Apps** | Flutter 3.x / Dart 3.x & SQLite[cite: 1] |

---

## 🔐 الأمان والموثوقية (Security & Performance)

* **التشفير وحماية البيانات**: كافة اتصالات الـ API تعمل عبر بروتوكول HTTPS، مع تشفير كلمات المرور باستخدام bcrypt[cite: 1].
* **حماية من الحجز المزدوج**: اعتماد **Redis Atomic Locks** لمنع تضارب إنتاج التذاكر أو تعارض حجز الفعاليات[cite: 1].
* **حماية رمز QR**: يحتوي الـ QR على رمز مبهم فريد (UUID) خالٍ من أي بيانات شخصية ذات صلة بالهوية[cite: 1].
* **التعافي والنسخ الاحتياطي**: نسخ احتياطية يومية آلية مع إصدار روابط تنزيل مؤقتة مشفرة عبر AWS S3 Pre-signed URLs[cite: 1].

---
