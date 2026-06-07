# Omurga v1.6.1 - Akıllı Satır Sistemi

Bu sürümde Düzen ve Header/Footer ekranlarında bloklar akıllı satır mantığıyla çalışır.

## Mantık

- Aynı satırdaki blokların toplamı %100'ü geçmiyorsa yan yana görünür.
- Toplam %100'ü geçerse yeni satıra geçer.
- Yeni blok eklerken `Otomatik / Kalan Alan` seçiliyse sistem son satırdaki boş alanı hesaplar.
- Örnek: Bir satırda %50 blok varsa yeni eklenen blok otomatik %50 olur.
- Örnek: Bir satırda %70 blok varsa yeni eklenen blok otomatik %30 olur.
- Mobilde tüm bloklar otomatik %100 olur ve alt alta gelir.

## Genişlik seçenekleri

%100, %75, %70, %67, %66, %50, %33, %30, %25

## Kullanım örneği

- Büyük Manşet: %70
- Son Haberler: %30

Masaüstünde yan yana, mobilde alt alta görünür.
