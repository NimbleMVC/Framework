# ModelInterface - Interfejs modelu

## Przegląd

`ModelInterface` definiuje kontrakt dla wszystkich modeli w frameworku NimblePHP. Określa podstawowe metody ORM (Object-Relational Mapping) oraz operacje CRUD na bazie danych.

## Lokalizacja

```php
NimblePHP\Framework\Interfaces\ModelInterface
```

## Metody interfejsu

### `prepareTableInstance()`
```php
/**
 * Prepare table instance
 * @return void
 */
public function prepareTableInstance(): void;
```

Przygotowuje instancję tabeli na podstawie konfiguracji modelu.

### `create()`
```php
/**
 * Create element
 * @param array $data
 * @return bool
 * @throws DatabaseException
 */
public function create(array $data): bool;
```

Tworzy nowy element w bazie danych.

#### Parametry
- `$data` - Dane do zapisania (tablica asocjacyjna)

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Rzuca
- `DatabaseException` - Gdy baza danych jest wyłączona lub wystąpi błąd

### `read()`
```php
/**
 * Read element
 * @param array|null $condition
 * @param array|null $columns
 * @param string|null $orderBy
 * @return array
 * @throws DatabaseException
 */
public function read(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array;
```

Odczytuje pojedynczy element z bazy danych.

#### Parametry
- `$condition` - Warunki wyszukiwania (domyślnie `null`)
- `$columns` - Kolumny do pobrania (domyślnie wszystkie)
- `$orderBy` - Sortowanie (domyślnie `null`)

#### Zwraca
- `array` - Dane elementu lub pusty array

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `readAll()`
```php
/**
 * Read multiple element
 * @param array|null $condition
 * @param array|null $columns
 * @param string|null $orderBy
 * @param string|null $limit
 * @param string|null $groupBy
 * @return array
 * @throws DatabaseException
 */
public function readAll(?array $condition = null, ?array $columns = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null): array;
```

Odczytuje wszystkie elementy spełniające warunki.

#### Parametry
- `$condition` - Warunki wyszukiwania
- `$columns` - Kolumny do pobrania
- `$orderBy` - Sortowanie
- `$limit` - Limit wyników
- `$groupBy` - Grupowanie

#### Zwraca
- `array` - Tablica elementów

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `update()`
```php
/**
 * Update element
 * @param array $data
 * @return bool
 * @throws DatabaseException
 */
public function update(array $data): bool;
```

Aktualizuje istniejący element (wymaga ustawionego ID).

#### Parametry
- `$data` - Dane do aktualizacji

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `save()`
```php
/**
 * Create or update element
 * @param array $data
 * @return bool
 * @throws DatabaseException
 */
public function save(array $data): bool;
```

Tworzy nowy element lub aktualizuje istniejący (na podstawie ID).

#### Parametry
- `$data` - Dane do zapisania

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `delete()`
```php
/**
 * Delete element by ID
 * @return bool
 * @throws DatabaseException
 */
public function delete(): bool;
```

Usuwa element o ustawionym ID.

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `getId()`
```php
/**
 * Get element id
 * @return int|null
 */
public function getId(): ?int;
```

Zwraca ID aktualnie załadowanego elementu.

#### Zwraca
- `?int` - ID elementu lub `null`

### `setId()`
```php
/**
 * Set element id
 * @param int|null $id
 * @return ModelInterface
 */
public function setId(?int $id = null): self;
```

Ustawia ID elementu.

#### Parametry
- `$id` - ID elementu

#### Zwraca
- `self` - Instancja modelu (fluent interface)

### `count()`
```php
/**
 * Count elements
 * @param array|null $condition
 * @param string|null $groupBy
 * @return int
 * @throws DatabaseException
 */
public function count(?array $condition = null, ?string $groupBy = null): int;
```

Zlicza elementy spełniające warunki.

#### Parametry
- `$condition` - Warunki wyszukiwania
- `$groupBy` - Grupowanie

#### Zwraca
- `int` - Liczba elementów

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `isset()`
```php
/**
 * Count elements
 * @param array|null $condition
 * @return int
 * @throws DatabaseException
 */
public function isset(?array $condition = null): int;
```

Sprawdza czy istnieją elementy spełniające warunki.

#### Parametry
- `$condition` - Warunki wyszukiwania

#### Zwraca
- `int` - Liczba znalezionych elementów

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `query()`
```php
/**
 * Query
 * @param string $sql
 * @return array
 * @throws DatabaseException
 */
public function query(string $sql): array;
```

Wykonuje niestandardowe zapytanie SQL.

#### Parametry
- `$sql` - Zapytanie SQL

#### Zwraca
- `array` - Wyniki zapytania

#### Rzuca
- `DatabaseException` - Gdy wystąpi błąd bazy danych

### `getTableInstance()`
```php
/**
 * Get table instance
 * @return Table
 */
public function getTableInstance(): Table;
```

Zwraca instancję tabeli.

#### Zwraca
- `Table` - Instancja klasy Table

### `log()`
```php
/**
 * Create log
 * @param string $message
 * @param string $level
 * @param array $content
 * @return bool
 */
public function log(string $message, string $level = 'INFO', array $content = []): bool;
```

Tworzy wpis w logach aplikacji.

#### Parametry
- `$message` - Wiadomość do zalogowania
- `$level` - Poziom logowania (domyślnie 'INFO')
- `$content` - Dodatkowe dane do zalogowania

#### Zwraca
- `bool` - `true` jeśli logowanie się powiodło

### `afterConstruct()`
```php
/**
 * After construct method
 * @return void
 */
public function afterConstruct(): void;
```

Metoda wywoływana po konstruktorze modelu.

## Implementacja w AbstractModel

Klasa `AbstractModel` implementuje `ModelInterface` i dostarcza kompletne implementacje wszystkich metod:

```php
abstract class AbstractModel implements ModelInterface
{
    use LoadModelTrait;
    use LogTrait;

    public null|string|false $useTable = null;
    public string $name;
    public ControllerInterface $controller;
    protected Table $table;
    protected ?int $id = null;
    public array $conditions = [];

    // Implementacje wszystkich metod interfejsu...
}
```

## Przykład implementacji własnego modelu

```php
<?php
namespace App\Models;

use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Exception\DatabaseException;

class UserModel extends AbstractModel
{
    // Opcjonalne: ręczne określenie nazwy tabeli
    public string $useTable = 'users';
    
    // Opcjonalne: globalne warunki
    public array $conditions = ['active' => 1];
    
    public function afterConstruct(): void
    {
        // Dodatkowa inicjalizacja modelu
        $this->log('Model UserModel został załadowany', 'DEBUG');
    }
    
    // Własne metody modelu
    public function findByEmail(string $email): array
    {
        return $this->read(['email' => $email]);
    }
    
    public function getActiveUsers(): array
    {
        return $this->readAll(['active' => 1], ['id', 'name', 'email'], 'name ASC');
    }
    
    public function countActiveUsers(): int
    {
        return $this->count(['active' => 1]);
    }
    
    public function createUser(array $data): bool
    {
        // Dodanie domyślnych wartości
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['active'] = 1;
        
        try {
            $result = $this->create($data);
            
            if ($result) {
                $this->log('Utworzono użytkownika', 'INFO', [
                    'user_id' => $this->getId(),
                    'email' => $data['email']
                ]);
            }
            
            return $result;
        } catch (DatabaseException $e) {
            $this->log('Błąd podczas tworzenia użytkownika', 'ERROR', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    public function updateUser(int $userId, array $data): bool
    {
        $this->setId($userId);
        
        try {
            $result = $this->update($data);
            
            if ($result) {
                $this->log('Zaktualizowano użytkownika', 'INFO', [
                    'user_id' => $userId,
                    'updated_fields' => array_keys($data)
                ]);
            }
            
            return $result;
        } catch (DatabaseException $e) {
            $this->log('Błąd podczas aktualizacji użytkownika', 'ERROR', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function deleteUser(int $userId): bool
    {
        $this->setId($userId);
        
        try {
            $result = $this->delete();
            
            if ($result) {
                $this->log('Usunięto użytkownika', 'INFO', [
                    'user_id' => $userId
                ]);
            }
            
            return $result;
        } catch (DatabaseException $e) {
            $this->log('Błąd podczas usuwania użytkownika', 'ERROR', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getUserWithOrders(int $userId): array
    {
        $user = $this->read(['id' => $userId]);
        
        if (!empty($user)) {
            // Pobieranie zamówień użytkownika
            $orderModel = $this->loadModel('App\Models\OrderModel');
            $orders = $orderModel->readAll(['user_id' => $userId]);
            
            $user['orders'] = $orders;
        }
        
        return $user;
    }
    
    public function searchUsers(string $query): array
    {
        $sql = "SELECT * FROM users WHERE 
                name LIKE :query OR 
                email LIKE :query OR 
                phone LIKE :query";
        
        return $this->query($sql, ['query' => "%{$query}%"]);
    }
}
```

## Wymagania implementacji

### 1. Dziedziczenie
Wszystkie modele muszą dziedziczyć po `AbstractModel` lub bezpośrednio implementować `ModelInterface`.

### 2. Metody wymagane
Implementacja musi zawierać wszystkie metody zdefiniowane w interfejsie.

### 3. Obsługa wyjątków
Implementacja powinna obsługiwać `DatabaseException` rzucane przez metody interfejsu.

### 4. Konfiguracja tabeli
Model powinien określić nazwę tabeli przez właściwość `$useTable`.

## Korzyści z używania interfejsu

### 1. Spójność API
Wszystkie modele mają te same podstawowe metody CRUD.

### 2. Testowanie
Interfejs ułatwia testowanie modeli poprzez możliwość mockowania metod.

### 3. Dokumentacja
Interfejs służy jako dokumentacja wymaganych funkcjonalności modelu.

### 4. Rozszerzalność
Łatwe dodawanie nowych funkcjonalności do wszystkich modeli.

## Przykład testowania

```php
<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\UserModel;
use NimblePHP\Framework\Exception\DatabaseException;

class UserModelTest extends TestCase
{
    private UserModel $model;
    
    protected function setUp(): void
    {
        $this->model = new UserModel();
    }
    
    public function testCreateUser(): void
    {
        $data = [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com'
        ];
        
        $result = $this->model->createUser($data);
        
        $this->assertTrue($result);
        $this->assertNotNull($this->model->getId());
    }
    
    public function testFindByEmail(): void
    {
        $user = $this->model->findByEmail('jan@example.com');
        
        $this->assertIsArray($user);
        $this->assertEquals('jan@example.com', $user['email']);
    }
    
    public function testCountActiveUsers(): void
    {
        $count = $this->model->countActiveUsers();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    public function testDeleteUser(): void
    {
        $this->model->setId(1);
        $result = $this->model->deleteUser(1);
        
        $this->assertIsBool($result);
    }
}
```

## Uwagi

- Interfejs jest implementowany przez `AbstractModel`
- Wszystkie modele aplikacji powinny dziedziczyć po `AbstractModel`
- Model automatycznie łączy się z bazą danych na podstawie konfiguracji
- Wszystkie operacje CRUD są automatycznie logowane
- Model obsługuje relacje między tabelami
- Interfejs zapewnia spójność API dla wszystkich modeli