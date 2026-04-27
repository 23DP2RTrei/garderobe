# 🧥 Garderobe — Digitālās garderobes pārvaldības sistēma
## Instalācijas norādījumi

### Prasības
- PHP 8.0+
- MySQL 8.0+
- Apache (XAMPP ieteicams)

---

## 1. Uzstādīšana uz XAMPP (localhost)

1. **Kopēt projektu:**
   Kopēt mapi `garderobe/` uz `C:\xampp\htdocs\garderobe\`

2. **Izveidot datu bāzi:**
   - Atvērt `http://localhost/phpmyadmin`
   - Noklikšķināt "New" → izveidot `garderobe` datu bāzi
   - Importēt failu `database.sql` (Import → Choose File)

3. **Konfigurācija:**
   Atvērt `includes/config.php` un pārbaudīt:
   ```php
   define('DB_USER', 'root');   // Jūsu MySQL lietotājs
   define('DB_PASS', '');        // Jūsu MySQL parole (XAMPP = tukšs)
   define('SITE_URL', 'http://localhost/garderobe');
   ```

4. **Izveidot uploads mapi:**
   Pārliecināties, ka mape `uploads/` eksistē un ir rakstāma.

5. **Atvērt pārlūkā:**
   `http://localhost/garderobe`

---

## 2. Uzstādīšana uz hostinga (InfinityFree, 000webhost u.c.)

1. Augšupielādēt visus failus caur File Manager vai FTP uz `public_html/garderobe/`
2. Izveidot MySQL datu bāzi hostinga vadības panelī
3. Importēt `database.sql`
4. Atjaunināt `includes/config.php`:
   ```php
   define('DB_HOST', 'sql...');          // Hostinga MySQL serveris
   define('DB_NAME', 'dbXXX_garderobe'); // Datu bāzes nosaukums
   define('DB_USER', 'dbXXX_user');      // Lietotājvārds
   define('DB_PASS', 'parole');          // Parole
   define('SITE_URL', 'https://jusudomens.infinityfreeapp.com/garderobe');
   ```

---

## Lietotāju lomas
| Loma       | Apraksts                                           |
|------------|----------------------------------------------------|
| user       | Pamata lietotājs — garderobe, kombinācijas, statistika |
| premium    | + AI ieteikumi, PDF eksports                       |
| admin      | Visi + lietotāju pārvaldība                        |

## Demo pieteikšanās
- **E-pasts:** `admin@garderobe.lv`
- **Parole:** `Admin123!`

⚠️ **Mainiet demo paroli pirmajā pieteikšanās reizē!**

---

## Failu struktūra
```
garderobe/
├── index.php              ← Sākumlapa
├── database.sql           ← Datu bāzes shēma
├── css/style.css          ← Dizains
├── js/main.js             ← JavaScript
├── uploads/               ← Augšupielādētie attēli
├── includes/
│   ├── config.php         ← Datu bāzes konfigurācija
│   ├── auth.php           ← Autentifikācija
│   ├── header.php         ← Navigācija
│   └── footer.php         ← Apakšdaļa
└── pages/
    ├── login.php          ← Pieteikšanās
    ├── register.php       ← Reģistrācija
    ├── wardrobe.php       ← Garderobe (galvenā)
    ├── clothing_save.php  ← Apģērba saglabāšana
    ├── outfits.php        ← Kombinācijas
    ├── outfit_save.php    ← Kombinācijas saglabāšana
    ├── stats.php          ← Statistika
    ├── ai.php             ← AI ieteikumi (premium)
    ├── export_pdf.php     ← PDF eksports (premium)
    ├── profile.php        ← Profils
    ├── admin.php          ← Administrācija
    └── logout.php         ← Iziet
```
