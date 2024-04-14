# Flysystem adapter for the Yandex Disk API

## Installation

### Composer package installation:

```bash
composer require impressiveweb/yandex-disk-flysystem-adapter
```

## Adapter creation and usage.

```php
use ImpressiveWeb\YandexDisk\Client;
use League\Flysystem\Filesystem;
use ImpressiveWeb\Flysystem\YandexDiskAdapter;
use League\Flysystem\StorageAttributes;

// 1. Create a new client.
$client = new Client($accessToken);

// 2. Create an adapter. 
$adapter = new YandexDiskAdapter($client);

// 3. Create a filesystem.
$filesystem = new Filesystem($adapter);

// Get a list of directories in the root of your Application or Disk. 
$items = $filesystem
    ->listContents('/')
    ->filter(fn(StorageAttributes $attributes) => $attributes->isDir())
    ->map(fn(StorageAttributes $attributes) => $attributes->path())
    ->toArray()
;

// Get a list of files in a root of your Application or Disk. 
$items = $filesystem
    ->listContents('/')
    ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
    ->map(fn(StorageAttributes $attributes) => $attributes->path())
    ->toArray()
;

```

## Other methods can be explored here:  https://flysystem.thephpleague.com/docs/usage/filesystem-api/