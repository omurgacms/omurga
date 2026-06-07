# Omurga v2.2 — Kullanıcı Rolleri ve Yayın Onay Sistemi

Bu sürüm Omurga çekirdeğine rol/yetki ve yayın akışı disiplinini ekler.

## Roller

- Yönetici: tüm yetkiler.
- Editör: içerik ekler/düzenler/yayınlar, kategori ve medya yönetir.
- Muhabir: haber ekler, kendi haberini düzenler, doğrudan yayınlayamaz.
- Yazar: yazı ekler, kendi yazısını düzenler, doğrudan yayınlayamaz.
- Reklam Yöneticisi: sadece reklam alanlarını yönetir.
- Tasarım Yöneticisi: tema, düzen, blok ve menü alanlarını yönetir.

## Yayın durumları

- Taslak
- İncelemede
- Yayında
- Planlandı
- Arşivde

Muhabir veya yazar `Yayında` ya da `Planlandı` seçse bile sistem bunu otomatik `İncelemede` durumuna çeker. Yayına alma yetkisi editör ve yöneticidedir.

## Çekirdek prensip

Manşet, sürmanşet, mobil manşet gibi tema/blok alanları çekirdeğe eklenmedi. Bu sürüm sadece kullanıcı, yetki ve yayın onay akışını düzenler.
