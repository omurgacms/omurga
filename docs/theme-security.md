# Omurga Tema Güvenlik Sistemi

Omurga, yanlışlıkla tüm temaların silinmesi veya bozuk bir temanın siteyi beyaz ekrana düşürmesi riskini azaltmak için tema güvenlik kuralları uygular.

## Silme Kuralları

- Aktif tema silinemez.
- Sistem temaları silinemez.
- Sistemde en az iki tema kalmak zorundadır.
- Tema silinmeden önce isteğe bağlı yedek alınabilir. Yönetim panelinde bu seçenek varsayılan olarak işaretlidir.

## Sistem Temaları

`theme.json` içinde aşağıdaki alan varsa tema sistem teması sayılır:

```json
{
  "system_theme": true
}
```

Omurga paketinde `omurga-kolay` ve `omurga-sabit` sistem teması olarak işaretlidir.

## Otomatik Kurtarma

Aktif tema eksik, bozuk veya geçersizse Omurga güvenli sistem temasına döner. Bu durumda işlem loglanır ve admin panel erişilebilir kalır.

## Tema Zip Güvenliği

Tema yüklemede şu kontroller yapılır:

- `theme.json` var mı?
- Tema ID/slug zaten kurulu mu?
- `.tpl` tema mı?
- Zorunlu dosyalar var mı?
- Zip içinde güvensiz dosya yolu var mı?
- Riskli PHP ifadeleri var mı?

