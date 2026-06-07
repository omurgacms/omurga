# Omurga v3.4.1 — Statik Sayfa / Yazı Ayrımı

Bu güncellemede statik sayfalar yazı/haber içeriklerinden tamamen ayrıldı.

## Değişiklikler
- `admin/pages.php` ayrı sabit sayfa listesi eklendi.
- `admin/page-edit.php` ayrı sabit sayfa düzenleme girişi eklendi.
- Sabit sayfalarda kategori ve etiket alanları gösterilmez.
- Sabit sayfa kaydedilirken `category_id` boş bırakılır ve etiketler temizlenir.
- Yazı/haber listesi artık varsayılan olarak `page` tipini listelemez.
- Admin menüsündeki Statik Sayfalar bağlantısı ayrı ekrana bağlandı.

## Kural
- Yazı/Haber: kategori ve etiket kullanabilir.
- Statik Sayfa: kategori ve etiket kullanmaz.
