# Oxygen Medical Shorts

Egyszerű, gyors YouTube Shorts carousel WordPress / Divi oldalakra. Admin felületen
adhatók hozzá videók, shortcode-dal jelennek meg. Plugin függőség nélkül, Swiper.js alapon.

## Funkciók

- **Saját admin felület**: „Shorts videók" menüpont, natív WP szerkesztő élménnyel
- **Per-videó mezők**: cím, leírás (excerpt), borítókép (opcionális — ha üres, YouTube auto-thumbnail), YouTube URL
- **Publikálva / Piszkozat státusz** → egy kattintással ki-be kapcsolhatók a videók
- **Dátum vagy kézi sorrend** (drag & drop admin listában)
- **Carousel megjelenítés**: Swiper.js, touch-swipe, reszponzív, lazy iframe
- **Autoplay + loop** 3000ms-el (konfigurálható)
- **Divi-kompatibilis stílus**: ETmodules font ikonok a navigációhoz, customizálható színek
- **Lazy iframe**: YouTube iframe csak kattintásra töltődik → nem rontja a PageSpeed-et

## Telepítés

1. Töltsd le a legfrissebb release ZIP-et a [Releases](../../releases) oldalról
2. WP admin → **Plugins → Új telepítése → Plugin feltöltése** → ZIP kiválasztása
3. **Telepítés → Aktiválás**
4. Bal oldali menüben megjelenik a **Shorts videók** menüpont

## Használat

### Új videó felvétele

**Shorts videók → Új videó**:
- **Cím**: a videókártyán látszó fejléc (UPPERCASE-re konvertálódik alapból)
- **Kivonat** (jobb oldali panel, opcionális): 1-2 mondatos leírás a cím alá
- **Kiemelt kép** (jobb oldali panel, opcionális): ha üres, YouTube auto-borító
- **YouTube URL**: elfogad `shorts/`, `watch?v=`, `youtu.be/` formátumokat

### Megjelenítés

Divi Code modulba (vagy Text modulba, shortcode-widgetbe) illeszd:

```
[om_shorts limit="6" title="Egy perc Oxygen Medical" intro="..." ]
```

### Shortcode paraméterek

| Paraméter | Alapért. | Leírás |
|---|---|---|
| `limit` | 6 | hány videót jelenítsen meg |
| `title` | — | szekció cím (H2) |
| `intro` | — | bevezető szöveg |
| `desktop` | 4 | ≥1200px: hány látszik egyszerre |
| `tablet` | 3 | ≥900px: hány látszik |
| `mobile` | 1.2 | <640px: hány látszik |
| `autoplay` | 1 | automatikus lapozás (0=ki) |
| `autoplay_delay` | 3000 | ms a lapozások között |
| `loop` | 1 | végtelenített lapozás (0=ki) |
| `orderby` | date | `date`, `menu_order`, `rand` |
| `order` | DESC | `DESC` vagy `ASC` |
| `accent` | — | kiemelt szín felülírása (pl. `accent="#c0392b"`) |

### Szín és egyéb megjelenés

**Shorts videók → Megjelenés** oldalon natív WP color picker-rel
állíthatók: accent szín, cím szín, szövegszín, kártya háttér, CTA badge szöveg,
autoplay/loop/hover beállítások.

## Kompatibilitás

- WordPress 5.5+
- PHP 7.4+
- Divi téma (az ETmodules font a nav gombok ikonjához)

## Licenc

GPLv2 vagy újabb, lásd `LICENSE`.

## Changelog

Lásd `CHANGELOG.md`.

## Fejlesztő

[UP2Digital Kft.](https://up2digital.hu)
