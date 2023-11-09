# Laravel Repositories

Laravel Repositories is used to abstract the data layer, making our application more flexible to maintain.

[![Latest Stable Version](http://poser.pugx.org/dovutuan/laracom/v)](https://packagist.org/packages/dovutuan/laracom) 
[![Total Downloads](http://poser.pugx.org/dovutuan/laracom/downloads)](https://packagist.org/packages/dovutuan/laracom) 
[![Latest Unstable Version](http://poser.pugx.org/dovutuan/laracom/v/unstable)](https://packagist.org/packages/dovutuan/laracom) 
[![License](http://poser.pugx.org/dovutuan/laracom/license)](https://packagist.org/packages/dovutuan/laracom) 
[![PHP Version Require](http://poser.pugx.org/dovutuan/laracom/require/php)](https://packagist.org/packages/dovutuan/laracom)

## Installation

### Composer

Execute the following command to get the latest version of the package:
```terminal
composer require dovutuan/laracom
```

### Laravel

Publish Configuration
```shell
php artisan vendor:publish --tag=laracom
```

## Methods

### Dovutuan\Laracom\RepositoryInterface

-
-
-

## Usage

### Create a Repository
```terminal
php artisan make:repository UserRepository
```

### Create a Repository and Service
```terminal
php artisan make:repository UserRepository --ser
```

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Dovutuan\Laracom\DomRepository\BaseRepository;

class UserRepository extends BaseRepository
{
    public function model(): string
    {
        return User::class;
    }
}
```

### Create a Service
```terminal
php artisan make:service UserService
```

### Create a Service and Repository
```terminal
php artisan make:service UserService --repo
```

```php
<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }
}
```

### Declare key search repository
```php
<?php

namespace App\Repositories;

use App\Models\User;
use Dovutuan\Laracom\DomRepository\BaseRepository;

class UserRepository extends BaseRepository
{
    protected array $base_search = [
        'id' => [
            ['column' => 'id', 'operator' => OPERATOR_EQUAL]
        ],
        'keyword' => [
            ['column' => 'name', 'operator' => OPERATOR_LIKE, 'boolean' => OPERATOR_BOOLEAN_OR],
            ['column' => 'email', 'operator' => OPERATOR_LIKE, 'boolean' => OPERATOR_BOOLEAN_AND],
        ],
        'name' => [
            ['column' => 'name', 'operator' => OPERATOR_LIKE],
        ]
    ];

    public function model(): string
    {
        return User::class;
    }
}
```

### Types of operators
```php
const OPERATOR_EQUAL = '=';
const OPERATOR_NOT_EQUAL = '<>';
const OPERATOR_LIKE = '%%';
const OPERATOR_BEFORE_LIKE = '%_';
const OPERATOR_AFTER_LIKE = '_%';
const OPERATOR_NOT_LIKE = '!%%';
const OPERATOR_GREATER = '>';
const OPERATOR_GREATER_EQUAL = '>=';
const OPERATOR_LESS = '<';
const OPERATOR_LES_EQUAL = '<=';
const OPERATOR_IN = 'in';
const OPERATOR_NOT_IN = '!in';
const OPERATOR_NULL = 'null';
const OPERATOR_NOT_NULL = '!null';
const OPERATOR_DATE = 'date';
const OPERATOR_DATE_NOT_EQUAL = '!date';
const OPERATOR_DATE_GREATER = '>date';
const OPERATOR_DATE_GREATER_EQUAL = '>=date';
const OPERATOR_DATE_LESS = '<date';
const OPERATOR_DATE_LESS_EQUAL = '<=date';
const OPERATOR_JSON = '{}';
const OPERATOR_JSON_NOT_CONTAIN = '!{}';
```

### Types of boolean
```php
const OPERATOR_BOOLEAN_AND = 'and';
const OPERATOR_BOOLEAN_OR = 'or';
```

### Use methods

Find result by id in Repository
```php
$user = $this->userRepository->find(123);
```

Find result by conditions in Repository
```php
$user = $this->userRepository->findByConditions(['id' => 123]);
```

Create new entry in Repository
```php
$user = $this->userRepository->create(Input::all());
```

Update entry in Repository
```php
$user = $this->userRepository->update(123, Input::all());
```

Update entry by conditions in Repository
```php
$user = $this->userRepository->updateByConditions(['id' => 123], Input::all());
```

Delete entry in Repository
```php
$user = $this->userRepository->delete(123);
```

Delete entry by conditions in Repository
```php
$user = $this->userRepository->deleteByConditions(['id' => 123]);
```

Count entry by conditions in Repository
```php
$user = $this->userRepository->count(['id' => 123]);
```

Paginate entry by conditions in Repository
```php
$user = $this->userRepository->paginate(['id' => 123]);
```

All entry by conditions in Repository
```php
$user = $this->userRepository->all(['id' => 123]);
```

Insert entry by conditions in Repository
```php
$user = $this->userRepository->inserrt([['name' => 'Hello'], ['name' => 'Hi']]);
```

Update or create entry by conditions in Repository
```php
$user = $this->userRepository->updateOrCreate(['id' => 123], ['name' => 'Hello']);
```

Upsert entry by conditions in Repository
```php
$user = $this->userRepository->update(['id' => 123, 'name' => 'Hello'], ['id'], ['name']);
```

All and pluck entry by conditions in Repository
```php
$user = $this->userRepository->allAndPluck('name', 'id', ['id' => 123]);
```