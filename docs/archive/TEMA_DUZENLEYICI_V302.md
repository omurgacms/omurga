# Omurga v3.0.2 - Tema Düzenleyici Sidebar ve Logo Düzeltmesi

Bu ara sürüm Tema Düzenleyici kullanımında görülen iki kullanıcı deneyimi sorununu düzeltir.

## Düzeltilenler

- Admin sidebar daraltıldığında durum tarayıcıda saklanır.
- Tema Düzenleyici içinde başka dosyaya tıklanınca sidebar kendiliğinden tekrar açılmaz.
- Sidebar sadece kullanıcı menü butonuna tekrar basarsa genişler.
- Mobilde hamburger menü normal off-canvas davranışını korur; mobil menü durumu kalıcı yapılmaz.
- Üst bardaki Omurga logosu biraz büyütüldü.
- Sidebar altındaki Omurga logosu biraz büyütüldü.

## Teknik not

Kalıcı durum `localStorage` içinde `omurga_admin_sidebar_collapsed` anahtarıyla tutulur.
