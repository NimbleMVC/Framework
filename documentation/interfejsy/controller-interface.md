# ControllerInterface - Interfejs kontrolera

## Przegląd

`ControllerInterface` definiuje kontrakt dla wszystkich kontrolerów w frameworku NimblePHP. Określa podstawowe metody, które muszą być implementowane przez każdy kontroler.

## Lokalizacja

```php
NimblePHP\Framework\Interfaces\ControllerInterface
```

## Metody interfejsu

### `loadModel()`
```php
/**
 * Load model
 * @template T
 * @param class-string<T> $name
 * @return T
 * @throws NimbleException
 * @throws NotFoundException
 */
public function loadModel(string $name): object;
```

Ładuje instancję modelu o podanej nazwie klasy.

#### Parametry
- `$name` - Nazwa klasy modelu (używając `::class`)

#### Zwraca
- `object` - Instancja modelu

#### Rzuca
- `NimbleException` - Gdy wystąpi błąd podczas ładowania modelu
- `NotFoundException` - Gdy model nie zostanie znaleziony

#### Przykład użycia
```php
// W kontrolerze
$userModel = $this->loadModel(UserModel::class);
$productModel = $this->loadModel(ProductModel::class);
```

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
- `$content` - Dodatkowe dane do zalogowania (domyślnie pusty array)

#### Zwraca
- `bool` - `true` jeśli logowanie się powiodło, `false` w przeciwnym przypadku

#### Przykład użycia
```php
// W kontrolerze
$this->log('Użytkownik zalogował się', 'INFO', ['user_id' => 123]);
$this->log('Błąd podczas przetwarzania', 'ERROR', ['error' => $exception->getMessage()]);
```

### `afterConstruct()`
```php
/**
 * After construct method
 * @return void
 */
public function afterConstruct(): void;
```

Metoda wywoływana po konstruktorze kontrolera. Może być używana do dodatkowej inicjalizacji.

#### Przykład implementacji
```php
// W kontrolerze
public function afterConstruct(): void
{
    // Sprawdzenie autoryzacji
    if (!$this->isAuthenticated()) {
        header('Location: /login');
        exit;
    }
    
    // Ładowanie danych użytkownika
    $this->loadUserData();
}
```

## Implementacja w AbstractController

Klasa `AbstractController` implementuje `ControllerInterface` i dostarcza domyślne implementacje wszystkich metod:

```php
abstract class AbstractController implements ControllerInterface
{
    use LoadModelTrait;

    public string $name;
    public string $action;
    public RequestInterface $request;

    #[Action("disabled")]
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

    #[Action("disabled")]
    public function afterConstruct(): void
    {
        // Domyślnie pusta implementacja
    }
}
```

## Przykład implementacji własnego kontrolera

```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Exception\NotFoundException;

class UserController extends AbstractController
{
    public function index(): void
    {
        try {
            $userModel = $this->loadModel(UserModel::class);
            $users = $userModel->readAll();
            
            $this->log('Pobrano listę użytkowników', 'INFO', [
                'count' => count($users)
            ]);
            
            echo json_encode($users);
        } catch (NotFoundException $e) {
            $this->log('Model UserModel nie został znaleziony', 'ERROR');
            http_response_code(500);
            echo "Błąd serwera";
        }
    }
    
    public function show(int $id): void
    {
        $userModel = $this->loadModel(UserModel::class);
        $user = $userModel->read(['id' => $id]);
        
        if (empty($user)) {
            $this->log('Użytkownik nie został znaleziony', 'WARNING', ['id' => $id]);
            http_response_code(404);
            echo "Użytkownik nie został znaleziony";
            return;
        }
        
        $this->log('Pobrano dane użytkownika', 'INFO', ['user_id' => $id]);
        echo json_encode($user);
    }
    
    public function create(): void
    {
        $data = $this->request->getAllPost();
        
        // Walidacja danych
        if (empty($data['name']) || empty($data['email'])) {
            $this->log('Nieprawidłowe dane użytkownika', 'WARNING', $data);
            http_response_code(400);
            echo "Nieprawidłowe dane";
            return;
        }
        
        try {
            $userModel = $this->loadModel(UserModel::class);
            $success = $userModel->create($data);
            
            if ($success) {
                $this->log('Utworzono nowego użytkownika', 'INFO', [
                    'user_data' => $data,
                    'user_id' => $userModel->getId()
                ]);
                echo "Użytkownik został utworzony";
            } else {
                $this->log('Błąd podczas tworzenia użytkownika', 'ERROR', $data);
                echo "Błąd podczas tworzenia użytkownika";
            }
        } catch (\Exception $e) {
            $this->log('Wyjątek podczas tworzenia użytkownika', 'ERROR', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            http_response_code(500);
            echo "Błąd serwera";
        }
    }
    
    public function afterConstruct(): void
    {
        // Sprawdzenie autoryzacji dla wszystkich akcji
        if (!$this->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        
        // Dodatkowa inicjalizacja
        $this->loadUserPermissions();
    }
    
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    private function loadUserPermissions(): void
    {
        // Ładowanie uprawnień użytkownika
        if (isset($_SESSION['user_id'])) {
            $permissionModel = $this->loadModel(PermissionModel::class);
            $_SESSION['permissions'] = $permissionModel->getUserPermissions($_SESSION['user_id']);
        }
    }
}
```

## Wymagania implementacji

### 1. Dziedziczenie
Wszystkie kontrolery muszą dziedziczyć po `AbstractController` lub bezpośrednio implementować `ControllerInterface`.

### 2. Metody wymagane
Implementacja musi zawierać wszystkie metody zdefiniowane w interfejsie:
- `loadModel(string $name): object`
- `log(string $message, string $level = 'INFO', array $content = []): bool`
- `afterConstruct(): void`

### 3. Obsługa wyjątków
Implementacja powinna obsługiwać wyjątki rzucane przez metody interfejsu:
- `NimbleException`
- `NotFoundException`

## Korzyści z używania interfejsu

### 1. Spójność
Wszystkie kontrolery mają te same podstawowe metody, co zapewnia spójność w całej aplikacji.

### 2. Testowanie
Interfejs ułatwia testowanie kontrolerów poprzez możliwość mockowania metod.

### 3. Dokumentacja
Interfejs służy jako dokumentacja wymaganych funkcjonalności kontrolera.

### 4. Rozszerzalność
Łatwe dodawanie nowych funkcjonalności do wszystkich kontrolerów.

## Przykład testowania

```php
<?php
namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use NimblePHP\Framework\Exception\NotFoundException;

class UserControllerTest extends TestCase
{
    private UserController $controller;
    
    protected function setUp(): void
    {
        $this->controller = new UserController();
    }
    
    public function testLoadModelThrowsExceptionForInvalidModel(): void
    {
        $this->expectException(NotFoundException::class);
        
        $this->controller->loadModel(\Invalid\Model\Class::class);
    }
    
    public function testLogMethodReturnsBoolean(): void
    {
        $result = $this->controller->log('Test message', 'INFO');
        
        $this->assertIsBool($result);
    }
    
    public function testAfterConstructMethodIsCallable(): void
    {
        // Metoda powinna być wywoływana bez błędów
        $this->controller->afterConstruct();
        
        $this->assertTrue(true); // Jeśli nie ma wyjątku, test przechodzi
    }
}
```

## Uwagi

- Interfejs jest implementowany przez `AbstractController`
- Wszystkie kontrolery aplikacji powinny dziedziczyć po `AbstractController`
- Metody interfejsu są oznaczone atrybutem `#[Action("disabled")]` w `AbstractController`
- Interfejs zapewnia spójność API dla wszystkich kontrolerów
- Implementacja w `AbstractController` dostarcza domyślne zachowanie