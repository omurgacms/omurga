# Omurga v3.4.3 — İçerik 404 ve URL Yönlendirme Düzeltmesi

Bu güncelleme içerik ekledikten sonra bağlantıya tıklayınca oluşan 404 sorununu düzeltir.

## Yapılanlar

- Haber/yazı URL yapısı `/haber/slug` olarak netleştirildi.
- Sabit sayfalar kökten çalışır: `/hakkimizda`, `/iletisim`.
- Eski kök haber bağlantıları otomatik `/haber/slug` adresine yönlendirilir.
- Admin girişliyken taslak/beklemede içerikler önizlenebilir.
- Sabit sayfa önizleme bağlantıları düzeltildi.
- Haber liste önizleme bağlantısı `post_url()` fonksiyonuna bağlandı.
- `/sayfa/slug` eski sabit sayfa bağlantısı `/slug` adresine yönlenir.

Çekirdeğe manşet, son dakika veya haber botu eklenmemiştir.
