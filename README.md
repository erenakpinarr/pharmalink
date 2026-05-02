<div align="center">
  <img src="https://img.icons8.com/color/120/000000/caduceus.png" alt="PharmaLink Logo">
  <h1>💊 PharmaLink</h1>
  <p><strong>Modern Eczane Rehberi ve Akıllı Nöbetçi Eczane Takip Sistemi</strong></p>

  <div>
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
    <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
    <img src="https://img.shields.io/badge/PWA-Ready-312e81?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA">
    <img src="https://img.shields.io/badge/License-MIT-success?style=for-the-badge" alt="License">
  </div>
</div>

<br>

## 📖 Proje Hakkında

**PharmaLink**, kullanıcıların çevrelerindeki eczanelere hızlıca ulaşmasını sağlayan, nöbetçi eczaneleri anlık olarak listeleyen ve modern tasarımıyla kullanıcı deneyimini ön planda tutan bir **Eczane Rehber** platformudur. Karmaşık ERP ve CRM süreçlerinden arındırılmış, sadece hıza ve doğru bilgiye odaklanmış bir yardımcıdır.

---

## 🚀 Öne Çıkan Özellikler

### 📍 Akıllı Harita & Konum
- **Eczane Bulucu:** Google Maps entegrasyonu ile yakındaki eczaneleri harita üzerinde görüntüleme.
- **Yol Tarifi:** Seçilen eczaneye tek tıkla navigasyon başlatma.

### 🔔 Nöbetçi Eczane Sistemi
- **Gerçek Zamanlı Takip:** Eczane yöneticileri tarafından anlık olarak güncellenebilen nöbetçi durumu.
- **Öncelikli Listeleme:** Nöbetçi olan eczanelerin aramalarda ve haritada öne çıkarılması.

### 📱 PWA & Modern UI
- **Yüklenebilir Uygulama:** Cihazlara uygulama olarak eklenebilir (PWA desteği).
- **Hızlı Arayüz:** Vanilla JS ve CSS ile optimize edilmiş, gecikmesiz kullanıcı deneyimi.


---

## 🛠 Teknik Mimari (Stack)

| Katman | Teknoloji | Açıklama |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ | Nesne yönelimli (OOP) ve güvenli kod yapısı. |
| **Database** | MySQL 8.0+ | İlişkisel veritabanı, PDO ile SQL Injection koruması. |
| **Frontend** | Vanilla CSS & JS | Modern, hafif ve kütüphane bağımlılığı düşük arayüz. |
| **Security** | BCrypt / PDO | Güvenli şifreleme ve veritabanı etkileşimi. |
| **PWA** | Manifest / SW | Web uygulamasının yerel uygulama gibi çalışmasını sağlar. |

---

## 📂 Klasör Yapısı

```bash
├── admin/          # Yönetici paneli ve yetkili işlemleri
├── api/            # İstemci taraflı istekler için uç noktalar
├── auth/           # Login, Register ve Şifre sıfırlama süreçleri
├── core/           # Veritabanı singleton ve global konfigürasyon
├── database/       # SQL şemaları ve migration scriptleri
├── includes/       # Reusable UI bileşenleri (Header, Footer, Nav)
├── pages/          # Sayfa bazlı template dosyaları
├── pharmacy/       # Eczane operasyonel mantığı
├── user/           # Müşteri/Hasta profil yönetimi
└── utils/          # Yardımcı fonksiyonlar ve helperlar
```

---

## ⚙️ Kurulum Adımları

1.  **Projeyi Klonlayın:**
    ```bash
    git clone https://github.com/semihsevimler/pharmalink.git
    cd PharmaLink
    ```

2.  **Yapılandırma:**
    - `.env.example` dosyasını `.env` olarak kopyalayın.
    - Veritabanı bilgilerinizi ve `APP_URL` bilginizi güncelleyin.

3.  **Veritabanı Hazırlığı:**
    - MySQL üzerinde bir veritabanı oluşturun.
    - `database/` klasöründeki SQL'i içe aktarın veya migration dosyasını çalıştırın:
    ```bash
    php scratch/002_advanced_erp_migration.php
    ```

4.  **Sunucuyu Başlatın:**
    - XAMPP, Laragon veya PHP built-in server kullanabilirsiniz:
    ```bash
    php -S localhost:8000
    ```

---

## 🔑 Demo Verileri

Sistemi test etmek için aşağıdaki varsayılan hesapları kullanabilirsiniz:

*   **Eczane Yöneticisi:** `e@e` / `123456`
*   **Standart Kullanıcı:** `u@u` / `123456`

---

## 🤝 Katkıda Bulunma

1. Bu projeyi fork'layın.
2. Yeni bir özellik dalı (branch) oluşturun: `git checkout -b ozellik/yeni-fikir`.
3. Değişikliklerinizi commit'leyin: `git commit -m 'Yeni özellik eklendi'`.
4. Dalınıza push yapın: `git push origin ozellik/yeni-fikir`.
5. Bir Pull Request açın.

---

## 📜 Lisans

Bu proje **MIT Lisansı** ile korunmaktadır. Daha fazla bilgi için `LICENSE` dosyasına göz atabilirsiniz.

<div align="center">
  <p>Geliştiren: <strong>Semih Sevimler</strong></p>
  <a href="https://github.com/semihsevimler">
    <img src="https://img.shields.io/github/followers/semihsevimler?label=Takip%20Et&style=social" alt="GitHub Follow">
  </a>
</div>

