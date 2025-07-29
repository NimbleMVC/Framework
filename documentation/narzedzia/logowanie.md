# System logowania

## Przegląd

System logowania w frameworku NimblePHP zapewnia kompleksowe zarządzanie logami aplikacji z obsługą różnych poziomów logowania, automatyczną rotacją plików oraz integracją z middleware.

## Klasa Log

### Lokalizacja

```php
NimblePHP\Framework\Log
```

### Właściwości statyczne

#### `$session`
```php
public static string $session;
```
Unikalny identyfikator sesji (GUID) używany do grupowania logów z jednego żądania.

#### `$storage`
```php
public static Storage $storage;
```
Instancja klasy Storage do zarządzania plikami logów.

## Metody statyczne

### `init()`
```php
public static function init(): void
```
Inicjalizuje system logowania - generuje sesję i tworzy instancję Storage.

### `log()`
```php
public static function log(string $message, string $level = 'INFO', array $content = []): bool
```
Główna metoda do tworzenia wpisów w logach.

#### Parametry
- `$message` - Wiadomość do zalogowania
- `$level` - Poziom logowania (domyślnie 'INFO')
- `$content` - Dodatkowe dane do zalogowania

#### Zwraca
- `bool` - `true` jeśli logowanie się powiodło, `false` w przeciwnym przypadku

#### Obsługiwane poziomy logowania
- `DEBUG` - Informacje debugowania
- `INFO` - Informacje ogólne
- `WARNING` - Ostrzeżenia
- `ERROR` - Błędy
- `CRITICAL` - Błędy krytyczne

#### Automatyczne mapowanie
- `ERR` → `ERROR`
- `FATAL_ERR` → `CRITICAL`
- `FATAL_ERROR` → `CRITICAL`

#### Przykład użycia
```php
Log::log('Użytkownik zalogował się', 'INFO', ['user_id' => 123, 'ip' => '192.168.1.1']);
Log::log('Błąd połączenia z bazą danych', 'ERROR', ['error' => $exception->getMessage()]);
Log::log('Debug informacja', 'DEBUG', ['data' => $debugData]);
```

## Struktura wpisu w logu

Każdy wpis w logu zawiera następujące informacje:

```json
{
    "datetime": "2024-01-15 10:30:45",
    "message": "Użytkownik zalogował się",
    "level": "INFO",
    "content": {
        "user_id": 123,
        "ip": "192.168.1.1"
    },
    "file": "/app/Controllers/AuthController.php",
    "class": "App\\Controllers\\AuthController",
    "function": "login",
    "line": 45,
    "get": {
        "page": "dashboard"
    },
    "session": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

### Pola wpisu
- `datetime` - Data i czas utworzenia wpisu
- `message` - Wiadomość logu
- `level` - Poziom logowania
- `content` - Dodatkowe dane
- `file` - Plik źródłowy
- `class` - Klasa źródłowa
- `function` - Funkcja źródłowa
- `line` - Numer linii
- `get` - Parametry GET z żądania
- `session` - Unikalny identyfikator sesji

## Integracja z kontrolerami

### Metoda log() w AbstractController
```php
#[Action("disabled")]
public function log(string $message, string $level = 'INFO', array $content = []): bool
```

Metoda dostępna w każdym kontrolerze dziedziczącym po `AbstractController`.

#### Przykład użycia w kontrolerze
```php
class UserController extends AbstractController
{
    public function create(): void
    {
        $data = $this->request->getAllPost();
        
        $userModel = $this->loadModel(UserModel::class);
        $success = $userModel->create($data);
        
        if ($success) {
            $this->log('Utworzono nowego użytkownika', 'INFO', [
                'user_data' => $data,
                'created_by' => $_SESSION['user_id'] ?? null
            ]);
            echo "Użytkownik został utworzony";
        } else {
            $this->log('Błąd podczas tworzenia użytkownika', 'ERROR', [
                'user_data' => $data,
                'error' => 'Database error'
            ]);
            echo "Błąd podczas tworzenia użytkownika";
        }
    }
}
```

## Integracja z modelami

### Trait LogTrait
Modele dziedziczące po `AbstractModel` automatycznie mają dostęp do funkcjonalności logowania przez `LogTrait`.

#### Przykład użycia w modelu
```php
class UserModel extends AbstractModel
{
    public function create(array $data): bool
    {
        try {
            $result = parent::create($data);
            
            if ($result) {
                $this->log('Utworzono użytkownika w bazie danych', 'INFO', [
                    'user_id' => $this->getId(),
                    'data' => $data
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->log('Błąd podczas tworzenia użytkownika', 'ERROR', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}
```

## Konfiguracja logowania

### Zmienna środowiskowa
```env
LOG=true
```

Gdy `LOG=false`, wszystkie wywołania `Log::log()` zwracają `false` bez zapisywania do pliku.

### Struktura plików logów
```
storage/
└── logs/
    ├── 2024_01_15.log.json
    ├── 2024_01_16.log.json
    └── 2024_01_17.log.json
```

### Format nazwy pliku
```
YYYY_MM_DD.log.json
```

## Automatyczna rotacja logów

System automatycznie zarządza plikami logów:

1. **Tworzenie nowego pliku** - Każdego dnia tworzony jest nowy plik
2. **Ograniczenie rozmiaru** - Pliki są monitorowane pod kątem rozmiaru
3. **Archiwizacja** - Stare pliki są automatycznie archiwizowane

### Metoda `rotateLogs()`
```php
private static function rotateLogs(string $currentFile): void
```

Sprawdza rozmiar pliku i wykonuje rotację jeśli jest zbyt duży.

## Integracja z middleware

System logowania integruje się z middleware poprzez hooki:

### Hook `beforeLog`
Wywoływany przed zapisaniem wpisu do logu.
```php
// W middleware
public function beforeLog(string &$message): void
{
    // Modyfikacja wiadomości przed zapisaniem
    $message = '[AUTH] ' . $message;
}
```

### Hook `afterLog`
Wywoływany po zapisaniu wpisu do logu.
```php
// W middleware
public function afterLog(array &$logContent): void
{
    // Modyfikacja danych logu po zapisaniu
    $logContent['custom_field'] = 'custom_value';
}
```

## Przykłady użycia

### Przykład 1: Logowanie błędów aplikacji
```php
try {
    // Kod aplikacji
    $result = $someService->process();
} catch (Exception $e) {
    Log::log('Błąd podczas przetwarzania', 'ERROR', [
        'exception' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Przykład 2: Logowanie akcji użytkownika
```php
public function login(): void
{
    $email = $this->request->getPost('email');
    $password = $this->request->getPost('password');
    
    $user = $this->authenticateUser($email, $password);
    
    if ($user) {
        $this->log('Użytkownik zalogował się', 'INFO', [
            'user_id' => $user['id'],
            'email' => $email,
            'ip' => $this->request->getServer('REMOTE_ADDR'),
            'user_agent' => $this->request->getServer('HTTP_USER_AGENT')
        ]);
        
        $this->response->redirect('/dashboard');
    } else {
        $this->log('Nieudana próba logowania', 'WARNING', [
            'email' => $email,
            'ip' => $this->request->getServer('REMOTE_ADDR')
        ]);
        
        $this->response->setStatusCode(401);
        $this->response->setJsonContent(['error' => 'Nieprawidłowe dane logowania']);
    }
}
```

### Przykład 3: Logowanie operacji na bazie danych
```php
public function delete(): bool
{
    $userId = $this->getId();
    
    try {
        $result = parent::delete();
        
        if ($result) {
            $this->log('Usunięto użytkownika', 'INFO', [
                'user_id' => $userId,
                'deleted_by' => $_SESSION['user_id'] ?? null
            ]);
        }
        
        return $result;
    } catch (Exception $e) {
        $this->log('Błąd podczas usuwania użytkownika', 'ERROR', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

## Najlepsze praktyki

### 1. Używaj odpowiednich poziomów logowania
- `DEBUG` - Tylko dla informacji debugowania
- `INFO` - Dla normalnych operacji
- `WARNING` - Dla potencjalnych problemów
- `ERROR` - Dla błędów aplikacji
- `CRITICAL` - Dla błędów krytycznych

### 2. Dodawaj kontekst do logów
```php
// Dobrze
$this->log('Utworzono użytkownika', 'INFO', [
    'user_id' => $userId,
    'email' => $email,
    'created_by' => $adminId
]);

// Źle
$this->log('Utworzono użytkownika', 'INFO');
```

### 3. Nie loguj wrażliwych danych
```php
// Dobrze
$this->log('Użytkownik zalogował się', 'INFO', [
    'user_id' => $user['id'],
    'email' => $user['email']
]);

// Źle - nie loguj haseł!
$this->log('Użytkownik zalogował się', 'INFO', [
    'password' => $password // NIGDY!
]);
```

### 4. Używaj spójnych nazw wiadomości
```php
// Spójne nazewnictwo
$this->log('user.created', 'INFO', $data);
$this->log('user.updated', 'INFO', $data);
$this->log('user.deleted', 'INFO', $data);
```

## Uwagi

- System logowania jest automatycznie inicjalizowany przez framework
- Logi są zapisywane w formacie JSON dla łatwego parsowania
- Każde żądanie ma unikalny identyfikator sesji
- System obsługuje automatyczną rotację plików
- Integracja z middleware pozwala na modyfikację logów
- Logowanie można wyłączyć przez zmienną środowiskową