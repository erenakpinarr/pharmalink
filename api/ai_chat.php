<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz istek']);
    exit;
}

$mesaj = trim($_POST['mesaj'] ?? '');

if (empty($mesaj)) {
    echo json_encode(['basari' => false, 'mesaj' => 'Lütfen bir mesaj girin.']);
    exit;
}

$cevap = "";
$mesajLower = mb_strtolower($mesaj, 'UTF-8');

// 1. Nöbetçi Eczane Sorgusu
if (strpos($mesajLower, 'nöbetçi') !== false || strpos($mesajLower, 'nobetci') !== false) {
    // Sistemdeki nöbetçileri getirelim
    $stmt = db()->prepare("SELECT eczane_adi, ilce, sehir, telefon, adres FROM eczaneler WHERE nobetci = 1 AND durum = 'onaylandi' LIMIT 3");
    $stmt->execute();
    $nobetciler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($nobetciler) > 0) {
        $cevap = "Şu anda sistemimizde kayıtlı bazı nöbetçi eczaneler şunlardır:<br><br>";
        foreach ($nobetciler as $n) {
            $cevap .= "🏥 <strong>" . e($n['eczane_adi']) . "</strong> (" . e($n['ilce']) . "/" . e($n['sehir']) . ")<br>📞 " . e($n['telefon']) . "<br><br>";
        }
        $cevap .= "Tüm nöbetçi eczaneleri görmek için lütfen ana sayfadaki haritayı inceleyin.";
    } else {
        $cevap = "Şu an sistemde aktif olarak işaretlenmiş nöbetçi eczane bulunmamaktadır.";
    }
} 
// 2. İlaç veya Kategori Arama (Basit NLP Yaklaşımı)
else {
    // Mesajdaki kelimeleri bulalım ve ilaç veritabanında aratalım
    $kelimeler = explode(' ', $mesajLower);
    $bulunanIlac = null;
    $stokEczaneler = [];
    
    foreach ($kelimeler as $kelime) {
        $kelime = trim($kelime, '.,!?');
        if (mb_strlen($kelime, 'UTF-8') > 3) { // 3 harften büyük kelimeleri ilaç olarak arayalım
            $stmt = db()->prepare("SELECT id, ad FROM ilaclar WHERE ad LIKE ? LIMIT 1");
            $stmt->execute(["%$kelime%"]);
            $ilac = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ilac) {
                $bulunanIlac = $ilac;
                break; // İlk eşleşen ilacı al
            }
        }
    }

    if ($bulunanIlac) {
        // İlacı bulduk, şimdi stoğunda olan eczaneleri getirelim
        $stmt = db()->prepare("
            SELECT e.eczane_adi, e.ilce, e.sehir, s.durum 
            FROM stok s 
            JOIN eczaneler e ON s.eczane_id = e.id 
            WHERE s.ilac_id = ? AND s.durum IN ('mevcut', 'az_stok') AND e.durum = 'onaylandi'
            LIMIT 3
        ");
        $stmt->execute([$bulunanIlac['id']]);
        $stoklar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($stoklar) > 0) {
            $cevap = "<strong>" . e($bulunanIlac['ad']) . "</strong> isimli ilaç şu eczanelerin stoklarında bulunuyor:<br><br>";
            foreach ($stoklar as $s) {
                $stokDurumu = $s['durum'] === 'mevcut' ? '<span style="color:var(--renk-basari); font-weight:bold;">Stokta Var</span>' : '<span style="color:var(--renk-uyari); font-weight:bold;">Az Stok</span>';
                $cevap .= "🏥 <strong>" . e($s['eczane_adi']) . "</strong> (" . e($s['ilce']) . "/" . e($s['sehir']) . ") - $stokDurumu<br>";
            }
        } else {
            $cevap = "Maalesef <strong>" . e($bulunanIlac['ad']) . "</strong> isimli ilacı şu an yakındaki hiçbir eczanenin stoklarında bulamadım. İsterseniz arama sayfasından ilacı aratıp, eczanelere 'Talep' gönderebilirsiniz.";
        }
    } 
    // 3. Eczane İsmi Arama
    elseif (strpos($mesajLower, 'eczane') !== false && mb_strlen($mesajLower, 'UTF-8') > 8) {
        // Mesajda geçen kelimeleri eczane tablosunda arayalım
        $kelimeler = array_filter(explode(' ', $mesajLower), function($k) { 
            return !in_array(trim($k, '.,!?'), ['eczane', 'eczaneler', 'nerede', 'var', 'mı', 'mi', 'bulunur']); 
        });
        
        $bulunanEczane = null;
        foreach ($kelimeler as $kelime) {
            $kelime = trim($kelime, '.,!?');
            if (mb_strlen($kelime, 'UTF-8') > 3) {
                $stmt = db()->prepare("SELECT eczane_adi, ilce, sehir, adres, telefon FROM eczaneler WHERE eczane_adi LIKE ? AND durum = 'onaylandi' LIMIT 1");
                $stmt->execute(["%$kelime%"]);
                $eczane = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($eczane) {
                    $bulunanEczane = $eczane;
                    break;
                }
            }
        }
        
        if ($bulunanEczane) {
            $cevap = "Aradığınız eczaneyi buldum:<br><br>🏥 <strong>" . e($bulunanEczane['eczane_adi']) . "</strong><br>📍 " . e($bulunanEczane['adres']) . " (" . e($bulunanEczane['ilce']) . "/" . e($bulunanEczane['sehir']) . ")<br>📞 " . e($bulunanEczane['telefon']);
        } else {
            // Eczane bulamadıysa genel cevaplara düşelim
            goto GenelCevap;
        }
    }
    // 4. Genel Sağlık Soruları
    else {
        GenelCevap:
        if (strpos($mesajLower, 'baş ağrısı') !== false || strpos($mesajLower, 'bas agrisi') !== false) {
            $cevap = "Baş ağrısı için genellikle ağrı kesiciler (Analjezikler) tercih edilir. Ancak şiddetli veya geçmeyen baş ağrılarınız varsa lütfen bir hekime başvurun. İlaç ismi (Örn: Parol) yazarsanız stok sorgusu yapabilirim.";
        } elseif (strpos($mesajLower, 'mide') !== false || strpos($mesajLower, 'bulantı') !== false) {
            $cevap = "Mide rahatsızlıkları için antiasit veya mide koruyucu şuruplar eczacınıza danışılarak kullanılabilir. Lütfen detaylı bilgi için en yakın eczaneye başvurun.";
        } elseif (strpos($mesajLower, 'ateş') !== false || strpos($mesajLower, 'ates') !== false) {
            $cevap = "Ateş düşürücü (Antipiretik) ilaçlar kullanılabilir. Ancak ateş 39 dereceyi geçiyorsa veya 3 günden uzun sürüyorsa mutlaka bir sağlık kuruluşuna başvurun.";
        } elseif (strpos($mesajLower, 'satın al') !== false || strpos($mesajLower, 'satin al') !== false || strpos($mesajLower, 'sipariş') !== false || strpos($mesajLower, 'siparis') !== false || strpos($mesajLower, 'fiyat') !== false) {
            $cevap = "PharmaLink bir e-ticaret platformu veya çevrimiçi eczane değildir. Bu nedenle doğrudan platformumuz üzerinden ilaç satışı, siparişi veya fiyatlandırması yapılmamaktadır. İlacın stokta olduğu eczaneleri haritadan bulup doğrudan eczaneye giderek satın alabilir veya eczaneyle iletişime geçebilirsiniz.";
        } elseif (strpos($mesajLower, 'merhaba') !== false || strpos($mesajLower, 'selam') !== false) {
            $cevap = "Merhaba! Ben PharmaLink Sağlık Asistanı. Bana bir <strong>ilaç ismi</strong> (örn: Aspirin) veya <strong>nöbetçi eczaneler</strong> diyerek veri tabanımızda sorgulama yaptırabilirsiniz.";
        } elseif (strpos($mesajLower, 'teşekkür') !== false || strpos($mesajLower, 'tesekkur') !== false || strpos($mesajLower, 'sağol') !== false) {
            $cevap = "Rica ederim! Sağlıklı günler dilerim. Başka bir sorunuz olursa buradayım.";
        } else {
            $cevap = "Söylediğinizi tam olarak anlayamadım veya veri tabanımızda eşleşen bir kayıt (ilaç, eczane vb.) bulamadım. Bana spesifik bir ilaç ismi veya eczane sorabilirsiniz (Örn: 'Parol var mı?' veya 'Nöbetçi eczaneler').";
        }
    }
}

// Gerçekçilik katmak için ufak bir bekleme (300ms - 800ms)
usleep(rand(300000, 800000));

echo json_encode([
    'basari' => true,
    'cevap' => $cevap
]);
