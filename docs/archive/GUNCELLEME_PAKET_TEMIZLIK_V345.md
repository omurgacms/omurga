# Omurga v3.4.5 — Güncelleme Paket Temizliği ve Kayıt Limiti

Bu sürüm `Sistem > Sistem Sağlığı ve Güncellemeler` ekranını düzenler.

## Eklenenler

- Yüklü güncelleme paketleri tek tek silinebilir.
- Tüm yüklü güncelleme paketleri tek tıkla silinebilir.
- Güncelleme uygulandıktan sonra zip paketini otomatik silme ayarı eklendi.
- Son Güncelleme Kayıtları için limit eklendi.
- Varsayılan güncelleme kayıt limiti: 30.
- Kayıt limiti panelden 5–200 arasında ayarlanabilir.
- Yüklü paket limiti panelden 1–100 arasında ayarlanabilir.
- Limit aşılırsa eski güncelleme kayıtları ve eski yüklenen paketler otomatik temizlenir.

## Korunanlar

- Güncelleme öncesi yedek alma sistemi korunur.
- `config.php`, `uploads`, `storage` koruması devam eder.
- Manşet / Son Dakika / Headline çekirdeğe eklenmez.
