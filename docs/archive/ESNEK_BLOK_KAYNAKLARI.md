# Omurga v1.6.7 — Esnek Blok Kaynakları

Bu sürümde bloklar tek bir klasöre bağlı değildir.

## Blok kaynakları

- Sistem blokları: Omurga çekirdeğinden gelir.
- Tema blokları: `themes/aktif-tema/blocks/` klasöründen gelir.
- Özel bloklar: `storage/blocks/` klasöründen gelir.

## Gelişmiş blok yapısı

```text
storage/blocks/editor-secimi/
  block.json
  view.php
  style.css
  script.js
```

## Basit blok yapısı

```text
storage/blocks/basit-duyuru.php
```

Basit bloklar otomatik listelenir, ancak gelişmiş ayar ve haber meta alanı için `block.json` kullanılması önerilir.

## Haber ekleme ekranına özel alan ekleme

Bir blok `block.json` içinde `post_meta` tanımlarsa, Haber Ekle/Haber Düzenle ekranında otomatik görünür.

```json
{
  "post_meta": {
    "editor_choice": {
      "type": "checkbox",
      "label": "Editörün Seçimi Bloğunda Göster",
      "default": false
    }
  }
}
```

Blok tarafında bu haberler şu ayarlarla çekilebilir:

```json
"settings": {
  "source": {"type":"select", "default":"meta"},
  "meta_key": {"type":"text", "default":"editor_choice"},
  "meta_value": {"type":"text", "default":"1"}
}
```

## Panel

Yeni ekran:

```text
Tasarım > Bloklar
```

Burada blok kaynağı, slug, kullanım alanı, ayar sayısı ve haber meta alanı sayısı görünür.
