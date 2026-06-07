# Omurga v2.6 — Bildirimler + İşlem Kayıtları

Bu sürüm Omurga çekirdeğine panel bildirimleri ve gelişmiş işlem kayıtları ekler.

## Bildirimler

Panel yolu:

```text
Sistem > Bildirimler
```

Bildirimler şu alanlarla saklanır:

- Tür: bilgi, başarı, uyarı, hata
- Başlık
- Mesaj
- Bağlantı
- Okundu / okunmadı
- Kullanıcıya özel veya genel bildirim

Kullanım:

```php
omurga_notify('Yeni haber incelemeye gönderildi', 'Muhabirin haberi editör onayı bekliyor.', 'info', 'admin/posts.php?status=pending');
```

## İşlem Kayıtları

Panel yolu:

```text
Sistem > İşlem Kayıtları
```

Kayıtlarda şunlar tutulur:

- Tarih
- Kullanıcı
- İşlem
- Modül
- Varlık türü
- Varlık ID
- IP adresi
- Açıklama
- Detay JSON

Kullanım:

```php
log_activity('post.publish', 'Haber yayınlandı', null, 'posts', 'post', 15);
```

## Çekirdek dışı bırakılanlar

Manşet, mobil manşet, sürmanşet, son dakika gibi tema/blok alanları çekirdeğe eklenmedi.
