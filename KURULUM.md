# Omurga CMS 1.2.0 RC1 Kurulum

Bu sürüm yeni özellik sürümü değildir. 1.2.0 RC1, temiz kurulum ve mevcut kurulumlarda stabilizasyon/release candidate hazırlığı için çıkarılmıştır. Yeni özellikler dondurulmuştur; hedef hata düzeltme, güvenlik, kurulum uyumluluğu, medya, tema/paket, migration ve admin kararlılığıdır.

1. Zip dosyasını açın.
2. Paket içindeki dosyaları hosting `public_html` veya ilgili site kök dizinine yükleyin.
3. Boş bir MySQL veritabanı oluşturun.
4. Site adresini tarayıcıda açın veya `/install/` adresine gidin.
5. Veritabanı bilgilerini, site profilini ve admin hesabını girin.
6. Kurulum tamamlandıktan sonra panel adresi: `/admin`

## Profil ve tema seçimi

- Haber profili seçilirse Haber V1 aktif gelir.
- Kurumsal profili seçilirse Kurumsal V1 aktif gelir.
- Topluluk profili seçilirse Topluluk V1 aktif gelir.
- Profil seçilmezse Haber V1 varsayılan tema olur.

## Güncelleme olarak yükleme

Mevcut Omurga kurulumunun üzerine dosyaları yazdırmadan önce mutlaka dosya ve veritabanı yedeği alın.

1. `config.php` dosyanızı koruyun.
2. `storage/installed.lock` dosyanızı koruyun.
3. Eski zip paketlerini proje kökünde bırakmayın.
4. Yükleme sonrası `/admin/health-check.php`, `/admin/system-tests.php`, `/admin/migrations.php` ve `/admin/diagnostics.php` ekranlarını kontrol edin.

## Dağıtım notu

Bu paket kurulu siteye ait `config.php`, `storage/installed.lock`, log ve cache çıktıları içermez.
