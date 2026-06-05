# Omurga Otomatik Kaydetme Sistemi

Bu güncelleme yazı ve sayfa editörlerine otomatik taslak kaydı ekler.

## Özellikler

- Yazı ve sayfa düzenleme ekranında otomatik kayıt çubuğu görünür.
- Varsayılan kayıt aralığı 30 saniyedir.
- İçerik, başlık, spot, SEO, görsel yolu, galeri, durum ve editör alanları geçici taslak olarak saklanır.
- Kayıt gerçek yazının üzerine yazılmaz; `post_autosaves` tablosunda kullanıcıya özel tutulur.
- Editörde otomatik kayıt bulunduğunda “Son otomatik kaydı geri yükle” butonu görünür.
- Normal kaydetme başarılı olunca ilgili otomatik kayıt temizlenir.

## Veritabanı

Tablo:

```text
post_autosaves
```

Alanlar:

```text
id
autosave_key
post_id
user_id
content_type
payload
created_at
updated_at
```

## Ayarlar

```text
autosave_enabled = 1
autosave_interval_seconds = 30
```

## Güvenlik

- Autosave API admin oturumu ister.
- CSRF kontrolü yapılır.
- Kayıtlar kullanıcı bazlıdır; başka kullanıcıların otomatik kayıtları görünmez.
