# Omurga v3.5.9 — Yazı Ekle 500 Düzeltmesi

Bu sürüm, yazı ekleme ekranında bazı hostinglerde görülen HTTP 500 hatasını engellemek için hazırlandı.

## Yapılanlar

- PHP 7.4 / eksik eklenti uyumluluğu için güvenli polyfill eklendi.
- `mbstring` kapalı olan hostinglerde `mb_strlen`, `mb_substr`, `mb_strtolower`, `mb_convert_case` kaynaklı fatal hatalar engellendi.
- PHP 8 fonksiyonları olan `str_starts_with`, `str_ends_with`, `str_contains` için yedek fonksiyonlar eklendi.
- Yazı editörü tasarımı korunmuştur.
- Çekirdeğe haber/manşet gibi özel modül eklenmemiştir.

## Not

Bu düzeltme özellikle `admin/addnews.php` ve `admin/post-edit.php` açılırken beyaz ekran / HTTP 500 oluşmasını önlemek içindir.
