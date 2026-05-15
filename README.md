<p align="center" dir="auto">
  <img alt="GitHub License" src="https://img.shields.io/github/license/NimbleMVC/Framework">
  <img alt="Packagist Dependency Version" src="https://img.shields.io/packagist/dependency-v/nimblephp/framework/php">
  <img alt="Packagist Version" src="https://img.shields.io/packagist/v/nimblephp/framework">
</p>

# <h1 align="center">NimblePHP</h1>
NimblePHP to lekki framework skupiający się na prostocie. Zapewnia wzorzec MVC oraz automatyczne połączenie z bazą danych, dostarczając wszystko, czego potrzebujesz do rozpoczęcia pracy. Jeśli potrzebujesz dodatkowych funkcji, po prostu zainstaluj odpowiedni moduł i zacznij z niego korzystać.


**Dokumentacja** projektu dostępna jest pod linkiem: https://nimblemvc.github.io/documentation/

## Dlaczego NimblePHP?

- **Prostota** Brak modułów które dodatkowo ociążają kod
- **Rozszerzenia** Wszystkie oficjalne rozszerzenia dosępne w jednym miejsciu (pod [tym](https://packagist.org/packages/nimblephp/) linkiem)
- **Szybki start** Szybka konfiguracja i pierwsze uruchomienie

## Instalacja
Na samym początku należy zaimportować repozytorium composer
```shell
composer require nimblephp/framework
```
Następnie należy utworzyć folder public a w nim plik index.php o treści:

```php
<?php

require('../vendor/autoload.php');

$route = new \NimblePHP\Framework\Routes\Route(new \NimblePHP\Framework\Request());
$kernel = new \NimblePHP\Framework\Kernel($route);
$kernel->handle();
```
oraz plik .htaccess z zawartością:
```text
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```
Teraz należy uruchomić plik index.php, pierwsze uruchomienie spowoduje utworzenie folderów wymaganych przez projekt

## CLI
### Czyszczenie cache
```shell
php vendor/bin/nimble cache:clear
```
### Uruchamianie wersji developerskiej aplikacji
```shell
php vendor/bin/nimble serve <host:127.0.0.1> <port:8080>
```

## Event listeners
Framework udostępnia teraz `EventDispatcher`, który działa równolegle do obecnego systemu middleware.

Nowe rozszerzenia aplikacji warto budować już na eventach:

```php
use App\Event\AfterTaskAdd;
use NimblePHP\Framework\Kernel;

Kernel::getEventDispatcher()->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
    // np. webhook, mail, aktualizacja statystyk
});
```

Następnie w aplikacji można wyemitować własny event:

```php
Kernel::dispatchEvent(new AfterTaskAdd($taskId, $payload));
```

Framework dispatchuje też własne eventy m.in. dla:

- bootstrapu kernela,
- rozwiązywania requestu i routingu,
- dispatchu kontrolera,
- wysyłania response,
- logów,
- renderowania widoków,
- modeli i ORM,
- lifecycle create/update/delete modeli,
- operacji na service containerze,
- rozwiązywania serwisów z kontenera.

> `MiddlewareManager` i interfejsy middleware pozostają dostępne dla kompatybilności, ale są traktowane jako **deprecated / legacy**. Nowy kod powinien używać event listenerów.

## Benchmark Route
Ręczny benchmark routera można uruchomić poleceniem:
```shell
php bin/route-benchmark
```
Skrypt wypisuje średni koszt `reload()` dla tras statycznych i dynamicznych oraz porównanie `registerRoutes()` dla cold cache i warm cache.

## Benchmark Cache
Ręczny benchmark cache można uruchomić poleceniem:
```shell
php bin/cache-benchmark
```
Skrypt mierzy koszt podstawowych operacji `set()`, `get()`, `has()` i `clear()` dla cache plikowego.

## Współtworzenie
Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błędy, chcesz pomóc w rozwoju? Otwórz issue lub prześlij pull request.

## Pomoc
Wszelkie problemy oraz pytania należy zadawać przez zakładkę discussions w github pod linkiem:
https://github.com/NimbleMVC/Framework/discussions
