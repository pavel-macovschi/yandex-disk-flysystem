# Flysystem adapter for the Yandex Disk API

## Installation

### Package installation via composer:

```bash
composer require impressiveweb/yandex-disk-flysystem-adapter
```

## Adapter creation and usage.

```php
use ImpressiveWeb\YandexDisk\Client;
use League\Flysystem\Filesystem;
use ImpressiveWeb\Flysystem\YandexDiskAdapter;


// 1. Create a new client.
$client = new Client($accessToken);

// 2. Create an adapter. 
$adapter = new YandexDiskAdapter($client);

// 3. Create a filesystem.
$filesystem = new Filesystem($adapter);

// Listing contents in the Test directory. 
$items = $filesystem
    ->listContents('Test directory')
    ->map(fn(StorageAttributes $attributes) => $attributes->path())
    ->toArray()
;

```

## Other methods can be explored here:  https://flysystem.thephpleague.com/docs/usage/filesystem-api/