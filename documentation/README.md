# Dokumentacja Frameworka NimblePHP

## Przegląd

NimblePHP to nowoczesny framework PHP zaprojektowany z myślą o prostocie, wydajności i łatwości użytkowania. Framework oferuje kompletne rozwiązanie do budowania aplikacji webowych z wbudowanym systemem routingu, obsługą baz danych, systemem logowania i wieloma innymi funkcjonalnościami.

## Struktura dokumentacji

### 📚 Podstawy
- [Instalacja i konfiguracja](./podstawy/instalacja.md)
- [Struktura projektu](./podstawy/struktura-projektu.md)
- [Konfiguracja środowiska](./podstawy/konfiguracja.md)

### 🏗️ Architektura
- [Kernel - Rdzeń aplikacji](./architektura/kernel.md)
- [Kontenery usług](./architektura/kontenery-uslug.md)
- [Middleware](./architektura/middleware.md)
- [Routing](./architektura/routing.md)

### 🎮 Kontrolery
- [Podstawy kontrolerów](./kontrolery/podstawy.md)
- [AbstractController](./kontrolery/abstract-controller.md)
- [Akcje i metody](./kontrolery/akcje.md)
- [Żądania i odpowiedzi](./kontrolery/zadania-odpowiedzi.md)

### 📊 Modele
- [Podstawy modeli](./modele/podstawy.md)
- [AbstractModel](./modele/abstract-model.md)
- [Operacje na bazie danych](./modele/operacje-bd.md)
- [Relacje między modelami](./modele/relacje.md)

### 🗄️ Baza danych
- [Konfiguracja bazy danych](./baza-danych/konfiguracja.md)
- [ORM](./baza-danych/orm.md)
- [Zapytania](./baza-danych/zapytania.md)
- [Migracje](./baza-danych/migracje.md)

### 🔧 Narzędzia
- [System logowania](./narzedzia/logowanie.md)
- [Zarządzanie plikami](./narzedzia/zarzadzanie-plikami.md)
- [Sesje i ciasteczka](./narzedzia/sesje-ciasteczka.md)
- [Cache](./narzedzia/cache.md)

### 🔌 Interfejsy
- [ControllerInterface](./interfejsy/controller-interface.md)
- [ModelInterface](./interfejsy/model-interface.md)
- [RequestInterface](./interfejsy/request-interface.md)
- [ResponseInterface](./interfejsy/response-interface.md)

### 🎨 Widoki
- [System szablonów](./widoki/system-szablonow.md)
- [Renderowanie](./widoki/renderowanie.md)
- [Komponenty](./widoki/komponenty.md)

### 🚀 Zaawansowane funkcje
- [CLI](./zaawansowane/cli.md)
- [Cron](./zaawansowane/cron.md)
- [Atrybuty](./zaawansowane/atrybuty.md)
- [Własne middleware](./zaawansowane/wlasne-middleware.md)

## Szybki start

### Instalacja

```bash
composer require nimblephp/framework
```

### Podstawowa konfiguracja

```php
<?php
// index.php
require_once 'vendor/autoload.php';

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;

$kernel = new Kernel(new Route());
$kernel->handle();
```

### Przykład kontrolera

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

## Wsparcie

- **GitHub**: [https://github.com/nimblephp/framework](https://github.com/nimblephp/framework)
- **Dokumentacja API**: [API Reference](./api-reference.md)
- **Przykłady**: [Przykłady użycia](./przyklady.md)

## Licencja

Ten projekt jest licencjonowany na podstawie licencji MIT - zobacz plik [LICENSE](../LICENSE) w głównym katalogu projektu.

---

*Dokumentacja jest stale aktualizowana. Ostatnia aktualizacja: 2024*