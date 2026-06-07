# Omurga v3.4.6 — İçerik / Sabit Sayfa / Kategori Düzeni

Bu güncelleme çekirdek içerik giriş ekranlarını sadeleştirir.

## Değişiklikler

- Sosyal medya görseli ekleme alanı kaldırıldı.
- Yazı/Haber ekleme ekranından görünür İçerik Türü seçimi kaldırıldı.
- Sabit Sayfa ekranından görünür İçerik Türü seçimi kaldırıldı.
- Sabit sayfalarda kategori ve etiket alanları kullanılmaz.
- Sabit sayfalarda öne çıkan görsel alanı gösterilmez.
- Sabit sayfalarda Tema/Blok Alanları gösterilmez; manşet/son dakika gibi haber-vitrin alanları sayfaya gelmez.
- Haber/Yazı ekranında birden fazla kategori seçilebilir.
- İlk seçilen kategori geriye uyumluluk için `category_id` alanında ana kategori olarak tutulur.
- Çoklu kategori ilişkisi `post_categories` tablosunda saklanır.
- Yazı/Sabit Sayfa ekranında sidebar seçimi eklendi:
  - Tema tek sidebar içeriyorsa sadece göster/gizle görünür.
  - Tema birden fazla sidebar içeriyorsa sidebar seçimi + göster/gizle görünür.
- Kategori ve Etiket ekranlarında yeni ekleme/düzenleme formu üst alana taşındı.
- Kategori/etiket listesi geniş alanda gösterilir; isimler sıkışmaz.

## Çekirdeğe eklenmeyenler

- Manşet
- Son dakika
- Sürmanşet
- Mobil manşet
- Haber botu

Bu alanlar tema, blok veya eklenti tarafında kalmaya devam eder.
