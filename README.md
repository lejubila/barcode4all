# Barcode Generator Pro

Applicazione **Laravel + Docker** per generare barcode professionali in formato
**vettoriale** (SVG, EPS, PDF) e raster ad alta risoluzione (JPEG), con anteprima
live, composizione guidata dei supplementi EAN e generazione in batch da CSV.

---

## ✨ Caratteristiche

- **Tipi di barcode**: ISBN-13, ISBN-10, ISSN, EAN-13, EAN-8, EAN-13 + Add-on 2/5
  cifre, UPC-A, UPC-E, Code 128, Code 39.
- **Formati di output**:
  - **SVG** — vettoriale nativo.
  - **EPS** — PostScript nativo, compatto (~3–4 KB, font Courier non incorporato).
  - **PDF** — vettoriale via DOMPDF.
  - **JPEG** — raster a 150 / 300 / 600 DPI (librsvg + ImageMagick).
- **Calcolo automatico del check digit** (EAN-13/8, ISBN-10/13, ISSN) e rifiuto
  dei codici con cifra di controllo errata.
- **Supplementi EAN-13 + 2 / + 5** con composizione **guidata** (valuta + prezzo
  per i libri, numero di edizione per i periodici) oppure **inserimento diretto**.
- **Anteprima live** con debounce (Alpine.js + Tailwind).
- **Generazione batch** da CSV con download in ZIP.
- **Rate limiting** sugli endpoint di generazione.

---

## 🧱 Stack tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | PHP 8.2, Laravel 11 |
| Barcode | [picqer/php-barcode-generator](https://github.com/picqer/php-barcode-generator) |
| PDF | [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) |
| EPS | Generatore PostScript nativo |
| JPEG | librsvg (`rsvg-convert`) + ImageMagick |
| Frontend | Blade + Alpine.js + Tailwind (CDN) |
| Container | Docker Compose (php-fpm, nginx, MySQL 8) |

---

## 🚀 Avvio rapido

Requisiti: **Docker** e **Docker Compose**.

```bash
# 1. Copia la configurazione
cp .env.example .env

# 2. Avvia lo stack (app + nginx + MySQL)
docker compose up -d

# 3. Genera la chiave applicativa ed esegui le migrazioni
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Apri **http://localhost:8080**.

> **Porta occupata?** La porta host è configurabile: la 80 del container è mappata
> su `${APP_PORT:-8080}`. Se la 8080 è già in uso:
> ```bash
> APP_PORT=8091 docker compose up -d   # poi apri http://localhost:8091
> ```

### Comandi utili

```bash
docker compose exec app php artisan optimize:clear   # svuota le cache
docker compose exec app php artisan test             # esegue i test
docker compose logs -f app                           # log applicativi
docker compose down                                  # ferma lo stack
```

---

## 🔌 Endpoint

| Metodo | Rotta | Descrizione |
|---|---|---|
| `GET` | `/` | Form di generazione con anteprima live |
| `POST` | `/generate` | Anteprima AJAX → JSON `{ ok, svg, dataUri }` |
| `POST` | `/download` | Scarica uno o più formati (più formati → ZIP) |
| `POST` | `/batch` | Upload CSV → ZIP con tutti i barcode |

Gli endpoint `generate` / `download` / `batch` sono protetti da rate limiting
(`throttle:60,1`).

---

## 📚 Tipi di barcode e add-on

### Supplementi EAN (Add-on)

L'add-on può essere inserito **direttamente** (2 o 5 cifre) o **composto in modo
guidato** secondo gli standard:

- **EAN-13 + 5** (prezzo consigliato libri): 1ª cifra = valuta, restanti 4 = prezzo
  ×100 (max 99,99). Es. USD 24,95 → `52495`.
  Valute: `0`/`1` = GBP £, `3` = AUD $, `4` = NZD $, `5` = USD $, `6` = CAD $,
  `9` = nessun prezzo (`90000`).
- **EAN-13 + 2** (periodici): numero progressivo di edizione/fascicolo (00–99).

---

## 📦 Generazione batch (CSV)

Carica un CSV con intestazione opzionale e le colonne:

```csv
type,code,addon,output_format
ISBN13,9780306406157,,svg
EAN13,5901234123457,,pdf
EAN13+5,5901234123457,52495,svg
CODE128,HELLO-123,,svg
```

Il risultato è uno ZIP con tutti i file generati; le righe in errore vengono
raccolte in un `errors.txt` incluso nell'archivio.

---

## 🗂️ Struttura del progetto

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/BarcodeController.php
│   │   └── Requests/GenerateBarcodeRequest.php
│   └── Services/
│       ├── BarcodeService.php          # generazione SVG + check digit + add-on
│       ├── ExportService.php           # SVG → PDF / EPS / JPEG
│       └── RawBarcodeGenerator.php      # accesso ai dati barra grezzi (picqer)
├── resources/views/barcode/index.blade.php
├── routes/web.php
├── tests/Feature/BarcodeGenerationTest.php
├── docker/
│   ├── php/Dockerfile                   # php:8.2-fpm + gd/zip + gs + ImageMagick + librsvg
│   └── nginx/default.conf
└── docker-compose.yml
```

---

## 🧪 Test

```bash
docker compose exec app php artisan test
```

La suite copre: generazione ISBN-13 valido, rifiuto del check digit errato,
conversione ISBN-10 → 13, EAN-13 + add-on a 5 cifre, codifica ISSN (prefisso 977),
conversioni SVG → PDF / EPS (vettoriale e compatto) / JPEG 300 DPI e l'endpoint di
anteprima.

---

## 🔒 Sicurezza

- Validazione e sanitizzazione dell'input (solo caratteri ammessi per tipo).
- File temporanei in `storage/app/temp/`, eliminati dopo l'invio.
- Rate limiting sugli endpoint di generazione.

---

## 📄 Licenza

Distribuito sotto licenza **MIT**.
