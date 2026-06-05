# Omurga Yedekleme ve Rollback Sistemi

Omurga 1.0.2 Beta ile yedekleme sistemi iki katmanlı çalışır:

## 1. Site Yedekleri

Yönetim paneli > Sistem > Yedekleme ekranından:

- Veritabanı yedeği
- Upload dosyaları yedeği
- Tam yedek

alınabilir. Geri yükleme işleminden önce sistem otomatik güvenlik yedeği oluşturur.

## 2. Tema/Paket Rollback

Tema veya paket güncelleme, yeniden kurma, sürüm düşürme ya da silme işlemlerinden önce mevcut klasör ZIP olarak yedeklenir.

Yönetim paneli > Sistem > Rollback / Geri Dön ekranında bu yedekler listelenir.

Rollback kuralları:

- Paket aktifse önce devre dışı bırakılır.
- Aktif tema doğrudan geri alınamaz; önce başka tema etkinleştirilmelidir.
- Geri dönmeden önce mevcut sürüm ayrıca yedeklenebilir.
- Rollback yedekleri `storage/backups/extensions` ve eski tema yedekleri için `storage/backups/themes` klasörlerinde tutulur.

Bu sistem, tema/paket güncellemesi sorun çıkarırsa çekirdeğe dokunmadan eski sürüme dönmeyi sağlar.
