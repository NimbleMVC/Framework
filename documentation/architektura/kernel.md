# Kernel - Rdzeń aplikacji

## Przegląd

Klasa `Kernel` jest centralnym elementem frameworka NimblePHP, odpowiedzialnym za inicjalizację aplikacji, zarządzanie cyklem życia żądania oraz koordynację wszystkich komponentów systemu.

## Lokalizacja

```php
NimblePHP\Framework\Kernel
```

## Implementowane interfejsy

- `KernelInterface`

## Właściwości statyczne

### `$projectPath`
```php
public static string $projectPath;
```
Ścieżka do głównego katalogu projektu.

### `$middlewareManager`
```php
public static MiddlewareManager $middlewareManager;
```
Instancja menedżera middleware odpowiedzialnego za zarządzanie middleware'ami.

### `$serviceContainer`
```php
public static ServiceContainer $serviceContainer;
```
Kontener usług (Service Container) do zarządzania zależnościami.

## Właściwości chronione

### `$router`
```php
protected RouteInterface $router;
```
Instancja routera odpowiedzialnego za obsługę routingu.

### `$request`
```php
protected RequestInterface $request;
```
Instancja klasy Request reprezentującej żądanie HTTP.

### `$response`
```php
protected ResponseInterface $response;
```
Instancja klasy Response reprezentującej odpowiedź HTTP.

## Konstruktor

```php
public function __construct(
    RouteInterface $router, 
    ?RequestInterface $request = null, 
    ?ResponseInterface $response = null
)
```

### Parametry
- `$router` - Instancja routera
- `$request` - Opcjonalna instancja żądania (domyślnie tworzona automatycznie)
- `$response` - Opcjonalna instancja odpowiedzi (domyślnie tworzona automatycznie)

### Działanie
Konstruktor inicjalizuje podstawowe komponenty frameworka:
1. Ustawia ścieżkę projektu
2. Inicjalizuje router, żądanie i odpowiedź
3. Ładuje konfigurację
4. Inicjalizuje debugger
5. Uruchamia autoloader
6. Tworzy menedżer middleware
7. Tworzy kontener usług
8. Rejestruje usługi

## Metody publiczne

### `handle()`
```php
public function handle(): void
```

Główna metoda obsługująca żądanie HTTP. Wykonuje następujące kroki:
1. Ładuje konfigurację
2. Uruchamia bootstrap
3. Ładuje kontroler
4. Wysyła odpowiedź

### `loadConfiguration()`
```php
public function loadConfiguration(): void
```

Ładuje pliki konfiguracyjne aplikacji z katalogu `config/`.

### `bootstrap()`
```php
public function bootstrap(): void
```

Inicjalizuje podstawowe komponenty aplikacji:
1. Obsługuje błędy
2. Tworzy automatyczne katalogi
3. Inicjalizuje sesję
4. Łączy z bazą danych
5. Ładuje moduły

## Metody chronione

### `getProjectPath()`
```php
protected function getProjectPath(): string
```

Zwraca ścieżkę do głównego katalogu projektu.

### `registerServices()`
```php
protected function registerServices(): void
```

Rejestruje podstawowe usługi w kontenerze usług.

### `errorCatcher()`
```php
protected function errorCatcher(): void
```

Konfiguruje obsługę błędów i wyjątków.

### `autoCreator()`
```php
protected function autoCreator(): void
```

Tworzy automatycznie wymagane katalogi i pliki.

### `initializeSession()`
```php
protected function initializeSession(): void
```

Inicjalizuje sesję PHP.

### `connectToDatabase()`
```php
protected function connectToDatabase(): void
```

Nawiązuje połączenie z bazą danych na podstawie konfiguracji.

### `autoloader()`
```php
protected function autoloader(): void
```

Konfiguruje autoloader dla klas aplikacji.

### `loadController()`
```php
protected function loadController(): void
```

Ładuje i wykonuje odpowiedni kontroler na podstawie routingu.

### `handleException()`
```php
protected function handleException(Throwable $exception): void
```

Obsługuje wyjątki i błędy aplikacji.

### `debug()`
```php
protected function debug(): void
```

Wyświetla informacje debugowania (tylko w trybie deweloperskim).

### `loadModules()`
```php
protected function loadModules(): void
```

Ładuje zarejestrowane moduły aplikacji.

### `initializeDebugHandler()`
```php
private function initializeDebugHandler(): void
```

Inicjalizuje handler debugowania (Whoops).

## Przykład użycia

```php
<?php
require_once 'vendor/autoload.php';

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;

// Tworzenie instancji kernel
$kernel = new Kernel(new Route());

// Obsługa żądania
$kernel->handle();
```

## Cykl życia aplikacji

1. **Inicjalizacja** - Konstruktor tworzy podstawowe komponenty
2. **Konfiguracja** - Ładowanie plików konfiguracyjnych
3. **Bootstrap** - Inicjalizacja komponentów systemu
4. **Routing** - Określenie odpowiedniego kontrolera
5. **Wykonanie** - Uruchomienie akcji kontrolera
6. **Odpowiedź** - Wysłanie odpowiedzi do klienta

## Uwagi

- Kernel jest singletonem w kontekście aplikacji
- Wszystkie statyczne właściwości są dostępne globalnie
- Metoda `handle()` powinna być wywoływana tylko raz na żądanie
- W trybie deweloperskim automatycznie wyświetlane są błędy
- Framework automatycznie obsługuje podstawowe wyjątki