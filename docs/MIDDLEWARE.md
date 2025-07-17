# Middleware System

Framework posiada nowoczesny system middleware z obsługą łańcucha middleware, middleware dla konkretnych tras i globalnych middleware, a także middleware dla różnych komponentów frameworka.

## Podstawowe koncepcje

### MiddlewareInterface (HTTP)
Wszystkie HTTP middleware muszą implementować interfejs `MiddlewareInterface`:

```php
interface MiddlewareInterface
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
    public function afterBootstrap(): void;
    public function beforeController(string &$controllerName, string &$action, array &$params): void;
    public function afterController(string $controllerName, string $action, array $params): void;
    public function handleException(Throwable $exception): void;
    public function log(array &$logContent): void;
    public function afterLog(array $logContent): void;
}
```

### MiddlewareManager
Klasa zarządzająca łańcuchem HTTP middleware:

```php
$manager = new MiddlewareManager();

// Dodawanie globalnych middleware
$manager->addGlobal(new CorsMiddleware());

// Dodawanie middleware dla konkretnych tras
$manager->addRoute('/admin', new AuthMiddleware());

// Uruchamianie łańcucha middleware
$response = $manager->run($request, $finalHandler);
```

## Typy Middleware

### 1. HTTP Middleware
Middleware obsługujące requesty HTTP.

### 2. Model Middleware
Middleware dla operacji na bazie danych w modelach.

**Interfejs:** `ModelMiddlewareInterface`

```php
interface ModelMiddlewareInterface
{
    public function beforeSave(array &$data): void;
    public function afterSave(array $data, bool $result): void;
    public function beforeFind(array &$conditions): void;
    public function afterFind(array $conditions, array $result): void;
    public function beforeDelete(array &$conditions): void;
    public function afterDelete(array $conditions, bool $result): void;
    public function beforeUpdate(array &$data, array &$conditions): void;
    public function afterUpdate(array $data, array $conditions, bool $result): void;
    public function beforeInsert(array &$data): void;
    public function afterInsert(array $data, bool $result): void;
}
```

### 3. View Middleware
Middleware dla przetwarzania widoków.

**Interfejs:** `ViewMiddlewareInterface`

```php
interface ViewMiddlewareInterface
{
    public function beforeRender(string &$template, array &$data): void;
    public function afterRender(string $template, array $data, string &$output): void;
    public function beforeAssign(string $key, &$value): void;
    public function afterAssign(string $key, $value): void;
    public function beforeInclude(string &$file, array &$data): void;
    public function afterInclude(string $file, array $data, string &$output): void;
}
```

### 4. Response Middleware
Middleware dla przetwarzania odpowiedzi.

**Interfejs:** `ResponseMiddlewareInterface`

```php
interface ResponseMiddlewareInterface
{
    public function beforeSend(ResponseInterface &$response): void;
    public function afterSend(ResponseInterface $response): void;
    public function beforeSetContent(ResponseInterface &$response, &$content): void;
    public function afterSetContent(ResponseInterface $response, $content): void;
    public function beforeSetHeader(ResponseInterface &$response, string $key, &$value): void;
    public function afterSetHeader(ResponseInterface $response, string $key, $value): void;
    public function beforeSetStatusCode(ResponseInterface &$response, &$statusCode): void;
    public function afterSetStatusCode(ResponseInterface $response, $statusCode): void;
}
```

### 5. Session Middleware
Middleware dla obsługi sesji.

**Interfejs:** `SessionMiddlewareInterface`

```php
interface SessionMiddlewareInterface
{
    public function beforeSet(string $key, &$value): void;
    public function afterSet(string $key, $value): void;
    public function beforeGet(string $key, &$value): void;
    public function afterGet(string $key, $value): void;
    public function beforeDestroy(): void;
    public function afterDestroy(): void;
    public function beforeRegenerate(): void;
    public function afterRegenerate(): void;
}
```

## Konfiguracja HTTP Middleware

Middleware można konfigurować programowo w kodzie aplikacji:

```php
// W pliku bootstrap.php lub podobnym
$manager = new MiddlewareManager();

// Dodawanie globalnych middleware
$manager->addGlobal(new \App\Middleware\LoggingMiddleware());
$manager->addGlobal(new \App\Middleware\CorsMiddleware());

// Dodawanie middleware dla konkretnych tras
$manager->addRoute('/admin', new \App\Middleware\AuthMiddleware());
$manager->addRoute('/api', new \App\Middleware\CorsMiddleware());

// Ustawienie managera w Kernel
Kernel::$middlewareManager = $manager;
```

## Dostępne HTTP Middleware

Framework nie zawiera predefiniowanych HTTP middleware. Możesz tworzyć własne middleware implementując interfejs `MiddlewareInterface`.

## Dostępne Model Middleware

Framework nie zawiera predefiniowanych model middleware. Możesz tworzyć własne middleware implementując interfejs `ModelMiddlewareInterface`.

## Dostępne View Middleware

Framework nie zawiera predefiniowanych view middleware. Możesz tworzyć własne middleware implementując interfejs `ViewMiddlewareInterface`.

## Dostępne Response Middleware

Framework nie zawiera predefiniowanych response middleware. Możesz tworzyć własne middleware implementując interfejs `ResponseMiddlewareInterface`.

## Dostępne Session Middleware

Framework nie zawiera predefiniowanych session middleware. Możesz tworzyć własne middleware implementując interfejs `SessionMiddlewareInterface`.

## Integracja z komponentami

### Model z middleware

```php
class User extends AbstractModel
{
    protected array $middleware = [
        \App\ModelMiddleware\CustomTimestampMiddleware::class,
        \App\ModelMiddleware\SoftDeleteMiddleware::class
    ];
    
    public function save(array $data): bool
    {
        // Middleware beforeSave() automatycznie dodaje timestamps
        return parent::save($data);
    }
}
```

### View z middleware

```php
class View
{
    protected array $middleware = [
        \App\ViewMiddleware\DataSanitizationMiddleware::class
    ];
    
    public function render(string $template, array $data): void
    {
        // Middleware beforeRender() automatycznie sanityzuje dane
        $this->processTemplate($template, $data);
    }
}
```

### Response z middleware

```php
class Response
{
    protected array $middleware = [
        \App\ResponseMiddleware\CompressionMiddleware::class
    ];
    
    public function send(): void
    {
        // Middleware beforeSend() automatycznie kompresuje odpowiedź
        $this->output();
    }
}
```

### Session z middleware

```php
class Session
{
    protected array $middleware = [
        \App\SessionMiddleware\SecurityMiddleware::class
    ];
    
    public function set(string $key, mixed $value): self
    {
        // Middleware beforeSet() automatycznie regeneruje ID przy logowaniu
        $_SESSION[$key] = $value;
        return $this;
    }
}
```

## Tworzenie własnych Middleware

### HTTP Middleware

```php
<?php

namespace App\Middleware;

use NimblePHP\Framework\Interfaces\MiddlewareInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;

class CustomHttpMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Kod wykonywany przed requestem
        $startTime = microtime(true);
        
        $response = $next($request);
        
        // Kod wykonywany po requeście
        $duration = microtime(true) - $startTime;
        
        // Dodanie nagłówka z czasem wykonania
        $response->setHeader('X-Execution-Time', $duration);
        
        return $response;
    }
    
    public function afterBootstrap(): void
    {
        // Kod wykonywany po bootstrap frameworka
    }
    
    public function beforeController(string &$controllerName, string &$action, array &$params): void
    {
        // Kod wykonywany przed kontrolerem
        // Można modyfikować nazwę kontrolera, akcję i parametry
    }
    
    public function afterController(string $controllerName, string $action, array $params): void
    {
        // Kod wykonywany po kontrolerze
    }
    
    public function handleException(\Throwable $exception): void
    {
        // Kod wykonywany przy wyjątkach
    }
    
    public function log(array &$logContent): void
    {
        // Modyfikacja zawartości logów
    }
    
    public function afterLog(array $logContent): void
    {
        // Kod wykonywany po zapisie logów
    }
}
```

### Model Middleware

```php
<?php

namespace App\ModelMiddleware;

use NimblePHP\Framework\Interfaces\ModelMiddlewareInterface;

class CustomModelMiddleware implements ModelMiddlewareInterface
{
    public function beforeSave(array &$data): void
    {
        // Automatyczne dodawanie pola 'modified_by'
        if (isset($_SESSION['user_id'])) {
            $data['modified_by'] = $_SESSION['user_id'];
        }
    }
    
    public function afterSave(array $data, bool $result): void
    {
        if ($result) {
            // Logowanie udanego zapisu
            Log::log('Record saved successfully', 'INFO', $data);
        }
    }
    
    public function beforeFind(array &$conditions): void
    {
        // Automatyczne filtrowanie po organizacji użytkownika
        if (isset($_SESSION['organization_id'])) {
            $conditions['organization_id'] = $_SESSION['organization_id'];
        }
    }
    
    public function afterFind(array $conditions, array $result): void
    {
        // Przetwarzanie wyników wyszukiwania
    }
    
    public function beforeDelete(array &$conditions): void
    {
        // Sprawdzanie uprawnień do usuwania
    }
    
    public function afterDelete(array $conditions, bool $result): void
    {
        // Logowanie usunięcia
    }
    
    public function beforeUpdate(array &$data, array &$conditions): void
    {
        // Walidacja danych przed aktualizacją
    }
    
    public function afterUpdate(array $data, array $conditions, bool $result): void
    {
        // Aktualizacja cache po zmianie
    }
    
    public function beforeInsert(array &$data): void
    {
        // Automatyczne dodawanie pola 'created_by'
        if (isset($_SESSION['user_id'])) {
            $data['created_by'] = $_SESSION['user_id'];
        }
    }
    
    public function afterInsert(array $data, bool $result): void
    {
        // Powiadomienie o nowym rekordzie
    }
}
```

### View Middleware

```php
<?php

namespace App\ViewMiddleware;

use NimblePHP\Framework\Interfaces\ViewMiddlewareInterface;

class CustomViewMiddleware implements ViewMiddlewareInterface
{
    public function beforeRender(string &$template, array &$data): void
    {
        // Dodanie globalnych danych do wszystkich widoków
        $data['current_user'] = $_SESSION['user'] ?? null;
        $data['app_name'] = 'My Application';
    }
    
    public function afterRender(string $template, array $data, string &$output): void
    {
        // Minifikacja HTML
        $output = preg_replace('/\s+/', ' ', $output);
    }
    
    public function beforeAssign(string $key, &$value): void
    {
        // Walidacja danych przed przypisaniem
    }
    
    public function afterAssign(string $key, $value): void
    {
        // Logowanie przypisanych danych
    }
    
    public function beforeInclude(string &$file, array &$data): void
    {
        // Sprawdzanie uprawnień do pliku
    }
    
    public function afterInclude(string $file, array $data, string &$output): void
    {
        // Przetwarzanie zawartości includowanego pliku
    }
}
```

### Response Middleware

```php
<?php

namespace App\ResponseMiddleware;

use NimblePHP\Framework\Interfaces\ResponseMiddlewareInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;

class CustomResponseMiddleware implements ResponseMiddlewareInterface
{
    public function beforeSend(ResponseInterface &$response): void
    {
        // Dodanie nagłówków bezpieczeństwa
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
    }
    
    public function afterSend(ResponseInterface $response): void
    {
        // Logowanie wysłanych odpowiedzi
    }
    
    public function beforeSetContent(ResponseInterface &$response, &$content): void
    {
        // Przetwarzanie zawartości przed ustawieniem
    }
    
    public function afterSetContent(ResponseInterface $response, $content): void
    {
        // Walidacja zawartości po ustawieniu
    }
    
    public function beforeSetHeader(ResponseInterface &$response, string $key, &$value): void
    {
        // Walidacja nagłówków
    }
    
    public function afterSetHeader(ResponseInterface $response, string $key, $value): void
    {
        // Logowanie ustawionych nagłówków
    }
    
    public function beforeSetStatusCode(ResponseInterface &$response, &$statusCode): void
    {
        // Sprawdzanie poprawności kodu statusu
    }
    
    public function afterSetStatusCode(ResponseInterface $response, $statusCode): void
    {
        // Logowanie kodów błędów
    }
}
```

### Session Middleware

```php
<?php

namespace App\SessionMiddleware;

use NimblePHP\Framework\Interfaces\SessionMiddlewareInterface;

class CustomSessionMiddleware implements SessionMiddlewareInterface
{
    public function beforeSet(string $key, &$value): void
    {
        // Szyfrowanie wrażliwych danych
        if (in_array($key, ['password', 'token'])) {
            $value = $this->encrypt($value);
        }
    }
    
    public function afterSet(string $key, $value): void
    {
        // Logowanie ustawionych wartości
    }
    
    public function beforeGet(string $key, &$value): void
    {
        // Deszyfrowanie wrażliwych danych
        if (in_array($key, ['password', 'token'])) {
            $value = $this->decrypt($value);
        }
    }
    
    public function afterGet(string $key, $value): void
    {
        // Walidacja pobranych danych
    }
    
    public function beforeDestroy(): void
    {
        // Czyszczenie powiązanych danych
    }
    
    public function afterDestroy(): void
    {
        // Logowanie zniszczenia sesji
    }
    
    public function beforeRegenerate(): void
    {
        // Backup starej sesji
    }
    
    public function afterRegenerate(): void
    {
        // Migracja danych do nowej sesji
    }
    
    private function encrypt($value): string
    {
        return base64_encode($value);
    }
    
    private function decrypt($value): string
    {
        return base64_decode($value);
    }
}
```

## Łańcuch HTTP Middleware

Middleware są wykonywane w kolejności:

1. Globalne middleware
2. Middleware dla konkretnych tras
3. Ogólne middleware
4. Kontroler
5. Middleware w odwrotnej kolejności

```
Request → Global → Route → General → Controller → General → Route → Global → Response
```

## Przerwanie łańcucha HTTP

Middleware może przerwać łańcuch zwracając Response:

```php
public function handle(RequestInterface $request, callable $next): ResponseInterface
{
    if (!$this->isAuthorized($request)) {
        return new Response('Unauthorized', 401);
    }
    
    return $next($request);
}
```

## Integracja z Kernel

Framework automatycznie ładuje i uruchamia HTTP middleware w metodzie `handle()`:

```php
public function handle(): void
{
    try {
        $this->bootstrap();
        
        if (isset(self::$middlewareManager)) {
            $response = self::$middlewareManager->run($this->request, function (RequestInterface $request) {
                $this->loadController();
                return $this->response;
            });
            
            if ($response instanceof ResponseInterface) {
                $this->response = $response;
            }
        } else {
            $this->loadController();
        }
    } catch (Throwable $e) {
        $this->handleException($e);
    }
}
```

## Automatyczne uruchamianie

Wszystkie typy middleware są automatycznie uruchamiane w odpowiednich momentach:

- **Model middleware** - przy operacjach na bazie danych
- **View middleware** - przy renderowaniu widoków
- **Response middleware** - przy wysyłaniu odpowiedzi
- **Session middleware** - przy operacjach na sesji

Nie wymagają dodatkowej konfiguracji - wystarczy dodać je do tablicy `$middleware` w odpowiedniej klasie. 