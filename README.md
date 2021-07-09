# PHP simple validation class

## Installation

```sh
composer install mozafar/php-validation
```

## Usage

Simply add class and pass `data` and `rules`:

```php
use Mozafar\Validation\Validation;

$data = Validation::make($_POST, [
    'name' => ['required', 'string'],
    'email' => ['required', 'email']
])->throws()->validated();
```
If there are any errors it throws `ValidationException` and list of errors available in `$exception->errors()` like this:
```php
use Mozafar\Validation\Validation;
use Mozafar\Validation\ValidationException;

try {
    Validation::make($_POST, [
        'name' => ['required', 'string'],
        'email' => ['required', 'email']
    ])->throws()->validated();

    return $data;
} catch (ValidationException $e) {
    return $e->errors();
}

```