# Changelog

Minden változás verziónként dokumentálva.

## [1.3.0] - 2026-04-20

### Módosítva
- Nav gombok stílusa a Divi Blog Carousel (dgbc) plugin design-ját követi:
  53×53px fehér doboz, ETmodules font ikonok (data-icon="4" / "5"),
  -70px kívül pozíció, nincs border-radius és box-shadow
- Cím és accent alapszín `#0b5f5b`

### Hozzáadva
- Autoplay 3000ms alapból, konfigurálható a Megjelenés oldalon és shortcode attr-ral
- Loop (végtelenített lapozás) alapból
- Pause on hover támogatás
- Videó indításkor autoplay leáll (ne lapozza ki a játszó videót)
- Shortcode attr-ok: `autoplay`, `autoplay_delay`, `loop`

## [1.2.0] - 2026-04-20

### Módosítva
- Max-width megkötés kivéve, a carousel teljes szélességet használ
- Navigációs nyilak kívülre kerültek (-50px), belső padding eltávolítva
- Kártyák egyforma magasságúak (swiper-wrapper align-items: stretch)

## [1.1.0] - 2026-04-20

### Hozzáadva
- WP admin Megjelenés beállítási oldal színpicker-ekkel (accent, cím, leírás,
  kártya háttér, szekció cím)
- CTA badge a kártya alján (mint az akciós árcímke), opcionális
- UPPERCASE cím toggle
- Szekció cím alatti accent színű vonal

### Módosítva
- CSS változók bevezetve (`--om-accent`, `--om-title`, stb.)

## [1.0.0] - 2026-04-20

### Első verzió
- CPT `om_short` létrehozása (cím, excerpt, featured image, YouTube URL meta)
- Shortcode `[om_shorts]` alapverziója
- Swiper.js carousel CDN-ről
- Lazy iframe betöltés (thumbnail + kattintásra iframe)
- YouTube-nocookie.com embed (GDPR-barát)
- Admin oszlopok: borító előnézet, video URL link
