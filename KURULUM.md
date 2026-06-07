# Omurga CMS 1.0.7.5 Beta Kurulum

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

## Dağıtım notu

Bu paket kurulu siteye ait `config.php`, `storage/installed.lock`, log ve cache çıktıları içermez.
