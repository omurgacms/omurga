# Paket Geliştirici Kılavuzu

Bu kılavuz Omurga CMS 1.0.3.7 Beta paket API için hazırlanmıştır.

1. `packages/paket-slug/` klasörü oluştur.
2. `package.json` dosyasını ekle.
3. Ana dosya olarak `package.php` kullan.
4. Yönetim ekranı gerekiyorsa `Omurga::addPackagePage()` kullan.
5. Ayar ekranı gerekiyorsa `Omurga::addPackageSettings()` ve `Omurga::addPackageSettingsPage()` kullan.
6. Veritabanı güncellemesi gerekiyorsa `Omurga::addPackageMigration()` kullan.

Paketler çekirdek dosyaları değiştirmemelidir. Paketler hook, ayar, blok ve yönetim sayfası API ile genişletme yapmalıdır.
