# Barcode Generator Pro — Piano progetto per Claude Code

## Obiettivo
Applicazione Laravel + Docker per generare barcode professionali in formato vettoriale.

### Tipi di barcode supportati
- ISBN-13, ISBN-10
- ISSN
- EAN-13, EAN-8
- EAN-13 + Add-on 2 cifre (prezzo)
- EAN-13 + Add-on 5 cifre (prezzo esteso)
- UPC-A, UPC-E
- Code 128, Code 39

### Formati di output
- **SVG** (vettoriale nativo, ideale per web e stampa)
- **EPS** (vettoriale, standard industria grafica)
- **PDF** (vettoriale, pronto per la stampa)
- **JPEG** (raster ad alta risoluzione, 300-600 DPI)

---

## Struttura del progetto da creare

```
barcode-generator/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
├── .env.example
├── app/
│   ├── Http/Controllers/
│   │   └── BarcodeController.php
│   ├── Services/
│   │   ├── BarcodeService.php
│   │   └── ExportService.php
│   └── Http/Requests/
│       └── GenerateBarcodeRequest.php
├── resources/views/
│   └── barcode/
│       └── index.blade.php  (o React se preferisci SPA)
└── routes/web.php
```

---

## Istruzioni per Claude Code

### Fase 1 — Setup Docker

Crea `docker-compose.yml` con i seguenti servizi:

```yaml
services:
  app:
    build: ./docker/php
    volumes:
      - .:/var/www
    depends_on:
      - db
  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: barcodes
      MYSQL_ROOT_PASSWORD: secret
```

**Dockerfile PHP** deve includere:
- Base: `php:8.2-fpm`
- Estensioni: `gd`, `zip`, `pdo_mysql`, `mbstring`
- Ghostscript: `apt-get install -y ghostscript`
- ImageMagick: `apt-get install -y imagemagick libmagickwand-dev`
- Composer

### Fase 2 — Dipendenze PHP (composer.json)

```
picqer/php-barcode-generator   → generazione SVG/PNG barcode
barryvdh/laravel-dompdf         → export PDF
```

Installa con:
```bash
composer require picqer/php-barcode-generator
composer require barryvdh/laravel-dompdf
```

### Fase 3 — BarcodeService

Crea `app/Services/BarcodeService.php` con:

```php
<?php
namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    // Metodi da implementare:

    public function generateSVG(string $code, string $type, array $options = []): string
    // Ritorna SVG string con il barcode

    public function generateEAN13WithAddon(string $ean13, string $addon, string $format): mixed
    // Genera EAN-13 + Add-on 2 o 5 cifre
    // Il campo addon può essere prezzo es. "99" o "15999"

    public function generateISBN(string $isbn, array $options = []): string
    // ISBN-13 è EAN-13; calcola check digit se mancante

    public function generateISSN(string $issn, array $options = []): string
    // ISSN → converte in EAN con prefisso 977
}
```

**Logica EAN + Add-on (importante):**
- EAN-13 principale: larghezza standard
- Add-on 2 cifre: spazio separatore 5 moduli, poi 2 barre
- Add-on 5 cifre: spazio separatore 5 moduli, poi 5 barre
- I due simboli condividono la stessa linea di base

### Fase 4 — ExportService

Crea `app/Services/ExportService.php`:

```php
public function toEPS(string $svgContent, string $filename): string
// Usa Ghostscript per convertire SVG→EPS
// Comando: gs -dNOPAUSE -dBATCH -sDEVICE=eps2write ...

public function toPDF(string $svgContent, string $filename): string
// Usa DOMPDF con il SVG embedded in HTML

public function toJPEG(string $svgContent, int $dpi, string $filename): string
// Usa ImageMagick: convert -density {dpi} input.svg output.jpg
```

### Fase 5 — Controller e Routes

`BarcodeController.php`:
```php
public function index()          // mostra form
public function generate(Request $r)  // genera + mostra preview SVG
public function download(Request $r)  // scarica nel formato scelto
public function batch(Request $r)     // genera più barcode (CSV upload)
```

Routes in `routes/web.php`:
```
GET  /             → form
POST /generate     → preview AJAX (ritorna JSON con SVG)
POST /download     → scarica file
POST /batch        → upload CSV, download ZIP
```

### Fase 6 — UI (Blade + Alpine.js o React)

Form con:
- **Tipo barcode**: select (ISBN-13, ISSN, EAN-13, EAN-8, EAN+2, EAN+5, UPC-A, Code128)
- **Codice**: input text con validazione live
- **Add-on** (visibile solo per EAN+2/+5): input separato
- **Formato output**: checkbox multipli (SVG, EPS, PDF, JPEG)
- **DPI** (solo JPEG): 150/300/600
- **Larghezza barre**: slider fine/medio/largo
- **Testo sotto**: toggle mostra/nascondi cifre
- **Preview live**: SVG visualizzato inline aggiornato in tempo reale
- **Download**: bottone per ogni formato selezionato

### Fase 7 — Batch mode

- Upload CSV con colonne: `type,code,addon,output_format`
- Elaborazione con Laravel Queue
- Download ZIP con tutti i file generati

---

## Note tecniche importanti

### EAN + Add-on
La libreria `picqer/php-barcode-generator` NON supporta nativamente i supplementi.
Devi implementare la composizione manuale in SVG:
1. Genera SVG del barcode EAN-13 principale
2. Genera SVG dell'add-on (tipo `UPCE` o personalizzato)
3. Unisci i due SVG con offset X calcolato correttamente
4. Aggiungi il testo del supplemento sopra le barre add-on

### Check digit
- EAN-13: somma pesata 1-3 alternata, mod 10
- ISBN-13: identico a EAN-13
- ISBN-10: somma pesata 10-1, mod 11 (X=10)
- ISSN: somma pesata 8-2, mod 11

### Conversione SVG → EPS con Ghostscript
```bash
gs -dNOPAUSE -dBATCH -dEPSCrop \
   -sDEVICE=eps2write \
   -sOutputFile=output.eps \
   input.pdf
```
Prima converti SVG→PDF con DOMPDF, poi PDF→EPS con Ghostscript.

### Sicurezza
- Sanitizza sempre il codice input (solo numeri/lettere ammessi)
- I file temporanei vanno in `storage/app/temp/` e cancellati dopo il download
- Aggiungi rate limiting alle API di generazione

---

## Comandi rapidi

```bash
# Avvia il progetto
docker-compose up -d

# Installa dipendenze
docker-compose exec app composer install
docker-compose exec app php artisan migrate

# Crea storage symlink
docker-compose exec app php artisan storage:link

# Svuota cache
docker-compose exec app php artisan optimize:clear
```

---

## Test

Crea `tests/Feature/BarcodeGenerationTest.php` con test per:
- Generazione ISBN-13 valido
- Rifiuto ISBN con check digit errato
- Generazione EAN-13 + Add-on 5 cifre
- Conversione SVG → EPS
- Conversione SVG → PDF
- Download JPEG 300 DPI

