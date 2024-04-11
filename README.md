# Flysystem adapter for Yandex Disk API

## Installation

### Package installation via composer:

```bash
composer require impressiveweb/yandex-disk-flysystem-adapter
```

## Usage

### Go to https://yandex.ru/dev/disk/poligon and click on a button to get OAuth token.

```php
use ImpressiveWeb\YandexDisk\Client;

// Access token.
$accessToken = 'xxxxxxxxxxxxxxxxxxx';

// Client init with an access token and default path prefix to a whole disk:/.
$client = new Client($accessToken);
```

### Go to https://oauth.yandex.ru/client/new create your first App and add necessary permissions.

### After getting client_id and client_secret you can use it for a client initialization.

```php
// Auth credentials.
$credentials = [
    'client_id' => 'xxxxxxxxxxxxxxxxxxx',
    'client_secret' => 'xxxxxxxxxxxxxxxxxxx',
];

// Default value for path prefix is set to disk:/.
$client = new Client($credentials);

// If you create you first Application, use path of your Application.
$pathPrefix = 'disk:/Applications/YourApp'

// Client init with credentials and access to your Application.
$client = new Client($credentials, $pathPrefix);
```

## Adapter creation and usage.

```php
use ImpressiveWeb\YandexDisk\Client;
use League\Flysystem\Filesystem;





```