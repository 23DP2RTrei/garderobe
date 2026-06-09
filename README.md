# Garderobe — Digitālās garderobes pārvaldības sistēma

Tīmekļa lietojumprogramma apģērbu katalogēšanai, tērpu kombināciju veidošanai un personalizētu ieteikumu saņemšanai.

---

## Tehnoloģijas

| Slānis      | Tehnoloģija                        |
|-------------|------------------------------------|
| Backend     | PHP 8.0+                           |
| Datubāze    | MySQL 8.0+                         |
| Frontend    | HTML5, CSS3, JavaScript (ES6)      |
| UI ietvars  | Bootstrap 5.3                      |
| Ikonas      | Bootstrap Icons 1.11               |
| Grafiki     | Chart.js 4.4                       |
| Laikapstākļi| Open-Meteo API (bezmaksas)         |
| Vide        | Apache (XAMPP)                     |

---

## Funkcionalitāte

### Pamata (visi lietotāji)
- Reģistrācija un pieteikšanās ar sesijām
- Apģērbu pievienošana, rediģēšana, dzēšana (CRUD)
- Attēlu augšupielāde ar automātisku fona noņemšanu (PHP GD)
- Filtrēšana pēc kategorijas, sezonas, krāsas, mīļākajiem
- Meklēšana pēc nosaukuma
- Šķirošana (jaunākie / vecākie / nosaukums)
- Lapošana (12 apģērbi lapā)
- Apģērbu atzīmēšana kā "mīļākie"
- Tērpu kombināciju veidošana ar vizuālu builder
- Kombināciju saglabāšana un valkāšanas reižu skaitīšana
- Statistika ar Chart.js grafikiem (joslu, donut)
- Profila rediģēšana un paroles maiņa
- - AI tērpu ieteikumi balstoties uz sezonu un reāliem laikapstākļiem
- Vizuāls "mini-fit" apģērbu priekšskatījums
- PDF eksports ar pilnu garderobes pārskatu


### Administrators
- Visu lietotāju pārskatīšana
- Lietotāja lomas maiņa (user / premium / admin)
- Lietotāja dzēšana
- Sistēmas statistika (kopējais lietotāju, apģērbu, kombināciju skaits)
- Pēdējās pieslēgšanās laika pārraudzība

---

## Datubāzes struktūra

```
users              — lietotāji (id, name, email, password, role, last_login, ...)
clothing           — apģērbi (id, user_id, name, category, color, season, is_favorite, ...)
outfits            — kombinācijas (id, user_id, name, times_worn, ...)
outfit_clothing    — kombinācija ↔ apģērbs (many-to-many)
ai_suggestions     — AI ieteikumi (id, user_id, suggestion_text, clothing_ids, ...)
```

**SQL iespējas:** JOIN, GROUP BY, ORDER BY, HAVING, subvaicājumi, agregācijas (COUNT, SUM, COALESCE), ārējās atslēgas ar CASCADE dzēšanu.

---

## Drošība

- **CSRF tokeni** — visās POST formās
- **SQL injection** aizsardzība — PDO prepared statements
- **XSS** aizsardzība — `htmlspecialchars()` visur
- **Paroles** — `password_hash()` ar BCRYPT
- **Sesijas** — servera puses sessiju pārvaldība
- **Autorizācija** — ownership pārbaude katrā darbībā
- **Failu augšupielāde** — MIME tipa validācija, izmēra ierobežojums (5 MB)

---

## Instalācija uz XAMPP (localhost)

### Prasības
- PHP 8.0+, MySQL 8.0+, Apache (XAMPP)

### Soļi

1. **Kopēt projektu:**
   ```
   C:\xampp\htdocs\garderobe\
   ```

2. **Izveidot datubāzi:**
   - Atvērt `http://localhost/phpmyadmin`
   - New → nosaukums `garderobe` → Create
   - Import → izvēlēt `database.sql` → Go

3. **Konfigurācija** (`includes/config.php`):
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('SITE_URL', 'http://localhost/garderobe');
   ```

4. **Pārliecināties**, ka mape `uploads/` eksistē (gitignore to izslēdz — izveidot manuāli).

5. **Atvērt:** `http://localhost/garderobe`

---

## Izvietošana uz publiskā hostinga (InfinityFree)

1. Reģistrēties: [infinityfree.com](https://infinityfree.com)
2. Izveidot hostinga kontu → iegūt FTP datus
3. Augšupielādēt visus failus uz `htdocs/garderobe/`
4. Vadības panelī izveidot MySQL datubāzi → importēt `database.sql`
5. Atjaunināt `includes/config.php`:
   ```php
   define('DB_HOST', 'sql###.infinityfree.com');
   define('DB_NAME', 'epiz_###_garderobe');
   define('DB_USER', 'epiz_###_user');
   define('DB_PASS', 'jūsu-parole');
   define('SITE_URL', 'https://jusudomens.infinityfreeapp.com/garderobe');
   ```
6. Izveidot `uploads/` mapi ar atļauju `755`

---

## Lietotāju lomas

| Loma      | Apraksts                                                    |
|-----------|-------------------------------------------------------------|
| user      | Garderobe, kombinācijas, statistika, mīļākie, profils       |
| premium   | + AI ieteikumi ar vizuālu fit preview, PDF eksports         |
| admin     | Viss + lietotāju pārvaldība, sistēmas statistika            |

---

## Demo pieteikšanās

| Lauks   | Vērtība              |
|---------|----------------------|
| E-pasts | `admin@garderobe.lv` |
| Parole  | `Admin123!`          |

> **Svarīgi:** Mainiet paroli pēc pirmās pieteikšanās!

---

## Failu struktūra

```
garderobe/
├── index.php               ← Publiskā sākumlapa
├── database.sql            ← Datubāzes shēma ar demo datiem
├── css/
│   └── style.css           ← Dizains (dark/light mode, responsive)
├── js/
│   └── main.js             ← Dark mode, attēlu priekšskatījums
├── uploads/                ← Augšupielādētie attēli (gitignore)
├── includes/
│   ├── config.php          ← DB konfigurācija + auto-migrācija
│   ├── auth.php            ← Autentifikācija, CSRF, attēlu apstrāde
│   ├── header.php          ← Navigācija (dark mode toggle)
│   └── footer.php          ← Apakšdaļa
└── pages/
    ├── login.php           ← Pieteikšanās
    ├── register.php        ← Reģistrācija ar validāciju
    ├── wardrobe.php        ← Garderobe (filtri, lapošana, mīļākie)
    ├── clothing_save.php   ← Apģērba saglabāšana/rediģēšana
    ├── toggle_favorite.php ← Mīļāko pārslēgšana
    ├── outfits.php         ← Kombinācijas + vizuāls builder
    ├── outfit_save.php     ← Kombinācijas saglabāšana
    ├── stats.php           ← Statistika ar Chart.js grafikiem
    ├── ai.php              ← AI ieteikumi (premium)
    ├── export_pdf.php      ← PDF eksports (premium)
    ├── profile.php         ← Profila rediģēšana
    ├── admin.php           ← Administrācijas panelis
    └── logout.php          ← Izrakstīšanās
```

---

## Autors

**Roberts Treijs** — Rīgas Valsts Tehnikums, 2026
