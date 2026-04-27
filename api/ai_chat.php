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
    $stmt = db()->prepare("SELECT eczane_adi, ilce, sehir, telefon, adres, enlem, boylam, harita_linki FROM eczaneler WHERE nobetci = 1 AND durum = 'onaylandi' LIMIT 3");
    $stmt->execute();
    $nobetciler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($nobetciler) > 0) {
        $cevap = "Şu anda sistemimizde kayıtlı bazı nöbetçi eczaneler şunlardır:<br><br>";
        foreach ($nobetciler as $n) {
            $haritaUrl = !empty($n['harita_linki']) ? $n['harita_linki'] : "https://www.google.com/maps/dir/?api=1&destination=" . $n['enlem'] . "," . $n['boylam'];
            $cevap .= "🏥 <strong>" . e($n['eczane_adi']) . "</strong> (" . e($n['ilce']) . "/" . e($n['sehir']) . ")<br>📞 " . e($n['telefon']) . " <a href=\"$haritaUrl\" target=\"_blank\" style=\"color:var(--renk-birincil); font-weight:600; font-size:0.8rem; margin-left:10px; text-decoration:underline;\">Yol Tarifi Al</a><br><br>";
        }
        $cevap .= "Tüm nöbetçi eczaneleri görmek için lütfen ana sayfadaki haritayı inceleyin.";
    } else {
        $cevap = "Şu an sistemde aktif olarak işaretlenmiş nöbetçi eczane bulunmamaktadır.";
    }
} 
// 2. Stok sorgula / İlaç arıyorum yönlendirmesi
elseif (
    strpos($mesajLower, 'ilaç arıyorum') !== false ||
    strpos($mesajLower, 'stok sorgula') !== false ||
    strpos($mesajLower, 'stok') !== false ||
    $mesajLower === 'ilaç' || $mesajLower === 'ilag'
) {
    $cevap = "Hangi ilacı arıyorsunuz? Lütfen <strong>ilaç adını</strong> yazın, size yakın eczanelerdeki stok durumunu kontrol edeyim.<br><small style='color:var(--metin-uc);'>(Örn: Parol, Aspirin, Augmentin vb.)</small>";
    usleep(rand(200000, 500000));
    echo json_encode(['basari' => true, 'cevap' => $cevap, 'moda' => 'ilac_bekleniyor']);
    exit;
}
// 3. Sağlık tavsiyeleri / genel bilgi
elseif (strpos($mesajLower, 'sağlık tavsiye') !== false || strpos($mesajLower, 'tavsiye') !== false || strpos($mesajLower, 'bilgi') !== false) {
    $cevap = "Aşağıdaki semptomlardan birini seçin, size uygun genel sağlık bilgisi sunayım:";
    usleep(rand(200000, 500000));
    $secenekler = [
        '<button type="button" class="ai-option-btn">Baş ağrısı</button>',
        '<button type="button" class="ai-option-btn">Mide bulantısı</button>',
        '<button type="button" class="ai-option-btn">Ateş var</button>',
        '<button type="button" class="ai-option-btn">Öksürük</button>',
        '<button type="button" class="ai-option-btn">Alerji</button>',
    ];
    $cevap .= '<div class="ai-options">' . implode('', $secenekler) . '</div>';
    echo json_encode(['basari' => true, 'cevap' => $cevap]);
    exit;
}
// 4. Eczane bul
elseif (strpos($mesajLower, 'eczane bul') !== false) {
    $cevap = "Hangi eczaneyi arıyorsunuz? Lütfen <strong>eczane adını</strong> yazın, sistemde arayıp bilgilerini getirim.";
    usleep(rand(200000, 500000));
    echo json_encode(['basari' => true, 'cevap' => $cevap]);
    exit;
}
// 5. İlaç veya Kategori Arama (Basit NLP Yaklaşımı)
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
            SELECT e.eczane_adi, e.ilce, e.sehir, e.enlem, e.boylam, e.harita_linki, s.durum 
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
                $haritaUrl = !empty($s['harita_linki']) ? $s['harita_linki'] : "https://www.google.com/maps/dir/?api=1&destination=" . $s['enlem'] . "," . $s['boylam'];
                $cevap .= "🏥 <strong>" . e($s['eczane_adi']) . "</strong> (" . e($s['ilce']) . "/" . e($s['sehir']) . ") - $stokDurumu <br><a href=\"$haritaUrl\" target=\"_blank\" style=\"display:inline-block; margin-top:4px; margin-bottom:8px; color:var(--renk-birincil); font-weight:600; font-size:0.8rem; text-decoration:underline;\">Eczaneye Git (Rota Çiz)</a><br>";
            }
        } else {
            $anaMenü = [
                '<button type="button" class="ai-option-btn">Nöbetçi eczaneler</button>',
                '<button type="button" class="ai-option-btn">Stok sorgula</button>',
                '<button type="button" class="ai-option-btn">Sağlık tavsiyeleri</button>',
                '<button type="button" class="ai-option-btn">Eczane bul</button>',
            ];
            $cevap = "Maalesef <strong>" . e($bulunanIlac['ad']) . "</strong> isimli ilacı şu an yakındaki hiçbir eczanenin stoklarında bulamadım. Başka bir konuda yardımcı olmamı ister misiniz?" . '<div class="ai-options">' . implode('', $anaMenü) . '</div>';
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
                $stmt = db()->prepare("SELECT eczane_adi, ilce, sehir, adres, telefon, enlem, boylam, harita_linki FROM eczaneler WHERE eczane_adi LIKE ? AND durum = 'onaylandi' LIMIT 1");
                $stmt->execute(["%$kelime%"]);
                $eczane = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($eczane) {
                    $bulunanEczane = $eczane;
                    break;
                }
            }
        }
        
        if ($bulunanEczane) {
            $haritaUrl = !empty($bulunanEczane['harita_linki']) ? $bulunanEczane['harita_linki'] : "https://www.google.com/maps/dir/?api=1&destination=" . $bulunanEczane['enlem'] . "," . $bulunanEczane['boylam'];
            $cevap = "Aradığınız eczaneyi buldum:<br><br>🏥 <strong>" . e($bulunanEczane['eczane_adi']) . "</strong><br>📍 " . e($bulunanEczane['adres']) . " (" . e($bulunanEczane['ilce']) . "/" . e($bulunanEczane['sehir']) . ")<br>📞 " . e($bulunanEczane['telefon']) . "<br><br><a href=\"$haritaUrl\" target=\"_blank\" style=\"display:inline-block; background:var(--renk-birincil); color:#fff; padding:6px 12px; border-radius:4px; font-size:0.85rem; font-weight:600; text-decoration:none;\">Google Haritalar'da Rota Çiz</a>";
        } else {
            // Eczane bulamadıysa genel cevaplara düşelim
            goto GenelCevap;
        }
    }
    // 4. Genel Sağlık ve Diğer Sorular
    else {
        GenelCevap:
        if (strpos($mesajLower, 'baş ağrısı') !== false || strpos($mesajLower, 'bas agrisi') !== false) {
            $cevap = "Baş ağrısı için genellikle ağrı kesiciler (Analjezikler) tercih edilir. Ancak şiddetli veya geçmeyen baş ağrılarınız varsa lütfen bir hekime başvurun.";
        } elseif (strpos($mesajLower, 'mide') !== false || strpos($mesajLower, 'bulantı') !== false) {
            $cevap = "Mide rahatsızlıkları için antiasit veya mide koruyucu şuruplar eczacınıza danışılarak kullanılabilir. Lütfen detaylı bilgi için en yakın eczaneye başvurun.";
        } elseif (strpos($mesajLower, 'ateş') !== false || strpos($mesajLower, 'ates') !== false) {
            $cevap = "Ateş düşürücü (Antipiretik) ilaçlar kullanılabilir. Ancak ateş 39°C'yi geçiyorsa veya 3 günden uzun sürüyorsa mutlaka bir sağlık kuruluşuna başvurun.";
        } elseif (strpos($mesajLower, 'öksürük') !== false || strpos($mesajLower, 'oksuruk') !== false) {
            $cevap = "Öksürük için şurup veya pastil şeklinde ilaçlar mevcuttur. Kuru öksürük ve balgamlı öksürük için farklı ilaçlar önerilir, eczacınıza danışmanızı tavsiye ederiz.";
        } elseif (strpos($mesajLower, 'alerjı') !== false || strpos($mesajLower, 'alerji') !== false) {
            $cevap = "Alerji semptomları için antihistaminik ilaçlar kullanılabilir. Ciddi alerjik reaksiyonlarda (nefes güçlüğü vb.) acile başvurun.";
        } elseif (strpos($mesajLower, 'fiyat') !== false || strpos($mesajLower, 'satın') !== false || strpos($mesajLower, 'sipariş') !== false) {
            $cevap = "PharmaLink bir e-ticaret platformu değildir. İlacınızı, stokta olduğu eczaneden bizzat satın alabilirsiniz. Yakın eczaneleri haritadan görebilirsiniz.";
        } elseif (strpos($mesajLower, 'merhaba') !== false || strpos($mesajLower, 'selam') !== false) {
            $cevap = "Merhaba! PharmaLink Sağlık Asistanı olarak size yardımcı olmaktan memnuniyet duyarım. Aşağıdaki seçeneklerden birini seçerek devam edebilirsiniz.";
        } elseif (strpos($mesajLower, 'teşekkür') !== false || strpos($mesajLower, 'sağol') !== false) {
            $cevap = "Rica ederim! Sağlıklı günler dilerim. Başka bir konuda yardımcı olmamı ister misiniz?";
        } elseif (strpos($mesajLower, 'arıyorum') !== false || strpos($mesajLower, 'var mı') !== false || strpos($mesajLower, 'nerede') !== false) {
            $cevap = "Belirttiğiniz ilacı veri tabanımızda bulamadım. Lütfen <strong>İlaç arıyorum</strong> seçeneğini kullanarak tam ilaç adını sorgulayın.";
        } elseif (strpos($mesajLower, 'eczane') !== false) {
            $cevap = "Belirttiğiniz eczane kaydını sistemimizde bulamadım. <strong>Nöbetçi eczaneler</strong> seçeneğiyle aktif eczaneleri listeleyebilirsiniz.";
        } elseif (strpos($mesajLower, 'pharmalink') !== false || strpos($mesajLower, 'pharma') !== false) {
            $cevap = "PharmaLink, Türkiye'nin dijital sağlık rehberidir. İlaç stoklarını gerçek zamanlı takip edebilir, nöbetçi eczaneleri haritadan bulabilir ve eczanelerle iletişime geçebilirsiniz.";
        } else {
            $cevap = "Lütfen aşağıdaki seçeneklerden birini kullanarak devam edin. Daha fazla bilgi için eczanenize danışmanızı öneririz.";
        }
    }
}

// Gerçekçilik katmak için ufak bir bekleme (300ms - 800ms)
usleep(rand(300000, 800000));

$havuz = [
    '<button type="button" class="ai-option-btn">Nöbetçi eczaneler</button>',
    '<button type="button" class="ai-option-btn">Stok sorgula</button>',
    '<button type="button" class="ai-option-btn">Sağlık tavsiyeleri</button>',
    '<button type="button" class="ai-option-btn">Eczane bul</button>',
    '<button type="button" class="ai-option-btn">Baş ağrısı</button>',
    '<button type="button" class="ai-option-btn">Mide bulantısı</button>',
    '<button type="button" class="ai-option-btn">Ateş var</button>',
    '<button type="button" class="ai-option-btn">Öksürük</button>',
    '<button type="button" class="ai-option-btn">Alerji</button>',
    '<button type="button" class="ai-option-btn">PharmaLink nedir?</button>',
    '<button type="button" class="ai-option-btn">İlaç fiyatları nedir?</button>'
];
shuffle($havuz);
$rastgeleSecenekler = array_slice($havuz, 0, 3);
$cevap .= '<div class="ai-options">' . implode('', $rastgeleSecenekler) . '</div>';

echo json_encode([
    'basari' => true,
    'cevap' => $cevap
]);
