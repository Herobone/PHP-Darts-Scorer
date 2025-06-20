# Projektdokumentation - PHP Darts Scorer

**Autor:** Julius G.  
**Datum:** 20. Juni 2025  
**Version:** 0.1.2

## 1. Projektübersicht

### 1.1 Projektbeschreibung
Der PHP Darts Scorer ist eine webbasierte Anwendung zur Verwaltung und Auswertung von Dart-Spielen. Die Anwendung ermöglicht es Benutzern, Spiele zu erstellen, Punkte zu verwalten und ihre Spielhistorie einzusehen.

### 1.2 Technologie-Stack
- **Backend:** PHP 8.4+
- **Frontend:** TypeScript, Tailwind CSS, Flowbite
- **Datenbank:** PostgreSQL
- **Web Server:** FrankenPHP/Caddy
- **Build Tools:** Vite, Bun
- **Template Engine:** Twig
- **Containerisierung:** Docker & Docker Compose

### 1.3 Hauptfunktionen
- Benutzerregistrierung und -authentifizierung
- Erstellung und Verwaltung von Dart-Spielen
- Punkteeingabe und -tracking
- Spielhistorie und Statistiken
- Responsive Webdesign mit automatischem Dark Mode

## 2. Systemarchitektur

### 2.1 MVC-Architektur
Das Projekt folgt dem Model-View-Controller (MVC) Architekturmuster:

- **Model:** Datenbankzugriff über `Database` Klasse und PostgreSQL
- **View:** Twig Templates im `templates/` Verzeichnis
- **Controller:** PHP Controller-Klassen im `src/Controller/` Verzeichnis

### 2.2 Projektstruktur
```
/
├── src/                    # PHP Quellcode
│   ├── Controller/         # Controller-Klassen
│   ├── Core/              # Kern-Klassen (Router, Database, etc.)
│   └── migrations/        # Datenbank-Migrationen
├── templates/             # Twig Templates
├── assets/                # Frontend Assets (TS, CSS)
├── public/                # Öffentlich zugängliche Dateien
├── build/                 # Kompilierte Assets
└── docker-compose.yaml    # Container-Konfiguration
```

### 2.3 Routing-System
Das Routing erfolgt über eine zentrale `Router`-Klasse in `src/Core/Router.php`. Routen werden in `src/base.php` definiert:

- **GET /** - Startseite
- **GET/POST /login** - Benutzeranmeldung
- **GET/POST /register** - Benutzerregistrierung
- **GET/POST /game/create** - Spiel erstellen
- **GET/POST /game/score** - Punkteeingabe
- **GET /history** - Spielhistorie

## 3. Datenbankdesign

### 3.1 Datenbank-Schema
Die Anwendung verwendet PostgreSQL mit folgenden Haupttabellen:

#### users
- `id` (UUID, Primary Key)
- `username` (TEXT, UNIQUE)
- `email` (TEXT, UNIQUE)
- `password` (TEXT, gehashed)
- `created_at`, `updated_at` (TIMESTAMPTZ)

#### games
- `id` (UUID, Primary Key)
- `owner_id` (UUID, Foreign Key zu users)
- `start_score` (INTEGER)
- `single_in`, `double_in`, `single_out`, `double_out` (BOOLEAN)
- `is_active` (BOOLEAN)

#### game_players
- `id` (SERIAL, Primary Key)
- `game_id` (UUID, Foreign Key zu games)
- `player_name` (TEXT)
- `finish_position` (INTEGER)
- `finished_at` (TIMESTAMPTZ)

#### score_history
- `id` (SERIAL, Primary Key)
- `game_player_id` (INTEGER, Foreign Key zu game_players)
- `score` (INTEGER)
- `is_bust_shot`, `is_turn_ender` (BOOLEAN)

### 3.2 Migrationen
Datenbank-Migrationen befinden sich in `src/migrations/up/` und werden automatisch über die `Database::migrate()` Methode ausgeführt.

## 4. Backend-Implementierung

### 4.1 Controller-Struktur
Alle Controller erben von `BaseController` und implementieren spezifische Funktionalitäten:

#### AuthController
- `login()` - Anzeige des Login-Formulars
- `handleLogin()` - Verarbeitung der Anmeldedaten
- `register()` - Anzeige des Registrierungsformulars
- `handleRegister()` - Verarbeitung der Registrierungsdaten
- `logout()` - Benutzerabmeldung

#### GameController
- `create()` - Anzeige des Spielerstellungsformulars
- `store()` - Speicherung eines neuen Spiels

#### ScoreController
- `score()` - Anzeige der Punkteeingabe-Oberfläche
- `submit()` - Verarbeitung der eingegebenen Punkte
- `undo()` - Rückgängigmachen der letzten Punkteeingabe

### 4.2 Session-Management
Benutzersitzungen werden über eine angepasste `PostgresSessionHandler` Klasse in der Datenbank gespeichert, um Skalierbarkeit zu gewährleisten.

### 4.3 Sicherheit
- Passwörter werden mit `password_hash()` gehashed
- Session-basierte Authentifizierung
- CSRF-Schutz durch Session-Validierung
- Input-Validierung und -Sanitization

## 5. Frontend-Implementierung

### 5.1 TypeScript-Architektur
Das Frontend ist in TypeScript implementiert mit modularer Struktur:

- `main.ts` - Haupteinstiegspunkt
- `modules/navbar.ts` - Navigation
- `pages/create_game.ts` - Spielerstellung
- `pages/scoring.ts` - Punkteeingabe

### 5.2 Styling
- **Tailwind CSS** für Utility-First CSS
- **Flowbite** für UI-Komponenten
- Responsive Design
- Custom CSS in `assets/css/styles.css`

### 5.3 Build-System
- **Vite** als Build-Tool und Dev-Server
- **Bun** als Package Manager
- Automatische Asset-Optimierung und Hot Module Replacement (HMR)

## 6. Deployment

### 6.1 Docker-Konfiguration
Die Anwendung wird über Docker Compose bereitgestellt:

#### Services:
- **app** - FrankenPHP Application Server (Port 80/443)
- **postgres** - PostgreSQL Datenbank (Port 5432)
- **pgadmin** - PostgreSQL Admin Interface (Port 5050)

### 6.2 Umgebungsvariablen
- `DEV` - Entwicklungsmodus
- `MIGRATE` - Automatische Datenbankmigrationen (kann auch über den /debug/migrate Endpunkt erfolgen, oder im Worker Mode automatisch)
- Datenbankverbindungsparameter in `config.php`

### 6.3 Production Build
```bash
# Docker Container starten
docker-compose up -d
```

## 7. Features und Funktionalitäten

### 7.1 Benutzerauthentifizierung
- Registrierung neuer Benutzer mit E-Mail-Validierung
- Sichere Anmeldung mit gehashten Passwörtern
- Session-basierte Zustandsverwaltung

### 7.2 Spielverwaltung
- Erstellung von Dart-Spielen
- Konfiguration von Ein- und Auswurfregeln (Single/Double)
- Mehrspielerfähigkeit mit beliebiger Spieleranzahl
- Automatische Spielerverwaltung und Reihenfolge

### 7.3 Punktesystem
- Präzise Punkteeingabe mit Validierung
- Automatische Bust-Erkennung
- Undo-Funktionalität für Korrekturen
- Echtzeit-Punkteberechnung und Anzeige

### 7.4 Spielhistorie
- Vollständige Speicherung aller Spiele
- Detaillierte Punktehistorie
- Spielerstatistiken und Ranglisten

## 8. Testing und Qualitätssicherung

### 8.1 Code-Qualität
- PSR-4 Autoloading Standard
- Typisierte PHP-Methoden mit DocBlocks
- TypeScript für typsichere Frontend-Entwicklung
- Konsistente Code-Formatierung

### 8.2 Error Handling
- Zentrale Fehlerbehandlung über `ErrorController`
- Prepared Statements für SQL-Injection-Schutz
- Try-catch Blöcke für kritische Operationen
- Debug-Modus für Entwicklung

## 9. Entwicklung und Wartung

### 9.1 Entwicklungsumgebung
```bash
# Abhängigkeiten installieren
bun install
composer install

# Entwicklungsserver starten
bun run dev

# Docker Development
# Hier wird nur die Datenbank benötigt
docker-compose up -d postgres

docker run \
   -e FRANKENPHP_CONFIG="./build/index.php --watch /app/**/*" -e MIGRATE="true" \
   -v $PWD/Caddyfile:/etc/caddy/Caddyfile \
   -v $PWD:/app \
   -p 80:80 -p 0.0.0.0:443:443/tcp -p 0.0.0.0:443:443/udp \
   --rm --network php-darts-scorer_default \
   herobone/frank
```

### 9.2 Datenbankmigrationen
Neue Migrationen werden in `src/migrations/up/` als SQL-Dateien erstellt und automatisch beim Start ausgeführt.

### 9.3 Asset-Entwicklung
- Vite Dev-Server mit HMR für schnelle Entwicklung
- Automatische TypeScript-Kompilierung
- CSS-Preprocessing mit Tailwind

## 10. Benutzerdokumentation

### 10.1 Systemanforderungen

Zur Ausführung des PHP Darts Scorer ist eine moderne Docker-Umgebung erforderlich. Die Anwendung wurde mit PHP 8.4, PostgreSQL und modernen Webtechnologien entwickelt. Für die lokale Nutzung wird Docker Desktop empfohlen, das alle benötigten Komponenten automatisch bereitstellt.

Zur Nutzung und lokalen Ausführung der Anwendung sind folgende Komponenten erforderlich:

- **Docker Desktop** (oder vergleichbare Container-Umgebung)
- **Docker Compose** ab Version 2.0
- **Git** für das Klonen des Repositories
- **Moderner Webbrowser** (z. B. Google Chrome, Mozilla Firefox, Safari)
- **Mindestens 2 GB freier Arbeitsspeicher**
- **Internetverbindung** für das Herunterladen der Docker Images

### 10.2 Installation und Einrichtung

#### Schritt 1: Repository klonen
```bash
git clone https://github.com/herobone/php-darts-scorer.git
cd php-darts-scorer
```

#### Schritt 2: Docker Container starten
```bash
# Docker Container im Hintergrund starten
docker-compose up -d

# Logs verfolgen (optional)
docker-compose logs -f
```

#### Schritt 3: Anwendung aufrufen
Die Datenbank wird automatisch beim ersten Start migriert.

- **Hauptanwendung:** http://localhost
- **PostgreSQL Admin (pgAdmin):** http://localhost:5050
  - E-Mail: admin@example.com
  - Passwort: admin

### 10.3 Erste Schritte

#### Benutzerregistrierung
1. Öffnen Sie http://localhost in Ihrem Browser
2. Klicken Sie auf "Register" in der Navigation
3. Geben Sie folgende Daten ein:
   - Benutzername (eindeutig)
   - E-Mail-Adresse
   - Sicheres Passwort (mindestens 8 Zeichen)
4. Klicken Sie auf "Registrieren"
5. Sie werden automatisch zur Anmeldeseite weitergeleitet

#### Anmeldung
1. Geben Sie Ihren Benutzernamen und Ihr Passwort ein
2. Klicken Sie auf "Anmelden"
3. Nach erfolgreicher Anmeldung gelangen Sie zur Startseite

### 10.4 Spielverwaltung

#### Neues Spiel erstellen
1. Klicken Sie auf "Start New Game" auf der Startseite
2. Konfigurieren Sie das Spiel:
   - **Startpunktzahl:** Standard 501 (anpassbar)
   - **Einstiegsregeln:** Single In oder Double In
   - **Ausstiegsregeln:** Single Out oder Double Out
3. Fügen Sie Spieler hinzu:
   - Klicken Sie auf "Add Player"
   - Geben Sie den Namen des Spielers ein
   - Wiederholen Sie den Vorgang für alle Spieler
4. Klicken Sie auf "Create Game"

#### Punkte eingeben
1. Das Spiel startet automatisch nach der Erstellung
2. Der aktuelle Spieler wird hervorgehoben
3. Geben Sie die geworfenen Punkte ein:
   - Verwenden Sie das Nummernfeld oder die Buttons
   - Bestätigen Sie mit "Submit Score"
4. Bei einem Bust (ungültiger Wurf) wird automatisch die Runde beendet
5. Das Spiel wechselt automatisch zum nächsten Spieler

#### Punkteeingabe rückgängig machen
- Klicken Sie auf "Undo" um den letzten Wurf zu korrigieren
- Dies ist nur für den letzten eingegebenen Wurf möglich

### 10.5 Funktionen im Detail

#### Spielhistorie
- **Zugriff:** Klicken Sie auf "View History" auf der Startseite
- **Funktionen:**
  - Übersicht aller gespielten Spiele
  - Detaillierte Punktehistorie
  - Spielerstatistiken
  - Spiele löschen (nur eigene)

#### Benutzeroberfläche
- **Responsive Design:** Optimiert für Desktop, Tablet und Smartphone
- **Automatischer Dark Mode:** Anpassung an Systempräferenz des Browsers
- **Intuitive Navigation:** Klare Menüstruktur und Breadcrumbs
- **Echtzeit-Updates:** Sofortige Anzeige von Punkteänderungen

#### Spielregeln und Validierung
- **Automatische Bust-Erkennung:** Bei ungültigen Würfen (z.B. Überschreitung)
- **Double Out Regel:** Automatische Prüfung bei entsprechender Konfiguration
- **Punktevalidierung:** Eingaben werden auf Gültigkeit geprüft (0-180 Punkte)
- **Spielende:** Automatische Erkennung wenn ein Spieler exakt 0 Punkte erreicht

### 10.6 Fehlerbehebung

#### Häufige Probleme

**Container starten nicht:**
```bash
# Container Status prüfen
docker-compose ps

# Logs anzeigen
docker-compose logs

# Container neu starten
docker-compose down
docker-compose up -d
```

**Datenbank-Verbindungsfehler:**
```bash
# PostgreSQL Container Status prüfen
docker-compose logs postgres

# Datenbank manuell migrieren
docker-compose exec app php -r "require 'src/base.php'; App\Core\Database::migrate();"
```

**Frontend-Assets fehlen:**
```bash
# Falls Assets fehlen, lokaler Build:
bun install
bun run build
```

### 10.7 Tipps zur Nutzung

#### Optimale Spielerfahrung
- **Spielernamen:** Verwenden Sie kurze, eindeutige Namen
- **Punkteeingabe:** Nutzen Sie die Tastatur für schnelle Eingabe
- **Mehrspielermodus:** Bis zu 4 Spieler werden optimal unterstützt, weitere Spieler können verschiebungen im UI hervorrufen.
- **Spielpausen:** Spiele werden automatisch gespeichert und können später fortgesetzt werden

#### Leistungsoptimierung
- **Browser-Cache leeren:** Bei Problemen mit der Anzeige
- **Aktive Spiele:** Beenden Sie nicht mehr benötigte Spiele
- **Datenbank-Wartung:** Regelmäßiges Löschen alter Spiele über die Historie

### 10.8 Sicherheitshinweise

- **Passwort-Sicherheit:** Verwenden Sie starke, einzigartige Passwörter
- **Daten-Backup:** Erstellen Sie regelmäßig Backups der PostgreSQL-Datenbank
- **Updates:** Halten Sie Docker Images aktuell mit `docker-compose pull`
- **Netzwerk-Sicherheit:** Verwenden Sie die Anwendung nur in vertrauenswürdigen Netzwerken

Die Bedienung der Anwendung ist weitgehend selbsterklärend. Fehlermeldungen und Hinweise bei fehlerhafter Eingabe werden direkt am jeweiligen Formular angezeigt. Sämtliche Inhalte sind barrierefrei gestaltet und für Tastaturbenutzer zugänglich. Die Navigationsstruktur ist konsistent und wurde auf eine einfache Bedienung hin optimiert.
