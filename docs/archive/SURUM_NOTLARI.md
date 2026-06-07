# Omurga v3.6.8 Sürüm Notları

- Sabit sayfa şablonlarında `$page` / `$post` uyumluluğu güçlendirildi.
- Temel yorum altyapısı, admin yorum yönetimi ve tema yardımcı fonksiyonları eklendi.
- Yorumlar varsayılan olarak haber/topluluk profillerinde açık, kurumsal/boş profillerinde kapalı başlar; içerik bazında değiştirilebilir.
- Yorum gönderiminde CSRF, honeypot, e-posta doğrulama, uzunluk sınırı ve güvenli escape/temizleme uygulanır.

- Admin `addnews.php` uyumluluk kısayolunda bootstrap yüklenmeden çağrılan profil yardımcıları düzeltildi.
- Zip çıkarma işlemleri güvenli yol denetiminden geçirildi.
- Medya ve yedek silme işlemleri izinli kök klasörlerle sınırlandı.
- Genel pakete ait olmayan örnek/placeholder metinler nötrleştirildi.
- Eski dağıtım kopyası paket dışına çıkarıldı.

## Sol Menü Logo Kartı Düzeltmesi

- Admin panel sol menüsünün altındaki Omurga logo kartının menü içeriklerini kapatması düzeltildi.
- Sidebar yapısı flex düzene alındı.
- Menü alanı kendi içinde kaydırılabilir hale getirildi.
- Alt logo kartı artık menü linklerinin üzerine binmez.
- Menü daraltıldığında alt logo kartı gizlenir.
- Mobil görünümde logo kartı normal akışta kalır.

Bu sürümde yeni özellik eklenmedi; sadece panel kullanılabilirliği düzeltildi.
