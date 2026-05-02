# 🔐 Giriş ve Güvenlik Testleri

Sistemin kapılarını ve anahtarlarını kontrol ediyoruz.

### 1. Yeni Kayıt Olma
- [+] `/auth/register.php` sayfasını açın. Form düzgün görünüyor mu?
- [+] Hiçbir şey yazmadan "Kayıt Ol"a basın. Hata veriyor mu?
- [+] Yanlış bir e-posta yazın (örn: `selam.com`). Uyarı çıkıyor mu?
- [+] Şifreyi 123 yazıp deneyin. "Kısa" uyarısı veriyor mu?
- [+] Tüm bilgileri doğru girip kayıt olun. Giriş sayfasına atıyor mu?

### 2. Giriş Yapma (Login)
- [+] Yanlış şifre yazınca "Hatalı giriş" diyor mu?
- [+]`e@e` ve `123456` (veya kendi şifrenizle) girince panel açılıyor mu?
- [+] Çıkış (Logout) butonuna basınca sistemden atıyor mu?

### 3. Güvenlik (Hack Kontrolü)
- [+] Giriş yapmadan `/admin/index.php` yazınca sizi login sayfasına geri gönderiyor mu?
- [+] Normal kullanıcı hesabı ile Admin sayfasına girmeye çalışınca "Yetkiniz yok" diyor mu?
- [+] Form alanlarına `<script>` gibi kodlar yazınca sistem bunları temizliyor mu?
