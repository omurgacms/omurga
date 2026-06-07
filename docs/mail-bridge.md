# Omurga Mail Bridge

Omurga CMS 1.0.7.5 Beta ile çekirdeğe sade bir mail köprüsü eklenmiştir.

Çekirdek SMTP, mail şablonu, kuyruk veya toplu mail sistemi içermez. Bu işler paketlere bırakılır. Çekirdeğin görevi standart bir çağrı noktası sağlamaktır.

## Kullanım

```php
om_mail('kullanici@example.com', 'Başlık', '<p>Merhaba</p>');
```

Detaylı sonuç almak için:

```php
$result = omurga_mail_result('kullanici@example.com', 'Başlık', 'İçerik');

if (!$result['success']) {
    // $result['message']
}
```

## Paket entegrasyonu

Bir mail paketi `omurga.mail.send` filtresini yakalar:

```php
omurga_add_filter('omurga.mail.send', function($result, $payload) {
    // SMTP / servis gönderimi burada yapılır.

    return [
        'success' => true,
        'handled' => true,
        'message' => 'E-posta gönderildi.',
        'payload' => $payload,
    ];
}, 10);
```

## Payload alanları

- `to`
- `subject`
- `body`
- `html`
- `from`
- `reply_to`
- `cc`
- `bcc`
- `attachments`
- `headers`
- `template`
- `context`
- `options`

## Hooklar

```php
omurga.mail.before_send
omurga.mail.send
omurga.mail.after_send
```

## Çekirdek davranışı

Mail paketi aktif değilse sistem hata vermez, beyaz sayfa oluşturmaz. `om_mail()` false döndürür. `omurga_mail_result()` ise açıklayıcı sonuç dizisi döndürür.
