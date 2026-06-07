# Omurga CMS 1.0.8 Beta - Dağıtım Notları

Bu paket, Omurga çekirdeği ile birlikte üç varsayılan tema içerir:

- Haber V1 (`themes/haber-v1`)
- Kurumsal V1 (`themes/kurumsal-v1`)
- Topluluk V1 (`themes/topluluk-v1`)

## Kurulum profiline göre aktif tema seçimi

- Haber profili: Haber V1
- Kurumsal profili: Kurumsal V1
- Topluluk profili: Topluluk V1
- Boş/hızlı kurulum: Haber V1

## 1.0.8 Beta ile gelenler

- Haber V1, Kurumsal V1 ve Topluluk V1 aynı dağıtım paketine eklendi.
- Demo içerik yapısı `demos/` klasörü altında düzenlendi.
- Kurulum profil seçimine göre varsayılan tema belirleme yapısı korundu.
- Dağıtım paketi temizlendi ve ürün paketine uygun hale getirildi.

## Dağıtım temizliği

- `config.php` pakete dahil edilmedi.
- `storage/installed.lock` pakete dahil edilmedi.
- Log/cache çıktıları temizlendi.
- `packages/` klasörü temiz tutuldu.
- Demo içerikler `demos/` altında ayrıca eklendi.
