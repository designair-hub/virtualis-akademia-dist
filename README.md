# virtualis-akademia-dist

A **Virtuális Akadémia** WordPress plugin nyilvános kiadási (distribution) repója.

Ebben a repóban **nincs forráskód**, csak a [Release-ek](https://github.com/designair-hub/virtualis-akademia-dist/releases):
minden Release egy `virtualis-akademia.zip` csatolmányt tartalmaz (éles build, a plugin mappájával).
A Release-eket a forrás-repó GitHub Actions workflow-ja készíti, ha a `main`-re új verzió kerül.

A telepített plugin ezeket a Release-eket figyeli ([Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)),
és ha újabb verziót talál, a WordPress **Frissítések** oldalán felkínálja. Kézi telepítéshez a
legutóbbi Release zip-jét kell feltölteni (Bővítmények → Új hozzáadása → Bővítmény feltöltése).
