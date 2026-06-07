# Omurga v3.6 — Stabil Çalışma ve Admin Test Güncellemesi

Bu sürüm yeni haber/manşet modülü eklemez. Amaç çekirdeği daha stabil ve ürün gibi kullanılabilir hale getirmektir.

## Yapılanlar

- Omurga sürümü 3.6 yapıldı.
- Sistem menüsüne `Kurulum Sonrası Test` ekranı eklendi.
- Yeni test ekranında PHP, PDO MySQL, GD/WebP, ZipArchive, yazılabilir klasörler, aktif profil, aktif tema ve temel tema şablonları kontrol edilir.
- Test ekranına kritik admin sayfaları için hızlı bağlantılar eklendi.
- Son hata kayıtları panelden görülebilir hale getirildi.
- Profil değiştirme açıklaması güvenli hale getirildi: mevcut içerik, sayfa, kategori, medya ve menüler silinmez.
- Yazılar ekranındaki `+ Yeni Yazı` butonu profil adına göre dinamik hale getirildi.
- Admin CSS önbellek etiketi 3.6 olarak güncellendi.

## Çekirdek kararı

Haber, manşet, son dakika, sürmanşet, proje, yönetim kurulu gibi özel işler çekirdeğe eklenmez. Bunlar tema, blok veya eklenti tarafında kalır.
