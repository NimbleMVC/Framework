[//]: # (Dokumentacja: https://nimblemvc.github.io/documentation/)

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

$route = new \Nimblephp\framework\Route(new \Nimblephp\framework\Request());
$kernel = new \Nimblephp\framework\Kernel($route);
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

## Współtworzenie
Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błędy, chcesz pomóc w rozwoju? Otwórz issue lub prześlij pull request.

## Pomoc
Wszelkie problemy oraz pytania należy zadawać przez zakładkę discussions w github pod linkiem:
https://github.com/NimbleMVC/Framework/discussions