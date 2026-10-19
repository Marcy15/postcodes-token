# A települési adatállomány eredete és javításai

A `cities.json` a mellékelt `postoffice.dump.sql` 3574 eredeti településrekordjából készült. **Minden `id`, `zip_code`, `city`, `id_county` mezőt változtatás nélkül őrzünk meg.**

Az eredeti `counties` tábla hibás: hiányzik az `id=1` (Budapest), `id=2` (Bács-Kiskun), `id=3` (Baranya); a `12`-es azonosítónál `Szepes` szerepel `Komárom-Esztergom` helyett. Három, településhez nem kapcsolódó extra sor is szerepel benne: `44=Aba`, `45=Baranya`, `46=Szepes`.

A `counties.json` ezért **normalizált, 20 elemű megye-/fővárostábla**, `1..20` ID-kkel. Ez eltér az eredeti SQL `counties` tábla tartalmától, de megszünteti a hiányzó hivatkozásokat és lehetővé teszi az összes megyére történő szűrést.

**Fontos korlát:** a forrás `cities` táblája település–megye párosításokban további hibákat is tartalmazhat. Ezeket nem javítjuk automatikusan, mert nem áll rendelkezésre hiteles összehasonlító országos címtár. A keresés a kapott adatokra épül. Az eredeti SQL a `database/sql/postoffice.dump.sql` fájlban érintetlenül megtalálható; azt **nem kell importálni**.
