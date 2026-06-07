# Omurga v3.5.1 - URL Uyumluluk Temizliği

Bu sürümde yayında kullanılmamış eski URL uyumluluk yönlendirmeleri kaldırıldı.

## Kalan temiz URL yapısı

- Yazılar: `/yazi/yazi-slug`
- Sabit sayfalar: `/sayfa-slug`
- Kategoriler: `/kategori/kategori-slug`
- Etiketler: `/etiket/etiket-slug`

## Kaldırılanlar

- `/haber/yazi-slug` -> `/yazi/yazi-slug` yönlendirmesi
- Kökten yazı slug arayıp `/yazi/slug` adresine yönlendirme
- `/sayfa/sayfa-slug` -> `/sayfa-slug` yönlendirmesi

Omurga henüz yayında kullanılmadığı için eski bağlantı uyumluluğu tutulmadı. Böylece router daha sade ve temiz hale getirildi.
