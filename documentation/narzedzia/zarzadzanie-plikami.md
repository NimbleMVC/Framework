# Zarządzanie plikami - Klasa Storage

## Przegląd

Klasa `Storage` w frameworku NimblePHP zapewnia bezpieczne i wygodne zarządzanie plikami w aplikacji. Oferuje metody do zapisywania, odczytywania, usuwania i zarządzania plikami z automatycznym tworzeniem katalogów i walidacją ścieżek.

## Lokalizacja

```php
NimblePHP\Framework\Storage
```

## Konstruktor

```php
public function __construct(string $directory, bool $securePath = true)
```

### Parametry
- `$directory` - Nazwa katalogu w folderze `storage/`
- `$securePath` - Czy używać bezpiecznych ścieżek (domyślnie `true`)

### Działanie
Konstruktor tworzy instancję Storage dla określonego katalogu w folderze `storage/` projektu.

#### Przykład użycia
```php
// Tworzenie instancji dla katalogu uploads
$storage = new Storage('uploads');

// Tworzenie instancji dla katalogu cache z niestandardową ścieżką
$storage = new Storage('cache', false);
```

## Metody publiczne

### `put()`
```php
public function put(string $filePath, string $content): true
```
Zapisuje zawartość do pliku.

#### Parametry
- `$filePath` - Ścieżka do pliku (względna do katalogu Storage)
- `$content` - Zawartość do zapisania

#### Zwraca
- `true` - Jeśli operacja się powiodła

#### Rzuca
- `NimbleException` - Gdy nie można zapisać pliku

#### Przykład użycia
```php
$storage = new Storage('documents');
$storage->put('user_123/profile.txt', 'Dane użytkownika');
$storage->put('config/settings.json', json_encode($settings));
```

### `append()`
```php
public function append(string $filePath, string $content, string $append = PHP_EOL): true
```
Dodaje zawartość na końcu pliku.

#### Parametry
- `$filePath` - Ścieżka do pliku
- `$content` - Zawartość do dodania
- `$append` - Separator do dodania (domyślnie nowa linia)

#### Zwraca
- `true` - Jeśli operacja się powiodła

#### Rzuca
- `NimbleException` - Gdy nie można dodać do pliku

#### Przykład użycia
```php
$storage = new Storage('logs');
$storage->append('application.log', 'Nowy wpis w logu');
$storage->append('debug.log', $debugData, "\n---\n");
```

### `get()`
```php
public function get(string $filePath): ?string
```
Odczytuje zawartość pliku.

#### Parametry
- `$filePath` - Ścieżka do pliku

#### Zwraca
- `string` - Zawartość pliku lub `null` jeśli plik nie istnieje

#### Przykład użycia
```php
$storage = new Storage('config');
$content = $storage->get('database.json');
if ($content) {
    $config = json_decode($content, true);
}
```

### `delete()`
```php
public function delete(string $filePath): bool
```
Usuwa plik.

#### Parametry
- `$filePath` - Ścieżka do pliku

#### Zwraca
- `bool` - `true` jeśli plik został usunięty, `false` jeśli nie istniał

#### Przykład użycia
```php
$storage = new Storage('uploads');
$deleted = $storage->delete('user_123/avatar.jpg');
if ($deleted) {
    echo "Plik został usunięty";
}
```

### `exists()`
```php
public function exists(string $filePath): bool
```
Sprawdza czy plik istnieje.

#### Parametry
- `$filePath` - Ścieżka do pliku

#### Zwraca
- `bool` - `true` jeśli plik istnieje

#### Przykład użycia
```php
$storage = new Storage('documents');
if ($storage->exists('user_123/contract.pdf')) {
    // Plik istnieje
}
```

### `listFiles()`
```php
public function listFiles(bool $extend = false): array
```
Listuje pliki w katalogu Storage.

#### Parametry
- `$extend` - Czy zwrócić szczegółowe informacje o plikach

#### Zwraca
- `array` - Lista plików

#### Przykład użycia
```php
$storage = new Storage('uploads');

// Podstawowa lista
$files = $storage->listFiles();
// ['file1.txt', 'file2.jpg', 'subfolder/file3.pdf']

// Szczegółowa lista
$files = $storage->listFiles(true);
// [
//     'file1.txt' => ['size' => 1024, 'modified' => '2024-01-15 10:30:00'],
//     'file2.jpg' => ['size' => 2048, 'modified' => '2024-01-15 11:00:00']
// ]
```

### `getFullPath()`
```php
public function getFullPath(string $filePath): string
```
Zwraca pełną ścieżkę do pliku.

#### Parametry
- `$filePath` - Ścieżka względna do pliku

#### Zwraca
- `string` - Pełna ścieżka do pliku

#### Przykład użycia
```php
$storage = new Storage('uploads');
$fullPath = $storage->getFullPath('user_123/avatar.jpg');
// /var/www/project/storage/uploads/user_123/avatar.jpg
```

### `getPath()`
```php
public function getPath(): string
```
Zwraca ścieżkę do katalogu Storage.

#### Zwraca
- `string` - Ścieżka do katalogu Storage

#### Przykład użycia
```php
$storage = new Storage('documents');
$path = $storage->getPath();
// /var/www/project/storage/documents
```

### `copy()`
```php
public function copy(string $sourcePath, string $destinationPath): bool
```
Kopiuje plik w obrębie Storage.

#### Parametry
- `$sourcePath` - Ścieżka źródłowa
- `$destinationPath` - Ścieżka docelowa

#### Zwraca
- `bool` - `true` jeśli kopiowanie się powiodło

#### Przykład użycia
```php
$storage = new Storage('uploads');
$copied = $storage->copy('original.jpg', 'backup/original.jpg');
```

### `move()`
```php
public function move(string $sourcePath, string $destinationPath): bool
```
Przenosi plik w obrębie Storage.

#### Parametry
- `$sourcePath` - Ścieżka źródłowa
- `$destinationPath` - Ścieżka docelowa

#### Zwraca
- `bool` - `true` jeśli przeniesienie się powiodło

#### Przykład użycia
```php
$storage = new Storage('uploads');
$moved = $storage->move('temp/file.jpg', 'processed/file.jpg');
```

### `getMetadata()`
```php
public function getMetadata(string $filePath): ?array
```
Pobiera metadane pliku.

#### Parametry
- `$filePath` - Ścieżka do pliku

#### Zwraca
- `array` - Metadane pliku lub `null` jeśli plik nie istnieje

#### Przykład użycia
```php
$storage = new Storage('uploads');
$metadata = $storage->getMetadata('document.pdf');
if ($metadata) {
    echo "Rozmiar: " . $metadata['size'] . " bajtów";
    echo "Ostatnia modyfikacja: " . $metadata['modified'];
}
```

## Metody prywatne

### `createBasePath()`
```php
private function createBasePath(): void
```
Tworzy podstawowy katalog Storage jeśli nie istnieje.

### `ensureDirectoryExists()`
```php
private function ensureDirectoryExists(string $directory): void
```
Upewnia się, że katalog istnieje, tworząc go jeśli potrzeba.

## Przykłady użycia

### Przykład 1: Zarządzanie plikami użytkowników
```php
class UserFileManager
{
    private Storage $storage;
    
    public function __construct()
    {
        $this->storage = new Storage('users');
    }
    
    public function saveUserAvatar(int $userId, string $imageData): bool
    {
        try {
            $this->storage->put("user_{$userId}/avatar.jpg", $imageData);
            return true;
        } catch (NimbleException $e) {
            Log::log('Błąd podczas zapisywania avatara', 'ERROR', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function getUserAvatar(int $userId): ?string
    {
        return $this->storage->get("user_{$userId}/avatar.jpg");
    }
    
    public function deleteUserFiles(int $userId): bool
    {
        $files = $this->storage->listFiles();
        $deleted = true;
        
        foreach ($files as $file) {
            if (strpos($file, "user_{$userId}/") === 0) {
                if (!$this->storage->delete($file)) {
                    $deleted = false;
                }
            }
        }
        
        return $deleted;
    }
}
```

### Przykład 2: System cache
```php
class CacheManager
{
    private Storage $storage;
    
    public function __construct()
    {
        $this->storage = new Storage('cache');
    }
    
    public function set(string $key, mixed $data, int $ttl = 3600): bool
    {
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        try {
            $this->storage->put("{$key}.cache", json_encode($cacheData));
            return true;
        } catch (NimbleException $e) {
            return false;
        }
    }
    
    public function get(string $key): mixed
    {
        $content = $this->storage->get("{$key}.cache");
        
        if (!$content) {
            return null;
        }
        
        $cacheData = json_decode($content, true);
        
        if (!$cacheData || $cacheData['expires'] < time()) {
            $this->storage->delete("{$key}.cache");
            return null;
        }
        
        return $cacheData['data'];
    }
    
    public function clear(): void
    {
        $files = $this->storage->listFiles();
        
        foreach ($files as $file) {
            if (strpos($file, '.cache') !== false) {
                $this->storage->delete($file);
            }
        }
    }
}
```

### Przykład 3: System logów aplikacji
```php
class ApplicationLogger
{
    private Storage $storage;
    
    public function __construct()
    {
        $this->storage = new Storage('logs');
    }
    
    public function log(string $message, string $level = 'INFO'): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        ];
        
        $filename = date('Y-m-d') . '.log';
        $content = json_encode($logEntry) . PHP_EOL;
        
        try {
            $this->storage->append($filename, $content);
        } catch (NimbleException $e) {
            // Fallback do standardowego logowania
            error_log("Błąd logowania: " . $e->getMessage());
        }
    }
    
    public function getLogs(string $date): array
    {
        $content = $this->storage->get("{$date}.log");
        
        if (!$content) {
            return [];
        }
        
        $logs = [];
        $lines = explode(PHP_EOL, trim($content));
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                $logs[] = json_decode($line, true);
            }
        }
        
        return $logs;
    }
}
```

## Bezpieczeństwo

### Walidacja ścieżek
Klasa Storage automatycznie waliduje ścieżki, aby zapobiec atakom typu path traversal:

```php
// Bezpieczne - ścieżka jest walidowana
$storage = new Storage('uploads');
$storage->put('user_123/file.txt', 'content'); // OK

// Niebezpieczne ścieżki są automatycznie blokowane
$storage->put('../../../etc/passwd', 'content'); // Zablokowane
```

### Tworzenie katalogów
Klasa automatycznie tworzy wymagane katalogi:

```php
$storage = new Storage('deep/nested/directory');
$storage->put('file.txt', 'content'); // Katalogi zostaną utworzone automatycznie
```

## Struktura katalogów

```
storage/
├── uploads/          # Pliki przesłane przez użytkowników
├── cache/           # Pliki cache aplikacji
├── logs/            # Logi aplikacji
├── documents/       # Dokumenty
└── temp/           # Pliki tymczasowe
```

## Uwagi

- Klasa Storage automatycznie tworzy katalogi jeśli nie istnieją
- Wszystkie ścieżki są walidowane pod kątem bezpieczeństwa
- Metody rzucają `NimbleException` w przypadku błędów
- Klasa jest thread-safe i może być używana w środowiskach wielowątkowych
- Automatyczne czyszczenie plików tymczasowych
- Obsługa dużych plików z optymalizacją pamięci