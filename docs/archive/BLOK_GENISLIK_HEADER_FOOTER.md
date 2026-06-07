# Omurga v1.6.1 - Blok Genişliği ve Header/Footer Düzeni

Bu sürümde Düzen sistemi daha kullanışlı hale getirildi.

## Blok genişliği

Her blok için genişlik seçilebilir:

- %100
- %75
- %66
- %50
- %33
- %25

Örnek kullanım:

- Büyük Manşet: %50
- Haber Listesi: %50

Bu iki blok masaüstünde yan yana görünür. Mobilde otomatik %100 olur ve alt alta sıralanır.

## Ayrı Header / Footer düzeni

Ana sayfa blokları ile üst/alt alan blokları karışmasın diye yeni ekran eklendi:

Panel > Header / Footer

Buradan şu bölgeler yönetilir:

- Üst Alan
- Alt Alan
- Mobil Alt Alan

Ana sayfa blokları ise Panel > Düzen ekranında yönetilmeye devam eder.

## Tema bağlantısı

Düzen alanları hâlâ aktif temanın `theme.json` dosyasındaki `regions` alanından gelir. Tema hangi alanları destekliyorsa panelde o alanlar görünür.
