# Bildirim Sistemi

Omurga 1.0.7.5 Beta ile panel içi bildirim altyapısı güçlendirilmiştir.

## Çekirdek API

```php
omurga_notify('Başlık', 'Mesaj', 'info', 'admin/example.php');
om_notify('Başlık', 'Mesaj', 'success');
omurga_notify_admins('Yeni yorum', 'Onay bekleyen yorum var.', 'comment', 'admin/comments.php?status=pending');
```

## Desteklenen türler

- info
- success
- warning
- danger
- update
- package
- theme
- comment
- user
- security
- system

## Yetki

Bildirim yönetimi için `notifications.manage` yetkisi eklenmiştir. `system.manage` ve eski uyumluluk için `users.manage` yetkisi olan kullanıcılar da bildirim sayfasına erişebilir.

## Otomatik bildirim üreten olaylar

- Yeni yorum gönderilmesi
- Yorum durumunun değiştirilmesi
- Yeni kullanıcı oluşturulması
- Kullanıcı güncellenmesi
- Paket etkinleştirme / pasifleştirme / silme
- Güncelleme bulunması ve uygulanması
- Güvenlik merkezi ayarlarının güncellenmesi

## Mail köprüsü

Bildirimler panel içidir. E-posta bildirimleri için mail paketi ileride `omurga_notification_created` hook'unu yakalayabilir.
