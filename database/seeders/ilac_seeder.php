<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';

$pdo = db();

echo "İlaç ve Kategori Seeder Başlıyor...\n";

// 1. Kategoriler Ekle veya ID Bul
$kategoriler_seed = [
    'Analjezikler ve Antipiretikler' => 'Ağrı kesici ve ateş düşürücüler',
    'Antibiyotikler' => 'Bakteriyel enfeksiyonlara karşı',
    'Solunum Sistemi' => 'Öksürük, astım ve KOAH ilaçları',
    'Sindirim Sistemi' => 'Mide koruyucular, antiasitler',
    'Kardiyovasküler Sistem' => 'Tansiyon ve kalp ilaçları',
    'Dermatolojik İlaçlar' => 'Krem ve merhemler',
    'Santral Sinir Sistemi' => 'Antidepresan ve sinirsel rahatsızlıklar',
    'Vitamin ve Takviyeler' => 'Vitamin, mineral',
    'Antihistaminikler' => 'Alerji ilaçları',
    'Antidiyabetikler' => 'Şeker hastalığı ilaçları'
];

$kategori_map = [];

foreach ($kategoriler_seed as $ad => $aciklama) {
    $stmt = $pdo->prepare("SELECT id FROM kategoriler WHERE ad = ?");
    $stmt->execute([$ad]);
    $kat = $stmt->fetch();
    
    if (!$kat) {
        $pdo->prepare("INSERT INTO kategoriler (ad, aciklama) VALUES (?, ?)")->execute([$ad, $aciklama]);
        $kategori_map[$ad] = $pdo->lastInsertId();
    } else {
        $kategori_map[$ad] = $kat['id'];
    }
}

// 2. İlaçlar
$ilaclar_seed = [
    // Analjezikler
    ['Parol 500mg Tablet', 'Analjezikler ve Antipiretikler', '8699514010019', 'Parasetamol', 'Yaygın ağrı kesici'],
    ['Majezik 100mg Tablet', 'Analjezikler ve Antipiretikler', '8699532090283', 'Flurbiprofen', 'Güçlü anti-enflamatuar/ağrı kesici'],
    ['Arveles 25mg Tablet', 'Analjezikler ve Antipiretikler', '8699514010156', 'Deksketoprofen', 'Hızlı etkili ağrı kesici'],
    ['Minoset Plus Tablet', 'Analjezikler ve Antipiretikler', '8699504011248', 'Parasetamol + Kafein', 'Ateş ve ağrı için'],
    ['Apranax Fort 550mg', 'Analjezikler ve Antipiretikler', '8699502040179', 'Naproksen Sodyum', 'Kas ve eklem ağrıları'],
    ['Nurofen Cold&Flu', 'Analjezikler ve Antipiretikler', '8699514010507', 'İbuprofen + Psödoefedrin', 'Soğuk algınlığı'],
    ['Dolven Pediatrik Şurup', 'Analjezikler ve Antipiretikler', '8699532570020', 'İbuprofen', 'Çocuklar için ateş düşürücü'],
    // Antibiyotikler
    ['Augmentin 1000mg Tablet', 'Antibiyotikler', '8699502750018', 'Amoksisilin + Klavulanat', 'Geniş spektrumlu'],
    ['Macrol 500mg Tablet', 'Antibiyotikler', '8699514010262', 'Klaritromisin', 'Makrolid grubu'],
    ['Klamoks 1000mg Tablet', 'Antibiyotikler', '8699532090146', 'Amoksisilin + Klavulanat', 'Solunum ve KBB enfeksiyonları'],
    ['Cipro 500mg Tablet', 'Antibiyotikler', '8699502040506', 'Siprofloksasin', 'İdrar yolları'],
    ['Monurol 3g Şase', 'Antibiyotikler', '8699514240036', 'Fosfomisin', 'Tek dozluk İYE ilacı'],
    // Solunum Sistemi
    ['Asist %4 Şurup', 'Solunum Sistemi', '8699514570027', 'Asetilsistein', 'Balgam söktürücü'],
    ['Nac 600mg Efervesan', 'Solunum Sistemi', '8699532020228', 'Asetilsistein', 'Yoğun balgam tedavisi'],
    ['Ventolin 100mcg İnhaler', 'Solunum Sistemi', '8699522950337', 'Salbutamol', 'Astım ve KOAH'],
    ['Levopront Şurup', 'Solunum Sistemi', '8699502570227', 'Levodropropizin', 'Kuru öksürük baskılayıcı'],
    ['Bricanyl Expektoran', 'Solunum Sistemi', '8699522570115', 'Terbutalin + Guaifenesin', 'Öksürük ve hırıltı'],
    // Sindirim Sistemi
    ['Lansor 30mg Kapsül', 'Sindirim Sistemi', '8699514070060', 'Lansoprazol', 'Mide asidi koruyucu'],
    ['Nexium 40mg Tablet', 'Sindirim Sistemi', '8699633010021', 'Esomeprazol', 'Proton pompası inhibitörü'],
    ['Rennie Çiğneme Tableti', 'Sindirim Sistemi', '8699504080183', 'Kalsiyum + Magnezyum', 'Anlık antiasit'],
    ['Gaviscon Likit Şurup', 'Sindirim Sistemi', '8699522570177', 'Sodyum Aljinat', 'Reflü ve mide yanması'],
    ['Talcid Çiğneme Tableti', 'Sindirim Sistemi', '8699504080060', 'Hidrotalsit', 'Asit giderici'],
    // Kardiyovasküler
    ['Concor 5mg Tablet', 'Kardiyovasküler Sistem', '8699526490145', 'Bisoprolol', 'Beta bloker kalp ilacı'],
    ['Norvasc 5mg Tablet', 'Kardiyovasküler Sistem', '8699514130078', 'Amlodipin', 'Tansiyon ilacı'],
    ['Beloc Zok 50mg', 'Kardiyovasküler Sistem', '8699522090224', 'Metoprolol', 'Kalp yetmezliği / Tansiyon'],
    ['Coraspin 100mg Tablet', 'Kardiyovasküler Sistem', '8699504010265', 'Asetilsalisilik Asit', 'Kan sulandırıcı'],
    ['Ator 10mg Tablet', 'Kardiyovasküler Sistem', '8699502041282', 'Atorvastatin', 'Kolesterol düşürücü'],
    // Dermatolojik
    ['Fucidin %2 Krem', 'Dermatolojik İlaçlar', '8699522350212', 'Fusidik Asit', 'Deri enfeksiyonları'],
    ['Terramycin Merhem', 'Dermatolojik İlaçlar', '8699514380060', 'Oksitetrasiklin', 'Deride yara ilacı'],
    ['Travazol Krem', 'Dermatolojik İlaçlar', '8699504350026', 'İzokonazol + Diflukortolon', 'Mantar ve kaşıntı'],
    ['Advantan %0.1 Krem', 'Dermatolojik İlaçlar', '8699504350125', 'Metilprednizolon', 'Egzama ve dermatit'],
    ['Bepanthol Onarıcı Merhem', 'Dermatolojik İlaçlar', '8690521000631', 'Dekspantenol', 'Cilt onarımı'],
    // Santral Sinir Sistemi
    ['Cipralex 10mg Tablet', 'Santral Sinir Sistemi', '8699522090025', 'Essitalopram', 'Antidepresan'],
    ['Lustral 50mg Tablet', 'Santral Sinir Sistemi', '8699532090412', 'Sertralin', 'Panik bozukluk ve depresyon'],
    ['Selectra 50mg Tablet', 'Santral Sinir Sistemi', '8699514090211', 'Sertralin', 'Depresyon tedavisi'],
    ['Atarax 30mg Şurup', 'Santral Sinir Sistemi', '8699514570058', 'Hidroksizin', 'Sakinleştirici'],
    // Vitaminler
    ['Redoxon C Vitamini', 'Vitamin ve Takviyeler', '8690521000204', 'Askorbik Asit', 'Bağışıklık'],
    ['Devit-3 Damla', 'Vitamin ve Takviyeler', '8699502590058', 'Kolekalsiferol', 'D vitamini takviyesi'],
    ['Benexol B12 Tablet', 'Vitamin ve Takviyeler', '8699504011408', 'B1-B6-B12 Vitamini', 'Sinir sistemi desteği'],
    ['Supradyn Energy', 'Vitamin ve Takviyeler', '8690521000303', 'Multivitamin', 'Enerji ve vitamin desteği'],
    ['Ferrum Fort Tablet', 'Vitamin ve Takviyeler', '8699514010354', 'Demir (III)', 'Demir eksikliği'],
    // Antihistaminikler
    ['Zyrtec 10mg Tablet', 'Antihistaminikler', '8699522090156', 'Setirizin', 'Saman nezlesi ve alerji'],
    ['Claritine 10mg Tablet', 'Antihistaminikler', '8699799031039', 'Loratadin', 'Günde 1 kez alerji ilacı'],
    ['Aerius 5mg Tablet', 'Antihistaminikler', '8699514090402', 'Desloratadin', 'Kaşıntı ve kızarıklık'],
    ['Crebros 5mg Tablet', 'Antihistaminikler', '8699514091225', 'Levosetirizin', 'Alerjik rinit'],
    // Antidiyabetikler
    ['Glucophage 1000mg Tablet', 'Antidiyabetikler', '8699633080016', 'Metformin', 'Tip 2 diyabet'],
    ['Diaformin 1000mg Tablet', 'Antidiyabetikler', '8699502041930', 'Metformin', 'Kan şekeri dengeleme'],
    ['Lantus Solostar Kalem', 'Antidiyabetikler', '8699532950051', 'İnsülin Glargin', 'Uzun etkili insülin'],
    ['Matofin 500mg Tablet', 'Antidiyabetikler', '8699514011887', 'Metformin', 'Şeker hastalığı']
];

$eklenen = 0;
foreach ($ilaclar_seed as $ilac) {
    list($ad, $kat_adi, $barkod, $etken, $aciklama) = $ilac;
    
    // Kategori ID
    $kat_id = $kategori_map[$kat_adi] ?? null;
    
    if ($kat_id) {
        $stmt = $pdo->prepare("SELECT id FROM ilaclar WHERE ad = ? OR barkod = ?");
        $stmt->execute([$ad, $barkod]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO ilaclar (kategori_id, ad, barkod, etken_madde, aciklama) VALUES (?, ?, ?, ?, ?)")->execute([$kat_id, $ad, $barkod, $etken, $aciklama]);
            $eklenen++;
        }
    }
}

echo "Toplam $eklenen yeni ilaç eklendi.\n";

// DB Enum Update for stok
try {
    $pdo->exec("ALTER TABLE stok MODIFY COLUMN durum ENUM('mevcut', 'tukendi') NOT NULL DEFAULT 'mevcut'");
    echo "Stok ENUM tablosu mevcut ve tukendi olarak guncellendi.\n";
} catch(Exception $e) {
    echo "ENUM guncellemesinde uyari: " . $e->getMessage() . "\n";
}

echo "Islem Tamamlandi!\n";



