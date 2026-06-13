# Known Issues - Omurga CMS 1.2.0 RC1

Omurga CMS 1.2.0 RC1 yeni özellik sürümü değildir. Yeni özellikler dondurulmuştur; bu dosya stabilizasyon adayında kalan bilinen riskleri takip eder.

## Kalan Bilinen Sorunlar

- Bazı manuel akışlar ortam bağımlıdır: büyük ZIP/görsel yükleme testleri XAMPP `upload_max_filesize` ve `post_max_size` değerlerine bağlıdır.
- Tema/paket güvenlik taraması statik analizdir; üçüncü taraf kodlar canlıya alınmadan önce ayrıca gözden geçirilmelidir.
- RC dağıtım paketi bu çalışma sırasında üretilmedi; paketleme öncesi `config.php`, `storage/installed.lock`, eski zipler, log/cache/tmp çıktıları için son dışlama denetimi yapılmalıdır.

## RC Kapsam Dışı

- Yeni büyük özellik ekleme.
- Büyük refactor.
- `bootstrap.php` dosyasını parçalama.
- Tema görünümünü yeniden tasarlama.
- Gereksiz veritabanı şema değişikliği.
