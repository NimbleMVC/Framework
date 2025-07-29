# AbstractModel - Abstrakcyjna klasa modelu

## Przegląd

`AbstractModel` to abstrakcyjna klasa bazowa dla wszystkich modeli w frameworku NimblePHP. Dostarcza kompletne funkcjonalności ORM (Object-Relational Mapping) oraz podstawowe operacje CRUD na bazie danych.

## Lokalizacja

```php
NimblePHP\Framework\Abstracts\AbstractModel
```

## Implementowane interfejsy

- `ModelInterface`

## Używane traity

- `LoadModelTrait` - Dostarcza funkcjonalność ładowania modeli
- `LogTrait` - Dostarcza funkcjonalność logowania

## Właściwości publiczne

### `$useTable`
```php
public null|string|false $useTable = null;
```
Nazwa tabeli w bazie danych:
- `null` - Automatyczne określenie nazwy tabeli na podstawie nazwy modelu
- `string` - Ręcznie określona nazwa tabeli
- `false` - Model nie używa bazy danych

### `$name`
```php
public string $name;
```
Nazwa modelu (ustawiana automatycznie przez framework).

### `$controller`
```php
public ControllerInterface $controller;
```
Instancja kontrolera, który załadował model.

### `$conditions`
```php
public array $conditions = [];
```
Globalne warunki dla zapytań (filtry).

## Właściwości chronione

### `$table`
```php
protected Table $table;
```
Instancja klasy Table do obsługi operacji na bazie danych.

### `$id`
```php
protected ?int $id = null;
```
ID aktualnie załadowanego elementu.

## Metody publiczne

### `afterConstruct()`
```php
public function afterConstruct(): void
```
Metoda wywoływana po konstruktorze modelu. Może być nadpisana w klasach dziedziczących.

### `create()`
```php
public function create(array $data): bool
```
Tworzy nowy element w bazie danych.

#### Parametry
- `$data` - Dane do zapisania (tablica asocjacyjna)

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Rzuca
- `DatabaseException` - Gdy baza danych jest wyłączona lub wystąpi błąd

#### Przykład użycia
```php
$userModel = $this->loadModel('App\Models\UserModel');
$success = $userModel->create([
    'name' => 'Jan Kowalski',
    'email' => 'jan@example.com',
    'created_at' => date('Y-m-d H:i:s')
]);
```

### `save()`
```php
public function save(array $data): bool
```
Tworzy nowy element lub aktualizuje istniejący (na podstawie ID).

#### Parametry
- `$data` - Dane do zapisania

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Przykład użycia
```php
$userModel = $this->loadModel('App\Models\UserModel');
$userModel->setId(123);
$success = $userModel->save(['name' => 'Nowe imię']);
```

### `read()`
```php
public function read(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
```
Odczytuje pojedynczy element z bazy danych.

#### Parametry
- `$condition` - Warunki wyszukiwania (domyślnie `null`)
- `$columns` - Kolumny do pobrania (domyślnie wszystkie)
- `$orderBy` - Sortowanie (domyślnie `null`)

#### Zwraca
- `array` - Dane elementu lub pusty array

#### Przykład użycia
```php
$user = $userModel->read(['id' => 123]);
$user = $userModel->read(['email' => 'jan@example.com'], ['id', 'name']);
```

### `readSecure()`
```php
public function readSecure(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
```
Odczytuje element z dodatkowymi zabezpieczeniami (filtrowanie HTML).

#### Parametry
- `$condition` - Warunki wyszukiwania
- `$columns` - Kolumny do pobrania
- `$orderBy` - Sortowanie

#### Zwraca
- `array` - Dane elementu z zabezpieczeniami

### `readAll()`
```php
public function readAll(?array $condition = null, ?array $columns = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null): array
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

#### Przykład użycia
```php
$users = $userModel->readAll(['active' => 1], ['id', 'name'], 'name ASC', '10');
```

### `update()`
```php
public function update(array $data): bool
```
Aktualizuje istniejący element (wymaga ustawionego ID).

#### Parametry
- `$data` - Dane do aktualizacji

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Przykład użycia
```php
$userModel->setId(123);
$success = $userModel->update(['name' => 'Nowe imię']);
```

### `delete()`
```php
public function delete(): bool
```
Usuwa element o ustawionym ID.

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Przykład użycia
```php
$userModel->setId(123);
$success = $userModel->delete();
```

### `deleteByConditions()`
```php
public function deleteByConditions(array $conditions): bool
```
Usuwa elementy spełniające podane warunki.

#### Parametry
- `$conditions` - Warunki usuwania

#### Zwraca
- `bool` - `true` jeśli operacja się powiodła

#### Przykład użycia
```php
$success = $userModel->deleteByConditions(['active' => 0]);
```

### `getId()`
```php
public function getId(): ?int
```
Zwraca ID aktualnie załadowanego elementu.

#### Zwraca
- `?int` - ID elementu lub `null`

### `setId()`
```php
public function setId(?int $id = null): self
```
Ustawia ID elementu.

#### Parametry
- `$id` - ID elementu

#### Zwraca
- `self` - Instancja modelu (fluent interface)

### `prepareTableInstance()`
```php
public function prepareTableInstance(): void
```
Przygotowuje instancję tabeli na podstawie konfiguracji modelu.

### `count()`
```php
public function count(?array $condition = null, ?string $groupBy = null): int
```
Zlicza elementy spełniające warunki.

#### Parametry
- `$condition` - Warunki wyszukiwania
- `$groupBy` - Grupowanie

#### Zwraca
- `int` - Liczba elementów

### `isset()`
```php
public function isset(?array $condition = null): int
```
Sprawdza czy istnieją elementy spełniające warunki.

#### Parametry
- `$condition` - Warunki wyszukiwania

#### Zwraca
- `int` - Liczba znalezionych elementów

### `query()`
```php
public function query(string $sql): array
```
Wykonuje niestandardowe zapytanie SQL.

#### Parametry
- `$sql` - Zapytanie SQL

#### Zwraca
- `array` - Wyniki zapytania

#### Przykład użycia
```php
$results = $userModel->query("SELECT * FROM users WHERE created_at > '2024-01-01'");
```

### `getTableInstance()`
```php
public function getTableInstance(): Table
```
Zwraca instancję tabeli.

#### Zwraca
- `Table` - Instancja klasy Table

### `bind()`
```php
public function bind(
    BindType|array $bind,
    ?string $tableName = null,
    ?string $primaryKey = null,
    ?string $foreignKey = null,
    null|array|Condition $condition = null,
    ?string $tableAlias = null
): self
```
Tworzy relację między tabelami.

#### Parametry
- `$bind` - Typ relacji lub tablica konfiguracji
- `$tableName` - Nazwa tabeli docelowej
- `$primaryKey` - Klucz główny
- `$foreignKey` - Klucz obcy
- `$condition` - Dodatkowe warunki
- `$tableAlias` - Alias tabeli

#### Zwraca
- `self` - Instancja modelu

### `setCondition()`
```php
public function setCondition(Condition|string $key, null|string|array $value = null): self
```
Ustawia warunek dla zapytań.

#### Parametry
- `$key` - Klucz warunku
- `$value` - Wartość warunku

#### Zwraca
- `self` - Instancja modelu

### `clearConditions()`
```php
public function clearConditions(): self
```
Czyści wszystkie ustawione warunki.

#### Zwraca
- `self` - Instancja modelu

## Przykład implementacji modelu

```php
<?php
namespace App\Models;

use NimblePHP\Framework\Abstracts\AbstractModel;

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
        
        return $this->create($data);
    }
}
```

## Konwencje nazewnictwa

### Nazwy modeli
- Modele powinny kończyć się sufiksem `Model`
- Nazwa powinna być w PascalCase
- Przykład: `UserModel`, `ProductModel`, `OrderModel`

### Nazwy tabel
- Domyślnie nazwa tabeli jest generowana automatycznie na podstawie nazwy modelu
- Można ręcznie określić nazwę tabeli przez właściwość `$useTable`

## Uwagi

- Model automatycznie łączy się z bazą danych na podstawie konfiguracji
- Wszystkie operacje CRUD są automatycznie logowane
- Model obsługuje relacje między tabelami
- Można tworzyć własne metody w modelach dziedziczących
- Model automatycznie obsługuje błędne zapytania do bazy danych