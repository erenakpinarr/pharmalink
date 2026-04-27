CREATE DATABASE IF NOT EXISTS eczane_sistemi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_turkish_ci;

USE eczane_sistemi;

CREATE TABLE IF NOT EXISTS kullanicilar (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ad          VARCHAR(100) NOT NULL,
    soyad       VARCHAR(100) NOT NULL,
    email       VARCHAR(191) NOT NULL UNIQUE,
    sifre       VARCHAR(255) NOT NULL,
    rol         ENUM('admin','eczane','kullanici') NOT NULL DEFAULT 'kullanici',
    telefon     VARCHAR(20) DEFAULT NULL,
    profil_resmi VARCHAR(255) DEFAULT NULL,
    sifre_token VARCHAR(255) DEFAULT NULL,
    sifre_token_exp DATETIME DEFAULT NULL,
    iki_adimli_sir VARCHAR(255) DEFAULT NULL,
    iki_adimli_aktif TINYINT(1) DEFAULT 0,
    aktif       TINYINT(1) NOT NULL DEFAULT 1,
    olusturma   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    sehir       VARCHAR(100) DEFAULT NULL,
    ilce        VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS kategoriler (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ad          VARCHAR(150) NOT NULL UNIQUE,
    aciklama    TEXT,
    olusturma   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS ilaclar (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    ad          VARCHAR(200) NOT NULL,
    barkod      VARCHAR(50) UNIQUE,
    etken_madde VARCHAR(200),
    aciklama    TEXT,
    resim       VARCHAR(255),
    aktif       TINYINT(1) NOT NULL DEFAULT 1,
    olusturma   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ilac_kategori FOREIGN KEY (kategori_id) REFERENCES kategoriler(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS eczaneler (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id    INT NOT NULL UNIQUE,
    slug            VARCHAR(100) UNIQUE DEFAULT NULL,
    eczane_adi      VARCHAR(200) NOT NULL,
    adres           TEXT NOT NULL,
    sehir           VARCHAR(100) NOT NULL,
    ilce            VARCHAR(100) NOT NULL,
    telefon         VARCHAR(20),
    hakkimizda      TEXT DEFAULT NULL,
    banner_resmi    VARCHAR(255) DEFAULT NULL,
    tema_rengi      VARCHAR(7) DEFAULT '#6366f1',
    vitrin_aktif    TINYINT(1) DEFAULT 0,
    enlem           DECIMAL(10, 8),
    boylam          DECIMAL(11, 8),
    harita_linki    VARCHAR(500),
    calisma_saatleri VARCHAR(100) DEFAULT '09:00 - 19:00',
    nobetci         TINYINT(1) NOT NULL DEFAULT 0,
    durum           ENUM('beklemede','onaylandi','reddedildi') NOT NULL DEFAULT 'beklemede',
    belge_dosyasi   VARCHAR(255),
    red_nedeni      TEXT,
    olusturma       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eczane_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS stok (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    eczane_id   INT NOT NULL,
    ilac_id     INT NOT NULL,
    durum       ENUM('mevcut','az_stok','tukendi') NOT NULL DEFAULT 'mevcut',
    adet        INT NOT NULL DEFAULT 0,
    minimum_adet INT NOT NULL DEFAULT 10,
    guncelleme  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_eczane_ilac (eczane_id, ilac_id),
    CONSTRAINT fk_stok_eczane FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stok_ilac   FOREIGN KEY (ilac_id)   REFERENCES ilaclar(id)   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS favoriler (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id    INT NOT NULL,
    ilac_id         INT NOT NULL,
    olusturma       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_kullanici_ilac (kullanici_id, ilac_id),
    CONSTRAINT fk_fav_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fav_ilac      FOREIGN KEY (ilac_id)      REFERENCES ilaclar(id)      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS talepler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    eczane_id INT NOT NULL,
    konu VARCHAR(255) NOT NULL,
    mesaj TEXT NOT NULL,
    yanit TEXT DEFAULT NULL,
    durum ENUM('bekliyor', 'yanitlandi', 'onaylandi', 'iptal', 'teslim_edildi') DEFAULT 'bekliyor',
    qr_kod_hash VARCHAR(255) DEFAULT NULL,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_talep_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    CONSTRAINT fk_talep_eczane FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS talep_mesajlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talep_id INT NOT NULL,
    gonderen_tipi ENUM('kullanici', 'eczane') NOT NULL,
    mesaj TEXT NOT NULL,
    okundu TINYINT(1) DEFAULT 0,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_talep_mesaj FOREIGN KEY (talep_id) REFERENCES talepler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS mesajlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mesaj TEXT NOT NULL,
    durum ENUM('yeni', 'okundu', 'yanitlandi') DEFAULT 'yeni',
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS ayirtmalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    eczane_id INT NOT NULL,
    ilac_id INT NOT NULL,
    notlar TEXT DEFAULT NULL,
    durum ENUM('beklemede', 'onaylandi', 'reddedildi', 'teslim_edildi', 'iptal') DEFAULT 'beklemede',
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ayirtma_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    CONSTRAINT fk_ayirtma_eczane FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE,
    CONSTRAINT fk_ayirtma_ilac FOREIGN KEY (ilac_id) REFERENCES ilaclar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS bildirimler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    mesaj TEXT NOT NULL,
    tip ENUM('info', 'basari', 'uyari', 'tehlike') DEFAULT 'info',
    kategori VARCHAR(50) DEFAULT 'sistem',
    okundu TINYINT(1) DEFAULT 0,
    baglanti_url VARCHAR(255) DEFAULT NULL,
    eylemler JSON DEFAULT NULL,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bildirim_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS eczane_calisanlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eczane_id INT NOT NULL,
    ad_soyad VARCHAR(200) NOT NULL,
    pozisyon ENUM('eczaci','eczane_teknisyeni','stajyer','muhasebe','diger') DEFAULT 'eczaci',
    telefon VARCHAR(20),
    email VARCHAR(191),
    baslangic_tarihi DATE,
    aktif TINYINT(1) DEFAULT 1,
    notlar TEXT,
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS eczane_calisma_saatleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eczane_id INT NOT NULL,
    gun TINYINT NOT NULL,
    acilis TIME,
    kapanis TIME,
    kapali TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_eczane_gun (eczane_id, gun),
    FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS musteri_ziyaretleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eczane_id INT NOT NULL,
    kullanici_id INT,
    tip ENUM('rezervasyon','talep','ziyaret') DEFAULT 'ziyaret',
    ilac_id INT DEFAULT NULL,
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS eczane_vardiyalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eczane_id INT NOT NULL,
    calisan_id INT NOT NULL,
    tarih DATE NOT NULL,
    baslangic TIME NOT NULL,
    bitis TIME NOT NULL,
    not_metni VARCHAR(255),
    FOREIGN KEY (eczane_id) REFERENCES eczaneler(id) ON DELETE CASCADE,
    FOREIGN KEY (calisan_id) REFERENCES eczane_calisanlar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO kullanicilar (ad, soyad, email, sifre, rol) VALUES
('Sistem', 'Admin', 'admin@eczane.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT IGNORE INTO kategoriler (ad, aciklama) VALUES
('Antibiyotikler', 'Bakteriyel enfeksiyonlara karşı kullanılan ilaçlar'),
('Analjezikler', 'Ağrı kesici ilaçlar'),
('Antihistaminikler', 'Alerji ilaçları'),
('Antihipertansifler', 'Tansiyon düşürücü ilaçlar'),
('Vitaminler & Takviyeler', 'Vitamin ve mineral destekleri'),
('Dermatoloji', 'Cilt hastalıkları için ilaçlar'),
('Kardiyoloji', 'Kalp ve damar hastalıkları ilaçları'),
('Sindirim Sistemi', 'Mide ve bağırsak ilaçları'),
('Solunum Sistemi', 'Öksürük ve solunum yolu ilaçları'),
('Diyabet İlaçları', 'Şeker hastalığı ilaçları');

INSERT IGNORE INTO ilaclar (kategori_id, ad, barkod, etken_madde, aciklama) VALUES
(1, 'Augmentin 1000 mg', '8699502750018', 'Amoksisilin + Klavulanat', 'Geniş spektrumlu antibiyotik'),
(1, 'Amoklavin 875 mg', '8699637090063', 'Amoksisilin + Klavulanat', 'Bakterisidal antibiyotik'),
(2, 'Parol 500 mg', '8699514010019', 'Parasetamol', 'Ateş düşürücü ve ağrı kesici'),
(2, 'Apranax 275 mg', '8699502040162', 'Naproksen Sodyum', 'Anti-enflamatuar ağrı kesici'),
(2, 'Vermidon', '8699522090040', 'Metamizol Sodyum', 'Güçlü ağrı kesici ve ateş düşürücü'),
(3, 'Allergic 10 mg', '8699514090082', 'Loratatadin', 'Mevsimsel alerjiler için'),
(3, 'Claritine 10 mg', '8699799031039', 'Loratatadin', 'Antihistaminik'),
(4, 'Norvasc 5 mg', '8699514130078', 'Amlodipin', 'Kalsiyum kanal blokörü'),
(4, 'Concor 5 mg', '8699526490145', 'Bisoprolol', 'Beta blokör antihipertansif'),
(5, 'Redoxon 1000 mg', '8690521000204', 'C Vitamini', 'Bağışıklık sistemi desteği'),
(5, 'D-Cure 25000 IU', '5413908000118', 'D3 Vitamini', 'D vitamini takviyesi'),
(8, 'Nexium 40 mg', '8699633010021', 'Esomeprazol', 'Proton pompa inhibitörü'),
(8, 'Lansor 30 mg', '8699514070060', 'Lansoprazol', 'Mide koruyucu'),
(9, 'Mucolit 600 mg', '8699514130252', 'Asetilsistein', 'Balgam söktürücü'),
(10, 'Glucophage 1000 mg', '8699633080016', 'Metformin', 'Tip 2 diyabet tedavisi');

-- Yeni Kullanıcılar
INSERT IGNORE INTO kullanicilar (id, ad, soyad, email, sifre, rol, sehir, ilce) VALUES
(2, 'Semih', 'Sevimler', 'semihsevimler@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'İstanbul', 'Başakşehir'),
(3, 'Eren', 'Akpınar', 'erenakpinar@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eczane', 'İstanbul', 'Başakşehir'),
(4, 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'İstanbul', 'Bakırköy'),
(5, 'Ayşe', 'Demir', 'ayse.demir@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'İstanbul', 'Şişli'),
(6, 'Can', 'Özkan', 'can.ozkan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'Ankara', 'Çankaya'),
(7, 'Selin', 'Aksu', 'selin.aksu@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'İzmir', 'Konak'),
(8, 'Mehmet', 'Merkez', 'merkez.eczane@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eczane', 'İstanbul', 'Kadıköy'),
(9, 'Leyla', 'Hayat', 'hayat.eczane@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eczane', 'İstanbul', 'Beşiktaş'),
(10, 'Yusuf', 'Şifa', 'sifa.eczane@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eczane', 'Ankara', 'Yenimahalle');

-- Yeni Eczaneler
INSERT IGNORE INTO eczaneler (id, kullanici_id, slug, eczane_adi, adres, sehir, ilce, telefon, nobetci, durum) VALUES
(1, 3, 'akpinar-eczanesi', 'Akpınar Eczanesi', 'Başakşehir Caddesi No 5', 'İstanbul', 'Başakşehir', '0212 555 11 11', 0, 'onaylandi'),
(2, 8, 'merkez-eczanesi', 'Merkez Eczanesi', 'Bağdat Caddesi No 120', 'İstanbul', 'Kadıköy', '0216 333 22 22', 1, 'onaylandi'),
(3, 9, 'hayat-eczanesi', 'Hayat Eczanesi', 'Beşiktaş Meydan No 2', 'İstanbul', 'Beşiktaş', '0212 222 33 33', 0, 'onaylandi'),
(4, 10, 'sifa-eczanesi', 'Şifa Eczanesi', 'İvedik Caddesi No 44', 'Ankara', 'Yenimahalle', '0312 444 55 55', 0, 'onaylandi');

-- Dummy Stoklar
-- Augmentin (ID 1), Parol (ID 3), Mucolit (ID 14), Glucophage (ID 15)
INSERT IGNORE INTO stok (eczane_id, ilac_id, durum, adet) VALUES
(1, 1, 'mevcut', 50),
(1, 3, 'mevcut', 100),
(1, 14, 'az_stok', 5),
(2, 1, 'mevcut', 30),
(2, 5, 'mevcut', 20),
(2, 10, 'tukendi', 0),
(3, 1, 'mevcut', 15),
(3, 3, 'mevcut', 80),
(3, 15, 'mevcut', 40),
(4, 3, 'mevcut', 60),
(4, 15, 'mevcut', 25);

-- Dummy Bildirimler
INSERT IGNORE INTO bildirimler (id, kullanici_id, baslik, mesaj, tip, kategori, okundu) VALUES
(1, 2, 'Hoş Geldiniz', 'PharmaLink platformuna hoş geldiniz. Aramalara başlayabilirsiniz.', 'info', 'sistem', 0),
(2, 2, 'Rezervasyon Onaylandı', 'Akpınar Eczanesinden ayırttığınız Parol onaylandı.', 'basari', 'eczane', 0),
(3, 3, 'Yeni Rezervasyon', 'Bir kullanıcı ilacınızı ayırttı. Lütfen onaylayın.', 'uyari', 'sistem', 0);
