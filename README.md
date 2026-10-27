# virtualis-akademia-dist

A **Virtuális Akadémia** WordPress plugin kész, telepíthető változata.

> Ez a repó **automatikusan generálódik**, kézzel ne szerkeszd. A forráskód a
> [designair-hub/virtualis-akademia](https://github.com/designair-hub/virtualis-akademia)
> repóban van; minden `main`-re történő push után a build eredménye ide kerül
> (`bin/publish-dist.sh`).

## Mi van benne

A plugin éles fájljai: fordított CSS/JS (`assets/dist`), a Composer éles
autoloaderje (`vendor`, benne a Plugin Update Checker) – forrásfájlok, tesztek
és fejlesztői eszközök nélkül.

## Frissítés a WordPressben

A telepített plugin ezt a repót figyeli ([Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)):
a legmagasabb `vX.Y.Z` címkén (címke hiányában a `main` ágon) lévő `virtualis-akademia.php`
fejlécének **Version** értékét. Ha az újabb a telepítettnél, a WordPress **Frissítések**
oldalán megjelenik a frissítés, és egy kattintással telepíthető.

- Az ellenőrzés kb. 12 óránként fut; azonnal: **Irányítópult → Frissítések → Újra ellenőrzés**.
- Privát repónál a `wp-config.php`-ba: `define( 'VA_UPDATE_TOKEN', 'ghp_…' );` (csak olvasási jogú token).
- Kikapcsolás (pl. teszt példányon): `define( 'VA_UPDATE_DISABLED', true );`

## Kézi telepítés

A [`main` ág zip-je](https://github.com/designair-hub/virtualis-akademia-dist/archive/refs/heads/main.zip)
a Bővítmények → Új hozzáadása → Bővítmény feltöltése oldalon is feltölthető
(a mappanév a telepítéskor `virtualis-akademia`-ra javítandó, ha a WordPress
a repó nevét használná – a frissítő ezt automatikusan rendezi).
