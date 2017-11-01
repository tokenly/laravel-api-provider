An API service provider for Laravel.  Used by various Tokenly services.

### Installation

- `composer require tokenly/laravel-api-provider`
- Create `config/api.php` and add a file with settings like the ones below

```php
<?php

return [

    'userRepositoryClass' => 'App\Repositories\UserRepository',
    'userClass'           => 'App\Models\User',

];
```