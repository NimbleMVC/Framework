# Żądania i odpowiedzi HTTP

## Klasa Request

### Przegląd

Klasa `Request` reprezentuje żądanie HTTP i dostarcza metody do bezpiecznego dostępu do danych żądania (GET, POST, cookies, files, headers).

### Lokalizacja

```php
NimblePHP\Framework\Request
```

### Implementowane interfejsy

- `RequestInterface`

### Właściwości prywatne

```php
private array $query;    // Dane GET
private array $post;     // Dane POST
private array $cookies;  // Ciasteczka
private array $files;    // Przesłane pliki
private array $headers;  // Nagłówki HTTP
private array $server;   // Dane serwera
```

## Metody klasy Request

### `getAllQuery()`
```php
public function getAllQuery(bool $protect = true): array
```
Pobiera wszystkie parametry GET.

#### Parametry
- `$protect` - Czy zastosować htmlspecialchars (domyślnie `true`)

#### Zwraca
- `array` - Wszystkie parametry GET

### `getQuery()`
```php
public function getQuery(string $key, mixed $default = null, bool $protect = true): mixed
```
Pobiera konkretny parametr GET.

#### Parametry
- `$key` - Klucz parametru
- `$default` - Wartość domyślna
- `$protect` - Czy zastosować htmlspecialchars

#### Zwraca
- `mixed` - Wartość parametru lub wartość domyślna

#### Przykład użycia
```php
$id = $this->request->getQuery('id', 0);
$name = $this->request->getQuery('name', '', true);
```

### `issetQuery()`
```php
public function issetQuery(string $key): bool
```
Sprawdza czy parametr GET istnieje.

#### Parametry
- `$key` - Klucz parametru

#### Zwraca
- `bool` - `true` jeśli parametr istnieje

### `getAllPost()`
```php
public function getAllPost(bool $protect = true): array
```
Pobiera wszystkie dane POST.

#### Parametry
- `$protect` - Czy zastosować htmlspecialchars

#### Zwraca
- `array` - Wszystkie dane POST

### `getPost()`
```php
public function getPost(string $key, mixed $default = null, bool $protect = true): mixed
```
Pobiera konkretną wartość POST.

#### Parametry
- `$key` - Klucz danych
- `$default` - Wartość domyślna
- `$protect` - Czy zastosować htmlspecialchars

#### Zwraca
- `mixed` - Wartość danych lub wartość domyślna

### `issetPost()`
```php
public function issetPost(string $key): bool
```
Sprawdza czy dane POST istnieją.

#### Parametry
- `$key` - Klucz danych

#### Zwraca
- `bool` - `true` jeśli dane istnieją

### `getCookie()`
```php
public function getCookie(string $key, mixed $default = null, bool $protect = true): mixed
```
Pobiera wartość ciasteczka.

#### Parametry
- `$key` - Nazwa ciasteczka
- `$default` - Wartość domyślna
- `$protect` - Czy zastosować htmlspecialchars

#### Zwraca
- `mixed` - Wartość ciasteczka lub wartość domyślna

### `issetCookie()`
```php
public function issetCookie(string $key): bool
```
Sprawdza czy ciasteczko istnieje.

#### Parametry
- `$key` - Nazwa ciasteczka

#### Zwraca
- `bool` - `true` jeśli ciasteczko istnieje

### `getFile()`
```php
public function getFile(string $key): mixed
```
Pobiera informacje o przesłanym pliku.

#### Parametry
- `$key` - Nazwa pola pliku

#### Zwraca
- `mixed` - Dane pliku lub `null`

### `getHeader()`
```php
public function getHeader(string $key): mixed
```
Pobiera wartość nagłówka HTTP.

#### Parametry
- `$key` - Nazwa nagłówka

#### Zwraca
- `mixed` - Wartość nagłówka lub `null`

### `getMethod()`
```php
public function getMethod(): string
```
Zwraca metodę HTTP żądania.

#### Zwraca
- `string` - Metoda HTTP (GET, POST, PUT, DELETE, etc.)

### `getUri()`
```php
public function getUri(): string
```
Zwraca URI żądania.

#### Zwraca
- `string` - URI żądania

### `getServer()`
```php
public function getServer(string $key, mixed $default = null): mixed
```
Pobiera wartość z tablicy `$_SERVER`.

#### Parametry
- `$key` - Klucz z tablicy `$_SERVER`
- `$default` - Wartość domyślna

#### Zwraca
- `mixed` - Wartość z `$_SERVER` lub wartość domyślna

### `getBody()`
```php
public function getBody(): string
```
Pobiera surowe dane body żądania.

#### Zwraca
- `string` - Dane body żądania

### `isAjax()`
```php
public function isAjax(): bool
```
Sprawdza czy żądanie jest typu AJAX.

#### Zwraca
- `bool` - `true` jeśli żądanie jest AJAX

### `validateInput()`
```php
public function validateInput(string $key, string $type = 'string', array $options = []): mixed
```
Waliduje i filtruje dane wejściowe.

#### Parametry
- `$key` - Klucz danych do walidacji
- `$type` - Typ walidacji (string, int, email, url, etc.)
- `$options` - Dodatkowe opcje walidacji

#### Zwraca
- `mixed` - Zwalidowane dane lub `null`

## Klasa Response

### Przegląd

Klasa `Response` reprezentuje odpowiedź HTTP i dostarcza metody do tworzenia i wysyłania odpowiedzi do klienta.

### Lokalizacja

```php
NimblePHP\Framework\Response
```

### Implementowane interfejsy

- `ResponseInterface`

### Właściwości chronione

```php
protected mixed $content;      // Treść odpowiedzi
protected int $statusCode;     // Kod statusu HTTP
protected array $headers;      // Nagłówki odpowiedzi
protected string $statusText;  // Tekst statusu
protected RequestInterface $request; // Instancja żądania
```

## Metody klasy Response

### `getContent()`
```php
public function getContent(): string
```
Pobiera treść odpowiedzi.

#### Zwraca
- `string` - Treść odpowiedzi

### `setContent()`
```php
public function setContent($content): void
```
Ustawia treść odpowiedzi.

#### Parametry
- `$content` - Treść odpowiedzi

### `setJsonContent()`
```php
public function setJsonContent(array $content = [], bool $addHeader = true): void
```
Ustawia treść odpowiedzi jako JSON.

#### Parametry
- `$content` - Dane do zakodowania jako JSON
- `$addHeader` - Czy dodać nagłówek Content-Type

#### Rzuca
- `NimbleException` - Gdy kodowanie JSON się nie powiedzie

#### Przykład użycia
```php
$response->setJsonContent(['status' => 'success', 'data' => $users]);
```

### `getStatusCode()`
```php
public function getStatusCode(): int
```
Pobiera kod statusu HTTP.

#### Zwraca
- `int` - Kod statusu HTTP

### `setStatusCode()`
```php
public function setStatusCode(int $code, string $text = ''): void
```
Ustawia kod statusu HTTP.

#### Parametry
- `$code` - Kod statusu HTTP
- `$text` - Tekst statusu (opcjonalny)

#### Przykład użycia
```php
$response->setStatusCode(404, 'Not Found');
$response->setStatusCode(201); // Tekst zostanie ustawiony automatycznie
```

### `addHeader()`
```php
public function addHeader(string $name, string $value): void
```
Dodaje nagłówek HTTP.

#### Parametry
- `$name` - Nazwa nagłówka
- `$value` - Wartość nagłówka

#### Przykład użycia
```php
$response->addHeader('Cache-Control', 'no-cache');
$response->addHeader('X-Custom-Header', 'custom-value');
```

### `send()`
```php
public function send(bool $die = false): void
```
Wysyła odpowiedź do klienta.

#### Parametry
- `$die` - Czy zakończyć wykonywanie skryptu po wysłaniu

#### Działanie
1. Ustawia kod statusu HTTP
2. Wysyła wszystkie nagłówki
3. Wysyła treść odpowiedzi
4. Opcjonalnie kończy wykonywanie skryptu

### `redirect()`
```php
public function redirect(string $url, int $statusCode = 302): never
```
Przekierowuje użytkownika na podany URL.

#### Parametry
- `$url` - URL do przekierowania
- `$statusCode` - Kod statusu przekierowania (domyślnie 302)

#### Rzuca
- `never` - Zawsze kończy wykonywanie skryptu

#### Przykład użycia
```php
$response->redirect('/dashboard');
$response->redirect('/login', 301);
```

## Przykłady użycia w kontrolerach

### Przykład 1: Obsługa formularza
```php
public function create(): void
{
    // Pobieranie danych z formularza
    $name = $this->request->getPost('name', '', true);
    $email = $this->request->getPost('email', '', true);
    
    // Walidacja
    if (empty($name) || empty($email)) {
        $this->response->setStatusCode(400);
        $this->response->setJsonContent([
            'error' => 'Nazwa i email są wymagane'
        ]);
        $this->response->send();
        return;
    }
    
    // Przetwarzanie danych
    $userModel = $this->loadModel('App\Models\UserModel');
    $success = $userModel->create(['name' => $name, 'email' => $email]);
    
    if ($success) {
        $this->response->setStatusCode(201);
        $this->response->setJsonContent(['status' => 'success']);
    } else {
        $this->response->setStatusCode(500);
        $this->response->setJsonContent(['error' => 'Błąd podczas tworzenia użytkownika']);
    }
    
    $this->response->send();
}
```

### Przykład 2: Pobieranie danych z parametrów URL
```php
public function show(): void
{
    $id = $this->request->getQuery('id', 0, false);
    
    if (!$id || !is_numeric($id)) {
        $this->response->setStatusCode(400);
        $this->response->setJsonContent(['error' => 'Nieprawidłowe ID']);
        $this->response->send();
        return;
    }
    
    $userModel = $this->loadModel('App\Models\UserModel');
    $user = $userModel->read(['id' => $id]);
    
    if (empty($user)) {
        $this->response->setStatusCode(404);
        $this->response->setJsonContent(['error' => 'Użytkownik nie został znaleziony']);
    } else {
        $this->response->setJsonContent(['user' => $user]);
    }
    
    $this->response->send();
}
```

### Przykład 3: Obsługa plików
```php
public function upload(): void
{
    $file = $this->request->getFile('document');
    
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $this->response->setStatusCode(400);
        $this->response->setJsonContent(['error' => 'Błąd podczas przesyłania pliku']);
        $this->response->send();
        return;
    }
    
    // Sprawdzenie typu pliku
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        $this->response->setStatusCode(400);
        $this->response->setJsonContent(['error' => 'Nieprawidłowy typ pliku']);
        $this->response->send();
        return;
    }
    
    // Przetwarzanie pliku
    $uploadPath = 'uploads/' . uniqid() . '_' . $file['name'];
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $this->response->setJsonContent(['status' => 'success', 'path' => $uploadPath]);
    } else {
        $this->response->setStatusCode(500);
        $this->response->setJsonContent(['error' => 'Błąd podczas zapisywania pliku']);
    }
    
    $this->response->send();
}
```

## Uwagi bezpieczeństwa

- Zawsze używaj parametru `$protect = true` dla danych użytkownika
- Waliduj wszystkie dane wejściowe przed przetwarzaniem
- Używaj `htmlspecialchars` dla danych wyświetlanych w HTML
- Sprawdzaj typy plików przed zapisaniem
- Używaj odpowiednich kodów statusu HTTP
- Nie ufaj danym z `$_GET` i `$_POST` bez walidacji