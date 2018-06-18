Tekton Services
==============

Tekton Services is a project that aims to create easy to use components for integrating with popular third party services.

## YouTube
```php
$config = [
    'key' => 'aslkmdalsdnasdnasdnkdnkjasndas',
    'channel' => 'ASDMALSmlk-ASDASDASDsasd',
    'url' => 'https://www.youtube.com/user/me',
    'refresh' => 60 * 2,
    'related' => false,
    'cookie' => false,
];

$youtube = new \Tekton\Services\Youtube($config, app('cache'));
```

## Instagram
```php
$config = [
    'token' => '123123123.1239hd99dh98h9dj192jd1928',
    'user' => 'anotheruser',
    'url' => 'https://www.instagram.com/anotheruser',
    'refresh' => 60 * 2,
];

$instagram = new \Tekton\Services\Instagram($config, app('cache'));
```
