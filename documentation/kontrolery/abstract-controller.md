# AbstractController - Abstrakcyjna klasa kontrolera

## Przegląd

`AbstractController` to abstrakcyjna klasa bazowa dla wszystkich kontrolerów w frameworku NimblePHP. Dostarcza podstawowe funkcjonalności i metody wspólne dla wszystkich kontrolerów aplikacji.

## Lokalizacja

```php
NimblePHP\Framework\Abstracts\AbstractController
```

## Implementowane interfejsy

- `ControllerInterface`

## Używane traity

- `LoadModelTrait` - Dostarcza funkcjonalność ładowania modeli

## Właściwości publiczne

### `$name`
```php
public string $name;
```
Nazwa kontrolera (ustawiana automatycznie przez framework).

### `$action`
```php
public string $action;
```
Nazwa akcji (metody) kontrolera (ustawiana automatycznie przez framework).

### `$request`
```php
public RequestInterface $request;
```
Instancja klasy Request reprezentującej bieżące żądanie HTTP.

## Metody publiczne

### `log()`
```php
#[Action("disabled")]
public function log(string $message, string $level = 'INFO', array $content = []): bool
```

Tworzy wpis w logach aplikacji.

#### Parametry
- `$message` - Wiadomość do zalogowania
- `$level` - Poziom logowania (domyślnie 'INFO')
- `$content` - Dodatkowe dane do zalogowania (domyślnie pusty array)

#### Zwraca
- `bool` - `true` jeśli logowanie się powiodło, `false` w przeciwnym przypadku

#### Atrybuty
- `#[Action("disabled")]` - Metoda nie jest dostępna jako akcja HTTP

#### Przykład użycia
```php
$this->log('Użytkownik zalogował się', 'INFO', ['user_id' => 123]);
```

### `afterConstruct()`
```php
#[Action("disabled")]
public function afterConstruct(): void
```

Metoda wywoływana po konstruktorze kontrolera. Może być nadpisana w klasach dziedziczących, aby wykonać dodatkową inicjalizację.

#### Atrybuty
- `#[Action("disabled")]` - Metoda nie jest dostępna jako akcja HTTP

#### Przykład nadpisania
```php
public function afterConstruct(): void
{
    // Dodatkowa inicjalizacja kontrolera
    $this->checkAuthentication();
    $this->loadUserData();
}
```

## Dziedziczone metody z LoadModelTrait

### `loadModel()`
```php
public function loadModel(string $name): object
```

Ładuje instancję modelu o podanej nazwie.

#### Parametry
- `$name` - Nazwa klasy modelu (z namespace)

#### Zwraca
- `object` - Instancja modelu

#### Przykład użycia
```php
$userModel = $this->loadModel('App\Models\UserModel');
$users = $userModel->readAll();
```

## Przykład implementacji kontrolera

```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;

class UserController extends AbstractController
{
    public function index(): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $users = $userModel->readAll();
        
        // Renderowanie widoku lub zwracanie odpowiedzi
        echo json_encode($users);
    }
    
    public function show(int $id): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $user = $userModel->read(['id' => $id]);
        
        if (empty($user)) {
            http_response_code(404);
            echo "Użytkownik nie został znaleziony";
            return;
        }
        
        echo json_encode($user);
    }
    
    public function create(): void
    {
        $data = $this->request->getAllPost();
        
        $userModel = $this->loadModel('App\Models\UserModel');
        $success = $userModel->create($data);
        
        if ($success) {
            $this->log('Utworzono nowego użytkownika', 'INFO', $data);
            echo "Użytkownik został utworzony";
        } else {
            echo "Błąd podczas tworzenia użytkownika";
        }
    }
    
    public function afterConstruct(): void
    {
        // Sprawdzenie autoryzacji dla wszystkich akcji
        if (!$this->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }
    
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
```

## Konwencje nazewnictwa

### Nazwy kontrolerów
- Kontrolery powinny kończyć się sufiksem `Controller`
- Nazwa powinna być w PascalCase
- Przykład: `UserController`, `ProductController`, `AdminController`

### Nazwy akcji
- Akcje powinny być w camelCase
- Standardowe akcje CRUD: `index`, `show`, `create`, `store`, `edit`, `update`, `delete`
- Przykład: `showUser`, `createProduct`, `updateSettings`

## Atrybuty akcji

### `#[Action("disabled")]`
Oznacza metodę jako niedostępną jako akcja HTTP. Metoda może być wywoływana tylko wewnętrznie.

### `#[Action("public")]`
Oznacza metodę jako publiczną akcję HTTP (domyślne zachowanie).

## Cykl życia kontrolera

1. **Konstruktor** - Tworzenie instancji kontrolera
2. **afterConstruct()** - Dodatkowa inicjalizacja (opcjonalna)
3. **Wykonanie akcji** - Uruchomienie odpowiedniej metody
4. **Zniszczenie** - Automatyczne czyszczenie zasobów

## Uwagi

- Wszystkie kontrolery muszą dziedziczyć po `AbstractController`
- Metody oznaczone atrybutem `#[Action("disabled")]` nie są dostępne jako akcje HTTP
- Klasa automatycznie dostarcza dostęp do żądania HTTP przez właściwość `$request`
- Trait `LoadModelTrait` zapewnia łatwy dostęp do modeli
- Metoda `afterConstruct()` jest idealnym miejscem na dodatkową inicjalizację