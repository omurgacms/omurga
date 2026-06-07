# Omurga v3.4.2 — Sabit Sayfa URL + Editör Düzeltmesi

- Sabit sayfalar WordPress mantığında kök URL ile çalışır: `/hakkimizda`, `/iletisim`.
- Eski `/sayfa/slug` isteği gelirse otomatik `/slug` adresine yönlendirilir.
- Slug çakışmalarına karşı `admin`, `kategori`, `etiket`, `uploads`, `storage` gibi kök alanlar rezerve edildi.
- Yeni sabit sayfa kaydedilince `page-edit.php` ekranına döner.
- Omurga Editör JavaScript sözdizimi hatası giderildi.
- Görsel, galeri ve HTML modu tekrar çalışacak şekilde senkronizasyon düzeltildi.
- Meta açıklama sayaç kodu güvenli hale getirildi.
