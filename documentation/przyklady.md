# Przykłady użycia NimblePHP Framework

## Przykład 1: Kompletna aplikacja CRUD

### Struktura projektu
```
app/
├── Controllers/
│   └── UserController.php
├── Models/
│   └── UserModel.php
├── Views/
│   └── users/
│       ├── index.php
│       ├── show.php
│       ├── create.php
│       └── edit.php
└── config/
    └── database.php
```

### Model użytkownika
```php
<?php
namespace App\Models;

use NimblePHP\Framework\Abstracts\AbstractModel;

class UserModel extends AbstractModel
{
    public string $useTable = 'users';
    
    public function afterConstruct(): void
    {
        $this->log('Model UserModel został załadowany', 'DEBUG');
    }
    
    public function findByEmail(string $email): array
    {
        return $this->read(['email' => $email]);
    }
    
    public function getActiveUsers(): array
    {
        return $this->readAll(['active' => 1], ['id', 'name', 'email'], 'name ASC');
    }
    
    public function createUser(array $data): bool
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['active'] = 1;
        
        return $this->create($data);
    }
}
```

### Kontroler użytkownika
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;

class UserController extends AbstractController
{
    public function index(): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $users = $userModel->getActiveUsers();
        
        $this->log('Pobrano listę użytkowników', 'INFO', ['count' => count($users)]);
        
        // Renderowanie widoku
        include 'app/Views/users/index.php';
    }
    
    public function show(int $id): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $user = $userModel->read(['id' => $id]);
        
        if (empty($user)) {
            $this->log('Użytkownik nie został znaleziony', 'WARNING', ['id' => $id]);
            http_response_code(404);
            echo "Użytkownik nie został znaleziony";
            return;
        }
        
        include 'app/Views/users/show.php';
    }
    
    public function create(): void
    {
        if ($this->request->getMethod() === 'POST') {
            $data = $this->request->getAllPost();
            
            // Walidacja
            if (empty($data['name']) || empty($data['email'])) {
                $this->log('Nieprawidłowe dane użytkownika', 'WARNING', $data);
                $error = "Nazwa i email są wymagane";
            } else {
                $userModel = $this->loadModel('App\Models\UserModel');
                $success = $userModel->createUser($data);
                
                if ($success) {
                    $this->log('Utworzono nowego użytkownika', 'INFO', [
                        'user_id' => $userModel->getId(),
                        'email' => $data['email']
                    ]);
                    header('Location: /users');
                    exit;
                } else {
                    $error = "Błąd podczas tworzenia użytkownika";
                }
            }
        }
        
        include 'app/Views/users/create.php';
    }
    
    public function edit(int $id): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $user = $userModel->read(['id' => $id]);
        
        if (empty($user)) {
            http_response_code(404);
            echo "Użytkownik nie został znaleziony";
            return;
        }
        
        if ($this->request->getMethod() === 'POST') {
            $data = $this->request->getAllPost();
            
            $userModel->setId($id);
            $success = $userModel->update($data);
            
            if ($success) {
                $this->log('Zaktualizowano użytkownika', 'INFO', ['user_id' => $id]);
                header('Location: /users/' . $id);
                exit;
            } else {
                $error = "Błąd podczas aktualizacji użytkownika";
            }
        }
        
        include 'app/Views/users/edit.php';
    }
    
    public function delete(int $id): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $userModel->setId($id);
        $success = $userModel->delete();
        
        if ($success) {
            $this->log('Usunięto użytkownika', 'INFO', ['user_id' => $id]);
            $this->response->setJsonContent(['status' => 'success']);
        } else {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent(['error' => 'Błąd podczas usuwania']);
        }
        
        $this->response->send();
    }
}
```

### Widok listy użytkowników
```php
<!DOCTYPE html>
<html>
<head>
    <title>Lista użytkowników</title>
</head>
<body>
    <h1>Lista użytkowników</h1>
    
    <a href="/users/create">Dodaj nowego użytkownika</a>
    
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa</th>
                <th>Email</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <a href="/users/<?= $user['id'] ?>">Pokaż</a>
                    <a href="/users/<?= $user['id'] ?>/edit">Edytuj</a>
                    <button onclick="deleteUser(<?= $user['id'] ?>)">Usuń</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
    function deleteUser(id) {
        if (confirm('Czy na pewno chcesz usunąć tego użytkownika?')) {
            fetch('/users/' + id, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Błąd podczas usuwania użytkownika');
                }
            });
        }
    }
    </script>
</body>
</html>
```

## Przykład 2: API REST

### Kontroler API
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;

class ApiController extends AbstractController
{
    public function afterConstruct(): void
    {
        // Sprawdzenie API key
        $apiKey = $this->request->getHeader('X-API-Key');
        if (!$this->validateApiKey($apiKey)) {
            $this->response->setStatusCode(401);
            $this->response->setJsonContent(['error' => 'Nieprawidłowy API key']);
            $this->response->send();
            exit;
        }
        
        // Ustawienie nagłówków CORS
        $this->response->addHeader('Access-Control-Allow-Origin', '*');
        $this->response->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $this->response->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key');
    }
    
    public function getUsers(): void
    {
        try {
            $userModel = $this->loadModel('App\Models\UserModel');
            $users = $userModel->readAll();
            
            $this->response->setJsonContent([
                'status' => 'success',
                'data' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            $this->log('Błąd API: pobieranie użytkowników', 'ERROR', [
                'error' => $e->getMessage()
            ]);
            
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Błąd serwera'
            ]);
        }
        
        $this->response->send();
    }
    
    public function getUser(int $id): void
    {
        try {
            $userModel = $this->loadModel('App\Models\UserModel');
            $user = $userModel->read(['id' => $id]);
            
            if (empty($user)) {
                $this->response->setStatusCode(404);
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Użytkownik nie został znaleziony'
                ]);
            } else {
                $this->response->setJsonContent([
                    'status' => 'success',
                    'data' => $user
                ]);
            }
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Błąd serwera'
            ]);
        }
        
        $this->response->send();
    }
    
    public function createUser(): void
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            
            if (!$data) {
                $this->response->setStatusCode(400);
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Nieprawidłowe dane JSON'
                ]);
                $this->response->send();
                return;
            }
            
            // Walidacja
            if (empty($data['name']) || empty($data['email'])) {
                $this->response->setStatusCode(400);
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Nazwa i email są wymagane'
                ]);
                $this->response->send();
                return;
            }
            
            $userModel = $this->loadModel('App\Models\UserModel');
            $success = $userModel->createUser($data);
            
            if ($success) {
                $this->response->setStatusCode(201);
                $this->response->setJsonContent([
                    'status' => 'success',
                    'data' => [
                        'id' => $userModel->getId(),
                        'message' => 'Użytkownik został utworzony'
                    ]
                ]);
            } else {
                $this->response->setStatusCode(500);
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Błąd podczas tworzenia użytkownika'
                ]);
            }
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Błąd serwera'
            ]);
        }
        
        $this->response->send();
    }
    
    private function validateApiKey(?string $apiKey): bool
    {
        // Implementacja walidacji API key
        return $apiKey === 'your-secret-api-key';
    }
}
```

## Przykład 3: System autoryzacji

### Middleware autoryzacji
```php
<?php
namespace App\Middleware;

use NimblePHP\Framework\Interfaces\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        // Sprawdzenie czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Sprawdzenie uprawnień
        $this->checkPermissions();
    }
    
    private function checkPermissions(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $userPermissions = $_SESSION['permissions'] ?? [];
        
        // Sprawdzenie uprawnień do konkretnych ścieżek
        if (strpos($requestUri, '/admin') === 0 && !in_array('admin', $userPermissions)) {
            http_response_code(403);
            echo "Brak uprawnień do dostępu do tej sekcji";
            exit;
        }
    }
}
```

### Kontroler autoryzacji
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;

class AuthController extends AbstractController
{
    public function login(): void
    {
        if ($this->request->getMethod() === 'POST') {
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            
            $userModel = $this->loadModel('App\Models\UserModel');
            $user = $userModel->findByEmail($email);
            
            if ($user && password_verify($password, $user['password'])) {
                // Logowanie udane
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Ładowanie uprawnień
                $permissionModel = $this->loadModel('App\Models\PermissionModel');
                $_SESSION['permissions'] = $permissionModel->getUserPermissions($user['id']);
                
                $this->log('Użytkownik zalogował się', 'INFO', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $this->request->getServer('REMOTE_ADDR')
                ]);
                
                $this->response->redirect('/dashboard');
            } else {
                $this->log('Nieudana próba logowania', 'WARNING', [
                    'email' => $email,
                    'ip' => $this->request->getServer('REMOTE_ADDR')
                ]);
                
                $error = "Nieprawidłowe dane logowania";
            }
        }
        
        include 'app/Views/auth/login.php';
    }
    
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Czyszczenie sesji
        session_destroy();
        
        if ($userId) {
            $this->log('Użytkownik wylogował się', 'INFO', ['user_id' => $userId]);
        }
        
        $this->response->redirect('/login');
    }
}
```

## Przykład 4: System cache

### Menedżer cache
```php
<?php
namespace App\Services;

use NimblePHP\Framework\Storage;

class CacheManager
{
    private Storage $storage;
    
    public function __construct()
    {
        $this->storage = new Storage('cache');
    }
    
    public function get(string $key): mixed
    {
        $content = $this->storage->get("{$key}.cache");
        
        if (!$content) {
            return null;
        }
        
        $data = json_decode($content, true);
        
        if (!$data || $data['expires'] < time()) {
            $this->storage->delete("{$key}.cache");
            return null;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        try {
            $this->storage->put("{$key}.cache", json_encode($data));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete(string $key): bool
    {
        return $this->storage->delete("{$key}.cache");
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

### Użycie cache w kontrolerze
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;
use App\Services\CacheManager;

class ProductController extends AbstractController
{
    private CacheManager $cache;
    
    public function __construct()
    {
        parent::__construct();
        $this->cache = new CacheManager();
    }
    
    public function index(): void
    {
        // Próba pobrania z cache
        $cacheKey = 'products_list';
        $products = $this->cache->get($cacheKey);
        
        if ($products === null) {
            // Pobranie z bazy danych
            $productModel = $this->loadModel('App\Models\ProductModel');
            $products = $productModel->readAll(['active' => 1]);
            
            // Zapisanie do cache na 1 godzinę
            $this->cache->set($cacheKey, $products, 3600);
            
            $this->log('Pobrano produkty z bazy danych', 'INFO');
        } else {
            $this->log('Pobrano produkty z cache', 'INFO');
        }
        
        $this->response->setJsonContent([
            'status' => 'success',
            'data' => $products,
            'cached' => $products !== null
        ]);
        $this->response->send();
    }
}
```

## Przykład 5: System logowania

### Niestandardowy logger
```php
<?php
namespace App\Services;

use NimblePHP\Framework\Storage;

class CustomLogger
{
    private Storage $storage;
    
    public function __construct()
    {
        $this->storage = new Storage('custom_logs');
    }
    
    public function logActivity(string $action, array $data = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'data' => $data
        ];
        
        $filename = date('Y-m-d') . '_activity.log';
        $content = json_encode($logEntry) . PHP_EOL;
        
        try {
            $this->storage->append($filename, $content);
        } catch (\Exception $e) {
            // Fallback do standardowego logowania
            error_log("Błąd logowania aktywności: " . $e->getMessage());
        }
    }
    
    public function getActivityLogs(string $date): array
    {
        $content = $this->storage->get("{$date}_activity.log");
        
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

### Użycie w kontrolerze
```php
<?php
namespace App\Controllers;

use NimblePHP\Framework\Abstracts\AbstractController;
use App\Services\CustomLogger;

class AdminController extends AbstractController
{
    private CustomLogger $activityLogger;
    
    public function __construct()
    {
        parent::__construct();
        $this->activityLogger = new CustomLogger();
    }
    
    public function deleteUser(int $id): void
    {
        $userModel = $this->loadModel('App\Models\UserModel');
        $user = $userModel->read(['id' => $id]);
        
        if (empty($user)) {
            $this->response->setStatusCode(404);
            $this->response->setJsonContent(['error' => 'Użytkownik nie został znaleziony']);
            $this->response->send();
            return;
        }
        
        $userModel->setId($id);
        $success = $userModel->delete();
        
        if ($success) {
            // Logowanie aktywności
            $this->activityLogger->logActivity('user_deleted', [
                'deleted_user_id' => $id,
                'deleted_user_email' => $user['email'],
                'deleted_by' => $_SESSION['user_id']
            ]);
            
            $this->response->setJsonContent(['status' => 'success']);
        } else {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent(['error' => 'Błąd podczas usuwania']);
        }
        
        $this->response->send();
    }
}
```

## Podsumowanie

Te przykłady pokazują różne sposoby wykorzystania frameworka NimblePHP:

1. **CRUD aplikacja** - Kompletny system zarządzania użytkownikami
2. **API REST** - Tworzenie API z autoryzacją i obsługą błędów
3. **System autoryzacji** - Middleware i kontroler logowania
4. **System cache** - Zarządzanie cache z użyciem Storage
5. **System logowania** - Niestandardowe logowanie aktywności

Framework oferuje elastyczność w tworzeniu różnych typów aplikacji, od prostych stron web po zaawansowane API i systemy z cache.