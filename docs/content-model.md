# Omurga İçerik Modeli

Omurga çekirdeğinde genel CMS mantığına yakın iki ana içerik türü vardır.

## Yazılar

Yazılar dinamik içeriktir. Haber, blog, duyuru ve makale gibi akışa giren içerikler burada yönetilir.

Yazılarda kategori, etiket, yorum, öne çıkan görsel ve yayın tarihi kullanılır. Son içerikler, popüler içerikler, öne çıkan içerikler gibi dinamik bloklar yalnızca yazı akışını kullanır; sayfalar bu bloklara dahil edilmez.

## Sayfalar

Sayfalar sabit içeriktir. Hakkımızda, İletişim, KVKK, Künye ve Reklam gibi linkle veya menüyle ulaşılan içerikler burada yönetilir.

Sayfalar kategori ve etiket kullanmaz. Dinamik yazı akışına, son içerikler bloklarına ve popüler içerik listelerine otomatik olarak girmez. Sayfa URL yapısı kökten çalışır: `/sayfa-slug`.

## Yönetim Menüsü

İçerikler menüsü altında şu ayrım korunur:

- Yazılar
- Sayfalar
- Kategoriler
- Etiketler
- Yorumlar

Bu ayrım çekirdek seviyesinde korunur; tema ve paketler sayfaları yazı akışına karıştırmamalıdır.
