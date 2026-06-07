# Üyelik ve Giriş Köprüsü

Omurga çekirdeğinde ayrı `/login`, `/register`, `/forgot-password`, `/profile` sayfaları zorunlu değildir.
Varsayılan akış admin giriş ekranı üzerinden çalışır:

- `admin/login.php?tab=login` giriş
- `admin/login.php?tab=register` kayıt
- `admin/login.php?tab=forgot` şifremi unuttum
- `admin/users.php?profile=1` profilim

Tema isterse bu bağlantıları kendi ön yüz sayfalarıyla değiştirebilir.
Çekirdekteki `Giriş / Kayıt` bloğu kullanıcı giriş yapmamışsa giriş/kayıt/şifre sıfırlama bağlantılarını, giriş yapmışsa profil ve panel bağlantılarını gösterir.

## Ayarlar

Aşağıdaki ayarlar `settings` tablosunda tutulur:

- `membership_registration_enabled` — `1` ise kayıt açık.
- `membership_default_role` — yeni kayıt rolü, varsayılan `member`.
- `membership_default_status` — `active` veya `pending`.

## Mail bağlantısı

Şifremi unuttum akışı `om_mail()` köprüsünü kullanır. Mail paketi aktif değilse token oluşturulur fakat e-posta gönderilemeyebilir.
