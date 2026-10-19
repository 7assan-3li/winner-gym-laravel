# دليل خادم الإنتاج وإضافة مشاريع جديدة (Docker Multi-Tenant Guide)
**خادم WINNER GYM والخدمات المشتركة — Contabo VPS**

---

## 1. بيانات الخادم الأساسية (Server Overview)

| البند | التفاصيل |
| :--- | :--- |
| **مزود الخدمة (Provider)** | Contabo VPS (`vmi3658804`) |
| **عنوان السيرفر (IP)** | `80.241.208.214` |
| **نظام التشغيل (OS)** | Ubuntu 24.04 LTS (Noble Numbat) |
| **المعالج والذاكرة** | 8 GB RAM + 4 GB Swap Space |
| **المنطقة الزمنية** | `Asia/Aden` (+0300) |
| **بيئة التشغيل** | Docker Engine 29+ & Docker Compose v2+ |
| **مستخدم إدارة المشاريع** | `hasan` (عضو في مجموعتي `sudo` و `docker`) |
| **مسار التطبيقات المركزي** | `/home/hasan/apps/` |
| **طريقة الدخول عبر SSH** | `ssh hasan@80.241.208.214` |

---

## 2. جدول حجز المنافذ والمشاريع (Port Allocation Table)

لتفادي أي تضارب بين المشاريع، يعمل كل مشروع على منفذ محلي معزول `127.0.0.1` ويتم استقبال الزوار عبر **Host Nginx** وشهادات **Let's Encrypt**:

| المشروع | المسار على السيرفر | المنفذ الداخلي (Host Port) | الدومين (Domain) | حالة التشغيل |
| :--- | :--- | :--- | :--- | :--- |
| **WINNER GYM** | `/home/hasan/apps/winner-gym` | `127.0.0.1:8001:80` | `winnergym1.com` | ✅ نشط (Active) |
| **المشروع الثاني** | `/home/hasan/apps/project-two` | `127.0.0.1:8002:80` | `app2.yourdomain.com` | 🟡 محجوز وجاهز |
| **المشروع الثالث** | `/home/hasan/apps/project-three`| `127.0.0.1:8003:80` | `app3.yourdomain.com` | ⚪ شاغر |
| **المشروع الرابع** | `/home/hasan/apps/project-four` | `127.0.0.1:8004:80` | `app4.yourdomain.com` | ⚪ شاغر |

---

## 3. خطوات إضافة أي مشروع جديد في 5 دقائق (Step-by-Step)

عند الرغبة في رفع أي تطبيق جديد (Laravel / Node.js / Python / Next.js / WordPress):

### الخطوة 1: توجيه الدومين في مزود النطاق (DNS)
وجّه الدومين الجديد (مثال: `app2.yourdomain.com`) بسجل **A Record** إلى IP السيرفر:
```text
Type: A
Name: app2 (أو @)
IPv4: 80.241.208.214
Proxy: DNS only (مؤقتاً لحين استخراج الشهادة)
```

---

### الخطوة 2: إنشاء مجلد المشروع وسحب الكود
سجل دخولك بالسيرفر `ssh hasan@80.241.208.214`:
```bash
cd /home/hasan/apps
mkdir project-two && cd project-two
git clone <رابط_مستودع_المشروع_الجديد> .
```

---

### الخطوة 3: ضبط المنفذ في `docker-compose.yml` الخاص بالمشروع الجديد
تأكد أن حاوية الويب في المشروع الجديد مربوطة بالمنفذ التالي `8002`:
```yaml
services:
  web: # أو app
    image: nginx:alpine # أو صورة تطبيقك
    container_name: project_two_web
    restart: unless-stopped
    ports:
      - "127.0.0.1:8002:80"   # <--- ربط بالمنفذ الداخلي 8002
    networks:
      - project_two_network

networks:
  project_two_network:
    driver: bridge
```
> **قاعدة ذهبية:** تأكد أن أسماء الحاويات (`container_name`) وشبكات الدوكر (`networks`) وحجوم البيانات (`volumes`) في المشروع الجديد فريدة ولا تطابق المشروع الأول (مثل: `project_two_db` و `project_two_web`).

ثم قم بتشغيل الحاويات:
```bash
docker compose up -d --build
```

---

### الخطوة 4: إنشاء ملف التوجيه في Nginx
أنشئ ملف الإعداد للدومين الجديد:
```bash
sudo nano /etc/nginx/sites-available/project-two.conf
```
ألصق الإعداد التالي مع تغيير الدومين والمنفذ `8002`:
```nginx
server {
    listen 80;
    server_name app2.yourdomain.com;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

فعّل الموقع واختبر Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/project-two.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### الخطوة 5: استخراج شهادة SSL مجانية فوراً
```bash
sudo certbot --nginx -d app2.yourdomain.com
```

بذلك يصبح المشروع الثاني حياً ومؤمناً برابط `https://app2.yourdomain.com` ويعمل جنباً إلى جنب مع المشروع الأول بدون أي تداخل!

---

## 4. أهم أوامر الإدارة والصيانة اليومية السريعة

```bash
# عرض استهلاك الذاكرة العشوائية والـ Swap
free -h

# عرض المساحة المتبقية على القرص الصلب
df -h

# مراقبة أداء واستهلاك جميع حاويات الدوكر النشطة
docker stats

# عرض جميع الحاويات الشغالة ومنافذها
docker ps

# فحص الجدار الناري وحماية SSH
sudo ufw status
sudo fail2ban-client status sshd

# تنظيف صور الدوكر المؤقتة غير المستخدمة لتحرير مساحة القرص
docker image prune -af
```
