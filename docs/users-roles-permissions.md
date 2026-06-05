# Kullanıcı ve Yetki Sistemi

Omurga çekirdeğinde tanıdık ve kontrollü kullanıcı/rol/yetki sistemi bulunur.

## Varsayılan roller

- Süper Yönetici: korumalı tam yetki.
- Yönetici: günlük site yönetimi için geniş yetki.
- Editör: içerik, sayfa, yorum, kategori, etiket, medya ve form yönetimi.
- Yazar: kendi içeriklerini oluşturur/düzenler ve incelemeye gönderir.
- Muhabir: haber akışı için taslak oluşturur ve incelemeye gönderir.
- Üye: temel/kısıtlı erişim.

## Korumalı rol

`super_admin` rolü korumalıdır. Süper Yönetici olmayan kullanıcılar bu rolü değiştiremez veya silemez.
Yeni kurulumda ilk kullanıcı `super_admin` olarak oluşturulur.

## Yönetim ekranları

- `admin/users.php`: kullanıcı ekleme/düzenleme/silme
- `admin/roles.php`: varsayılan roller ve sahip oldukları yetkiler
- `admin/permissions.php`: çekirdek yetki anahtarları

## Paket ve tema kuralları

Paketler kendi yetkilerini kaydedebilir. Temalar kullanıcı/rol/yetki sistemini değiştiremez. Çekirdek güvenlik yetkileri korumalıdır.
