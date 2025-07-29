# Podsumowanie dokumentacji NimblePHP Framework

## Przegląd frameworka

NimblePHP to nowoczesny, lekki framework PHP zaprojektowany z myślą o prostocie, wydajności i łatwości użytkowania. Framework oferuje kompletne rozwiązanie do budowania aplikacji webowych z wbudowanym systemem routingu, obsługą baz danych, systemem logowania i wieloma innymi funkcjonalnościami.

## Główne komponenty

### 🏗️ Architektura
- **Kernel** - Centralny rdzeń aplikacji odpowiedzialny za inicjalizację i zarządzanie cyklem życia żądania
- **Kontenery usług** - System dependency injection do zarządzania zależnościami
- **Middleware** - System filtrowania i modyfikacji żądań
- **Routing** - Zaawansowany system routingu z obsługą różnych metod HTTP

### 🎮 Kontrolery
- **AbstractController** - Abstrakcyjna klasa bazowa dla wszystkich kontrolerów
- **ControllerInterface** - Kontrakt definiujący wymagane metody kontrolerów
- **Akcje i metody** - System automatycznego mapowania URL na metody kontrolerów
- **Żądania i odpowiedzi** - Klasy Request i Response do obsługi HTTP

### 📊 Modele
- **AbstractModel** - Abstrakcyjna klasa bazowa z pełną funkcjonalnością ORM
- **ModelInterface** - Kontrakt definiujący wymagane metody modeli
- **Operacje CRUD** - Kompletne operacje Create, Read, Update, Delete
- **Relacje** - System relacji między tabelami

### 🗄️ Baza danych
- **ORM** - Object-Relational Mapping z automatycznym mapowaniem tabel
- **Zapytania** - Bezpieczne zapytania z prepared statements
- **Transakcje** - Obsługa transakcji bazodanowych
- **Migracje** - System migracji schematu bazy danych

### 🔧 Narzędzia
- **System logowania** - Zaawansowany system logowania z różnymi poziomami
- **Zarządzanie plikami** - Klasa Storage do bezpiecznego zarządzania plikami
- **Sesje i ciasteczka** - System zarządzania sesjami i ciasteczkami
- **Cache** - System cache z różnymi backendami

### 🔌 Interfejsy
- **ControllerInterface** - Kontrakt dla kontrolerów
- **ModelInterface** - Kontrakt dla modeli
- **RequestInterface** - Kontrakt dla żądań HTTP
- **ResponseInterface** - Kontrakt dla odpowiedzi HTTP

## Kluczowe funkcjonalności

### 1. Automatyczne ładowanie klas
Framework automatycznie ładuje klasy na podstawie konwencji nazewnictwa i struktury katalogów.

### 2. System routingu
Zaawansowany system routingu z obsługą:
- Parametrów URL
- Różnych metod HTTP
- Middleware dla tras
- Automatycznego mapowania kontrolerów

### 3. ORM (Object-Relational Mapping)
Kompletny system ORM z:
- Automatycznym mapowaniem tabel
- Operacjami CRUD
- Relacjami między tabelami
- Query builder
- Migracjami

### 4. System logowania
Zaawansowany system logowania z:
- Różnymi poziomami logowania (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Automatyczną rotacją plików
- Integracją z middleware
- Strukturalnymi logami w formacie JSON

### 5. Zarządzanie plikami
Bezpieczny system zarządzania plikami z:
- Walidacją ścieżek
- Automatycznym tworzeniem katalogów
- Operacjami na plikach (zapisywanie, odczytywanie, usuwanie)
- Metadanymi plików

### 6. Middleware
System middleware do:
- Filtrowania żądań
- Modyfikacji odpowiedzi
- Autoryzacji i autentykacji
- Logowania i monitorowania

## Zalety frameworka

### 1. Prostość
- Minimalistyczna architektura
- Jasne konwencje nazewnictwa
- Łatwa krzywa uczenia

### 2. Wydajność
- Lekka waga frameworka
- Optymalizowane zapytania do bazy danych
- Efektywne zarządzanie pamięcią

### 3. Elastyczność
- Możliwość rozszerzania funkcjonalności
- Własne middleware
- Niestandardowe komponenty

### 4. Bezpieczeństwo
- Automatyczna walidacja danych wejściowych
- Zabezpieczenia przed SQL injection
- Walidacja ścieżek plików
- Obsługa CSRF

### 5. Dokumentacja
- Kompletna dokumentacja w języku polskim
- Przykłady użycia
- Najlepsze praktyki

## Struktura projektu

```
project/
├── src/                    # Kod źródłowy frameworka
│   ├── Abstracts/         # Klasy abstrakcyjne
│   ├── Attributes/        # Atrybuty PHP 8
│   ├── CLI/              # Narzędzia wiersza poleceń
│   ├── Container/        # Kontenery usług
│   ├── Exception/        # Wyjątki
│   ├── Interfaces/       # Interfejsy
│   ├── Libs/            # Biblioteki pomocnicze
│   ├── Middleware/      # Middleware
│   ├── Routes/          # Routing
│   ├── Traits/          # Traity
│   └── ...              # Inne komponenty
├── storage/              # Pliki aplikacji
│   ├── logs/            # Logi
│   ├── cache/           # Cache
│   ├── uploads/         # Przesłane pliki
│   └── ...              # Inne katalogi
├── tests/               # Testy
├── documentation/       # Dokumentacja
└── ...                 # Inne pliki
```

## Szybki start

### 1. Instalacja
```bash
composer require nimblephp/framework
```

### 2. Podstawowa konfiguracja
```php
<?php
// index.php
require_once 'vendor/autoload.php';

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;

$kernel = new Kernel(new Route());
$kernel->handle();
```

### 3. Pierwszy kontroler
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;

class HomeController extends AbstractController
{
    public function index(): void
    {
        echo "Witaj w NimblePHP!";
    }
}
```

### 4. Pierwszy model
```php
<?php
namespace App\Models;

use NimblePHP\Framework\Abstracts\AbstractModel;

class UserModel extends AbstractModel
{
    public string $useTable = 'users';
    
    public function getActiveUsers(): array
    {
        return $this->readAll(['active' => 1]);
    }
}
```

## Najlepsze praktyki

### 1. Struktura katalogów
- Używaj konwencji nazewnictwa
- Organizuj kod w logiczne katalogi
- Separuj logikę biznesową od prezentacji

### 2. Kontrolery
- Trzymaj kontrolery lekkimi
- Używaj modeli do logiki biznesowej
- Waliduj dane wejściowe
- Loguj ważne operacje

### 3. Modele
- Używaj relacji między modelami
- Implementuj własne metody dla specyficznej logiki
- Używaj transakcji dla operacji wielotabelowych

### 4. Bezpieczeństwo
- Waliduj wszystkie dane wejściowe
- Używaj prepared statements
- Implementuj autoryzację
- Loguj podejrzane aktywności

### 5. Wydajność
- Używaj cache dla często używanych danych
- Optymalizuj zapytania do bazy danych
- Używaj lazy loading dla relacji
- Monitoruj wydajność aplikacji

## Rozszerzenia i moduły

Framework obsługuje rozszerzenia poprzez:
- System modułów
- Własne middleware
- Niestandardowe komponenty
- Integrację z zewnętrznymi bibliotekami

## Wsparcie i społeczność

- **Dokumentacja**: Kompletna dokumentacja w języku polskim
- **Przykłady**: Liczne przykłady użycia
- **GitHub**: Kod źródłowy i issue tracker
- **Społeczność**: Forum i grupy dyskusyjne

## Podsumowanie

NimblePHP to nowoczesny, lekki i elastyczny framework PHP, który oferuje:

- **Prostotę** - Łatwa krzywa uczenia i jasne konwencje
- **Wydajność** - Optymalizowane komponenty i minimalne zużycie zasobów
- **Bezpieczeństwo** - Wbudowane zabezpieczenia i walidacja
- **Elastyczność** - Możliwość rozszerzania i dostosowywania
- **Dokumentację** - Kompletna dokumentacja w języku polskim

Framework jest idealny dla:
- Małych i średnich projektów
- Prototypowania aplikacji
- Nauki programowania PHP
- Projektów wymagających pełnej kontroli nad kodem

NimblePHP łączy w sobie prostotę z potężnymi funkcjonalnościami, oferując developerom narzędzie do szybkiego i efektywnego tworzenia aplikacji webowych.