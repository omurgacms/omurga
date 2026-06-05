# Güvenlik Taraması Bilgilendirme Modu

Omurga CMS 1.0.2 Beta aşamasında tema ve paket güvenlik taraması engelleyici değil, bilgilendirici çalışır.

## Davranış

- Tema içinde riskli fonksiyon görüldüğünde tema kurulumu engellenmez.
- Paket içinde riskli fonksiyon görüldüğünde paket kurulumu engellenmez.
- Uyarılar Güvenlik Merkezi ekranında bilgi olarak gösterilir.
- Aktivite kayıtlarına bilgilendirme notu düşülebilir.

## Kalan Koruma

Çekirdek koruması korunur:

- `/core`
- `/admin`
- `/install`
- `bootstrap.php`

Bu alanlar tema/paket müdahalelerine karşı korunmaya devam eder.

## Neden?

Tema ve paket ekosistemi gelişirken yanlış pozitiflerin geliştiricileri engellememesi için bu mod tercih edildi. Omurga Merkezi büyük güncellemesi geldiğinde resmi mağaza paketleri için daha sıkı güvenlik politikası uygulanabilir.
