-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: eczane_sistemi
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ayirtmalar`
--

DROP TABLE IF EXISTS `ayirtmalar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ayirtmalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `eczane_id` int(11) NOT NULL,
  `ilac_id` int(11) NOT NULL,
  `notlar` text DEFAULT NULL,
  `durum` enum('beklemede','onaylandi','reddedildi','teslim_edildi','iptal') DEFAULT 'beklemede',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ayirtma_kullanici` (`kullanici_id`),
  KEY `fk_ayirtma_eczane` (`eczane_id`),
  KEY `fk_ayirtma_ilac` (`ilac_id`),
  CONSTRAINT `fk_ayirtma_eczane` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ayirtma_ilac` FOREIGN KEY (`ilac_id`) REFERENCES `ilaclar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ayirtma_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ayirtmalar`
--

LOCK TABLES `ayirtmalar` WRITE;
/*!40000 ALTER TABLE `ayirtmalar` DISABLE KEYS */;
/*!40000 ALTER TABLE `ayirtmalar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bildirimler`
--

DROP TABLE IF EXISTS `bildirimler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bildirimler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) DEFAULT NULL,
  `baslik` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `tip` enum('basari','tehlike','uyari','info') DEFAULT 'info',
  `kategori` enum('sistem','eczane','ilac','guvenlik') DEFAULT 'sistem',
  `eylemler` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eylemler`)),
  `okundu` tinyint(1) DEFAULT 0,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bildirimler`
--

LOCK TABLES `bildirimler` WRITE;
/*!40000 ALTER TABLE `bildirimler` DISABLE KEYS */;
INSERT INTO `bildirimler` VALUES (1,2,'Hoş Geldiniz','PharmaLink platformuna hoş geldiniz. Aramalara başlayabilirsiniz.','info','sistem',NULL,0,'2026-04-27 14:28:43'),(2,2,'Rezervasyon Onaylandı','Akpınar Eczanesinden ayırttığınız Parol onaylandı.','basari','eczane',NULL,0,'2026-04-27 14:28:43'),(3,3,'Yeni Rezervasyon','Bir kullanıcı ilacınızı ayırttı. Lütfen onaylayın.','uyari','sistem',NULL,0,'2026-04-27 14:28:43');
/*!40000 ALTER TABLE `bildirimler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eczane_calisanlar`
--

DROP TABLE IF EXISTS `eczane_calisanlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eczane_calisanlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eczane_id` int(11) NOT NULL,
  `ad_soyad` varchar(200) NOT NULL,
  `pozisyon` enum('eczaci','eczane_teknisyeni','stajyer','muhasebe','diger') DEFAULT 'eczaci',
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `baslangic_tarihi` date DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `notlar` text DEFAULT NULL,
  `olusturma` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `eczane_id` (`eczane_id`),
  CONSTRAINT `eczane_calisanlar_ibfk_1` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eczane_calisanlar`
--

LOCK TABLES `eczane_calisanlar` WRITE;
/*!40000 ALTER TABLE `eczane_calisanlar` DISABLE KEYS */;
/*!40000 ALTER TABLE `eczane_calisanlar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eczane_calisma_saatleri`
--

DROP TABLE IF EXISTS `eczane_calisma_saatleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eczane_calisma_saatleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eczane_id` int(11) NOT NULL,
  `gun` tinyint(4) NOT NULL,
  `acilis` time DEFAULT NULL,
  `kapanis` time DEFAULT NULL,
  `kapali` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eczane_gun` (`eczane_id`,`gun`),
  CONSTRAINT `eczane_calisma_saatleri_ibfk_1` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eczane_calisma_saatleri`
--

LOCK TABLES `eczane_calisma_saatleri` WRITE;
/*!40000 ALTER TABLE `eczane_calisma_saatleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `eczane_calisma_saatleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eczane_vardiyalar`
--

DROP TABLE IF EXISTS `eczane_vardiyalar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eczane_vardiyalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eczane_id` int(11) NOT NULL,
  `calisan_id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `baslangic` time NOT NULL,
  `bitis` time NOT NULL,
  `not_metni` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eczane_id` (`eczane_id`),
  KEY `calisan_id` (`calisan_id`),
  CONSTRAINT `eczane_vardiyalar_ibfk_1` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eczane_vardiyalar_ibfk_2` FOREIGN KEY (`calisan_id`) REFERENCES `eczane_calisanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eczane_vardiyalar`
--

LOCK TABLES `eczane_vardiyalar` WRITE;
/*!40000 ALTER TABLE `eczane_vardiyalar` DISABLE KEYS */;
/*!40000 ALTER TABLE `eczane_vardiyalar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eczaneler`
--

DROP TABLE IF EXISTS `eczaneler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eczaneler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `eczane_adi` varchar(200) NOT NULL,
  `adres` text NOT NULL,
  `sehir` varchar(100) NOT NULL,
  `ilce` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `enlem` decimal(10,8) DEFAULT NULL,
  `boylam` decimal(11,8) DEFAULT NULL,
  `harita_linki` varchar(500) DEFAULT NULL,
  `calisma_saatleri` varchar(100) DEFAULT '09:00 - 19:00',
  `nobetci` tinyint(1) NOT NULL DEFAULT 0,
  `durum` enum('beklemede','onaylandi','reddedildi') NOT NULL DEFAULT 'beklemede',
  `belge_dosyasi` varchar(255) DEFAULT NULL,
  `red_nedeni` text DEFAULT NULL,
  `olusturma` datetime NOT NULL DEFAULT current_timestamp(),
  `guncelleme` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slug` varchar(100) DEFAULT NULL,
  `hakkimizda` text DEFAULT NULL,
  `banner_resmi` varchar(255) DEFAULT NULL,
  `tema_rengi` varchar(7) DEFAULT '#6366f1',
  `vitrin_aktif` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_id` (`kullanici_id`),
  UNIQUE KEY `slug` (`slug`),
  CONSTRAINT `fk_eczane_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eczaneler`
--

LOCK TABLES `eczaneler` WRITE;
/*!40000 ALTER TABLE `eczaneler` DISABLE KEYS */;
INSERT INTO `eczaneler` VALUES (2,8,'Merkez Eczanesi','Bağdat Caddesi No 120','İstanbul','Kadıköy','0216 333 22 22',NULL,NULL,NULL,'09:00 - 19:00',1,'onaylandi',NULL,NULL,'2026-04-27 14:28:01','2026-04-27 14:46:52','merkez-eczanesi',NULL,NULL,'#6366f1',0),(3,9,'Hayat Eczanesi','Beşiktaş Meydan No 2','İstanbul','Beşiktaş','0212 222 33 33',NULL,NULL,NULL,'09:00 - 19:00',0,'onaylandi',NULL,NULL,'2026-04-27 14:28:01','2026-04-27 14:46:52','hayat-eczanesi',NULL,NULL,'#6366f1',0),(4,10,'Şifa Eczanesi','İvedik Caddesi No 44','Ankara','Yenimahalle','0312 444 55 55',NULL,NULL,NULL,'09:00 - 19:00',0,'onaylandi',NULL,NULL,'2026-04-27 14:28:01','2026-04-27 14:46:52','sifa-eczanesi',NULL,NULL,'#6366f1',0);
/*!40000 ALTER TABLE `eczaneler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favoriler`
--

DROP TABLE IF EXISTS `favoriler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `ilac_id` int(11) NOT NULL,
  `olusturma` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kullanici_ilac` (`kullanici_id`,`ilac_id`),
  KEY `fk_fav_ilac` (`ilac_id`),
  CONSTRAINT `fk_fav_ilac` FOREIGN KEY (`ilac_id`) REFERENCES `ilaclar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fav_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favoriler`
--

LOCK TABLES `favoriler` WRITE;
/*!40000 ALTER TABLE `favoriler` DISABLE KEYS */;
INSERT INTO `favoriler` VALUES (2,14,3,'2026-04-27 14:44:27');
/*!40000 ALTER TABLE `favoriler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ilaclar`
--

DROP TABLE IF EXISTS `ilaclar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ilaclar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_id` int(11) NOT NULL,
  `ad` varchar(200) NOT NULL,
  `barkod` varchar(50) DEFAULT NULL,
  `etken_madde` varchar(200) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `resim` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barkod` (`barkod`),
  KEY `fk_ilac_kategori` (`kategori_id`),
  CONSTRAINT `fk_ilac_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ilaclar`
--

LOCK TABLES `ilaclar` WRITE;
/*!40000 ALTER TABLE `ilaclar` DISABLE KEYS */;
INSERT INTO `ilaclar` VALUES (1,1,'Augmentin 1000 mg','8699502750018','Amoksisilin + Klavulanat','Geniş spektrumlu antibiyotik',NULL,1,'2026-04-03 17:32:37'),(2,1,'Amoklavin 875 mg','8699637090063','Amoksisilin + Klavulanat','Bakterisidal antibiyotik',NULL,1,'2026-04-03 17:32:37'),(3,2,'Parol 500 mg','8699514010019','Parasetamol','Ateş düşürücü ve ağrı kesici',NULL,1,'2026-04-03 17:32:37'),(4,2,'Apranax 275 mg','8699502040162','Naproksen Sodyum','Anti-enflamatuar ağrı kesici',NULL,1,'2026-04-03 17:32:37'),(5,2,'Vermidon','8699522090040','Metamizol Sodyum','Güçlü ağrı kesici ve ateş düşürücü',NULL,1,'2026-04-03 17:32:37'),(6,3,'Allergic 10 mg','8699514090082','Loratatadin','Mevsimsel alerjiler için',NULL,1,'2026-04-03 17:32:37'),(7,3,'Claritine 10 mg','8699799031039','Loratatadin','Antihistaminik',NULL,1,'2026-04-03 17:32:37'),(8,4,'Norvasc 5 mg','8699514130078','Amlodipin','Kalsiyum kanal blokörü',NULL,1,'2026-04-03 17:32:37'),(9,4,'Concor 5 mg','8699526490145','Bisoprolol','Beta blokör antihipertansif',NULL,1,'2026-04-03 17:32:37'),(10,5,'Redoxon 1000 mg','8690521000204','C Vitamini','Bağışıklık sistemi desteği',NULL,1,'2026-04-03 17:32:37'),(11,5,'D-Cure 25000 IU','5413908000118','D3 Vitamini','D vitamini takviyesi',NULL,1,'2026-04-03 17:32:37'),(12,8,'Nexium 40 mg','8699633010021','Esomeprazol','Proton pompa inhibitörü',NULL,1,'2026-04-03 17:32:37'),(13,8,'Lansor 30 mg','8699514070060','Lansoprazol','Mide koruyucu',NULL,1,'2026-04-03 17:32:37'),(14,9,'Mucolit 600 mg','8699514130252','Asetilsistein','Balgam söktürücü',NULL,1,'2026-04-03 17:32:37'),(15,10,'Glucophage 1000 mg','8699633080016','Metformin','Tip 2 diyabet tedavisi',NULL,1,'2026-04-03 17:32:37'),(16,2,'Arveles 25 mg','8699536090011','Deksketoprofen','Çeşitli şiddetli ağrıların tedavisinde kullanılan, çok hızlı etki gösteren güçlü bir ağrı kesici.',NULL,1,'2026-04-27 14:51:48'),(17,2,'Majezik 100 mg','8699514090022','Flurbiprofen','Özellikle kas-iskelet sistemi ağrıları, eklem iltihapları ve adet dönemi sancılarında kullanılan anti-enflamatuar tablet.',NULL,1,'2026-04-27 14:52:10'),(18,6,'Bepanthol Onarıcı Bakım Merhemi','8699546090033','Dekspantenol (Provitamin B5)','Kurumuş, çatlamış ve tahriş olmuş cilt bölgelerini onarmaya, nemlendirmeye ve korumaya yardımcı medikal merhem.',NULL,1,'2026-04-27 14:52:30'),(19,9,'Iliadin %0.05 Burun Spreyi','8699514090044','Oksimetazolin Hidroklorür','Soğuk algınlığı, sinüzit ve saman nezlesine bağlı burun tıkanıklıklarını çok hızlı bir şekilde açarak nefes almayı kolaylaştıran burun spreyi.',NULL,1,'2026-04-27 14:52:59'),(20,8,'Rennie Çiğneme Tableti','8699546090055','Kalsiyum Karbonat + Magnezyum Karbonat','Mide asidini dengeleyerek mide yanması, ekşimesi ve hazımsızlık gibi şikayetleri dakikalar içinde hafifleten antiasit tablet.',NULL,1,'2026-04-27 14:53:19'),(21,9,'Benical Cold','8699514090066','Parasetamol + Psödoefedrin + Dekstrometorfan','Soğuk algınlığı ve grip semptomlarını (ateş, burun tıkanıklığı, öksürük ve kırgınlık) hafifleten kombine soğuk algınlığı ilacı.',NULL,1,'2026-04-27 14:53:36'),(22,6,'Fucidin %2 Krem','8699536090077','Fusidik Asit','Cilt üzerindeki bakteriyel enfeksiyonları, yara ve sivilce iltihaplarını tedavi etmeye yönelik topikal antibiyotikli krem.',NULL,1,'2026-04-27 14:53:51'),(23,5,'Supradyn All Day','8699546090088','Multivitamin ve Mineral Kompleksi','Günlük vitamin ve mineral ihtiyacını karşılamaya, enerji seviyesini artırmaya ve yorgunluğu azaltmaya yardımcı efervesan/tablet takviye.',NULL,1,'2026-04-27 14:54:16'),(24,9,'Katarin Forte','8699514090099','Parasetamol + Oksolamin + Klorfeniramin','Gribe ve soğuk algınlığına bağlı kas ağrısı, ateş, boğaz ağrısı ve balgamsız öksürük şikayetlerini aynı anda gideren kapsül.',NULL,1,'2026-04-27 14:54:35');
/*!40000 ALTER TABLE `ilaclar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategoriler`
--

DROP TABLE IF EXISTS `kategoriler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(150) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturma` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad` (`ad`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategoriler`
--

LOCK TABLES `kategoriler` WRITE;
/*!40000 ALTER TABLE `kategoriler` DISABLE KEYS */;
INSERT INTO `kategoriler` VALUES (1,'Antibiyotikler','Bakteriyel enfeksiyonlara karşı kullanılan ilaçlar','2026-04-03 17:32:37'),(2,'Analjezikler','Ağrı kesici ilaçlar','2026-04-03 17:32:37'),(3,'Antihistaminikler','Alerji ilaçları','2026-04-03 17:32:37'),(4,'Antihipertansifler','Tansiyon düşürücü ilaçlar','2026-04-03 17:32:37'),(5,'Vitaminler & Takviyeler','Vitamin ve mineral destekleri','2026-04-03 17:32:37'),(6,'Dermatoloji','Cilt hastalıkları için ilaçlar','2026-04-03 17:32:37'),(7,'Kardiyoloji','Kalp ve damar hastalıkları ilaçları','2026-04-03 17:32:37'),(8,'Sindirim Sistemi','Mide ve bağırsak ilaçları','2026-04-03 17:32:37'),(9,'Solunum Sistemi','Öksürük ve solunum yolu ilaçları','2026-04-03 17:32:37'),(10,'Diyabet İlaçları','Şeker hastalığı ilaçları','2026-04-03 17:32:37'),(84,'Diyabet Ä°laÃ§larÄ±','Åžeker hastalÄ±ÄŸÄ± ilaÃ§larÄ±','2026-04-27 14:27:34');
/*!40000 ALTER TABLE `kategoriler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` enum('admin','eczane','kullanici') NOT NULL DEFAULT 'kullanici',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma` datetime NOT NULL DEFAULT current_timestamp(),
  `guncelleme` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sehir` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `profil_resmi` varchar(255) DEFAULT NULL,
  `sifre_token` varchar(255) DEFAULT NULL,
  `sifre_token_exp` datetime DEFAULT NULL,
  `iki_adimli_sir` varchar(255) DEFAULT NULL,
  `iki_adimli_aktif` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanicilar`
--

LOCK TABLES `kullanicilar` WRITE;
/*!40000 ALTER TABLE `kullanicilar` DISABLE KEYS */;
INSERT INTO `kullanicilar` VALUES (1,'Sistem','Admin','admin@eczane.com','$2y$10$M3n8P8.HS6rT.HBvKkncQeUKs6cEuquMSNURcxqSsQmwq9V8TGPi6','admin',1,'2026-04-03 17:32:37','2026-04-10 17:26:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(2,'Semih','Sevimler','semihsevimler@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kullanici',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','İstanbul','Başakşehir',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(4,'Ahmet','Yılmaz','ahmet.yilmaz@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kullanici',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','İstanbul','Bakırköy',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(5,'Ayşe','Demir','ayse.demir@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kullanici',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','İstanbul','Şişli',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(6,'Can','Özkan','can.ozkan@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kullanici',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','Ankara','Çankaya',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(7,'Selin','Aksu','selin.aksu@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kullanici',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','İzmir','Konak',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(8,'Mehmet','Merkez','test@test.com','$2y$10$2mOGNhhE86IKpyc94TV2AOT1Zvq4k7jVEyRd9yO/NKDyR/uyQGRSW','kullanici',1,'2026-04-03 18:04:14','2026-04-27 14:46:52','İstanbul','Kadıköy',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(9,'Leyla','Hayat','new@user.com','$2y$10$kCORoA9O0kecz723Zzc6gednTDETDMzks9SRHp7QdY93EshofF3FK','kullanici',1,'2026-04-03 18:07:03','2026-04-27 14:46:52','İstanbul','Beşiktaş',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(10,'Yusuf','Şifa','sifa.eczane@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','eczane',1,'2026-04-27 14:27:34','2026-04-27 14:46:52','Ankara','Yenimahalle',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(14,'Eren','Akpınar','erenakpinar@gmail.com','$2y$10$aJWa7NfAiKCkEqE7LI9qPO.3QEdddkz2.9rqbq5QYhw50HRWD37qi','kullanici',1,'2026-04-27 14:25:26','2026-04-27 14:25:26','İstanbul','Başakşehir',NULL,NULL,NULL,NULL,NULL,0,0,NULL);
/*!40000 ALTER TABLE `kullanicilar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesajlar`
--

DROP TABLE IF EXISTS `mesajlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mesajlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mesaj` text NOT NULL,
  `durum` enum('yeni','okundu','yanitlandi') DEFAULT 'yeni',
  `olusturma` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesajlar`
--

LOCK TABLES `mesajlar` WRITE;
/*!40000 ALTER TABLE `mesajlar` DISABLE KEYS */;
/*!40000 ALTER TABLE `mesajlar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `musteri_ziyaretleri`
--

DROP TABLE IF EXISTS `musteri_ziyaretleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `musteri_ziyaretleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eczane_id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `tip` enum('rezervasyon','talep','ziyaret') DEFAULT 'ziyaret',
  `ilac_id` int(11) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `eczane_id` (`eczane_id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `musteri_ziyaretleri_ibfk_1` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `musteri_ziyaretleri_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `musteri_ziyaretleri`
--

LOCK TABLES `musteri_ziyaretleri` WRITE;
/*!40000 ALTER TABLE `musteri_ziyaretleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `musteri_ziyaretleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stok`
--

DROP TABLE IF EXISTS `stok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eczane_id` int(11) NOT NULL,
  `ilac_id` int(11) NOT NULL,
  `durum` enum('mevcut','az_stok','tukendi') NOT NULL DEFAULT 'mevcut',
  `guncelleme` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adet` int(11) NOT NULL DEFAULT 0,
  `minimum_adet` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eczane_ilac` (`eczane_id`,`ilac_id`),
  KEY `fk_stok_ilac` (`ilac_id`),
  CONSTRAINT `fk_stok_eczane` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stok_ilac` FOREIGN KEY (`ilac_id`) REFERENCES `ilaclar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stok`
--

LOCK TABLES `stok` WRITE;
/*!40000 ALTER TABLE `stok` DISABLE KEYS */;
INSERT INTO `stok` VALUES (1,2,1,'mevcut','2026-04-27 14:28:43',30,10),(2,2,5,'mevcut','2026-04-27 14:28:43',20,10),(3,2,10,'tukendi','2026-04-27 14:28:43',0,10),(4,3,1,'mevcut','2026-04-27 14:28:43',15,10),(5,3,3,'mevcut','2026-04-27 14:28:43',80,10),(6,3,15,'mevcut','2026-04-27 14:28:43',40,10),(7,4,3,'mevcut','2026-04-27 14:28:43',60,10),(8,4,15,'mevcut','2026-04-27 14:28:43',25,10);
/*!40000 ALTER TABLE `stok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `talep_mesajlari`
--

DROP TABLE IF EXISTS `talep_mesajlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `talep_mesajlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `talep_id` int(11) NOT NULL,
  `gonderen_tipi` enum('kullanici','eczane') NOT NULL,
  `mesaj` text NOT NULL,
  `okundu` tinyint(1) DEFAULT 0,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_talep_mesaj` (`talep_id`),
  CONSTRAINT `fk_talep_mesaj` FOREIGN KEY (`talep_id`) REFERENCES `talepler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talep_mesajlari`
--

LOCK TABLES `talep_mesajlari` WRITE;
/*!40000 ALTER TABLE `talep_mesajlari` DISABLE KEYS */;
/*!40000 ALTER TABLE `talep_mesajlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `talepler`
--

DROP TABLE IF EXISTS `talepler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `talepler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `eczane_id` int(11) NOT NULL,
  `konu` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `yanit` text DEFAULT NULL,
  `durum` enum('bekliyor','yanitlandi','onaylandi','iptal','teslim_edildi') DEFAULT 'bekliyor',
  `qr_kod_hash` varchar(255) DEFAULT NULL,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_talep_kullanici` (`kullanici_id`),
  KEY `fk_talep_eczane` (`eczane_id`),
  CONSTRAINT `fk_talep_eczane` FOREIGN KEY (`eczane_id`) REFERENCES `eczaneler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_talep_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talepler`
--

LOCK TABLES `talepler` WRITE;
/*!40000 ALTER TABLE `talepler` DISABLE KEYS */;
/*!40000 ALTER TABLE `talepler` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-27 15:03:31
