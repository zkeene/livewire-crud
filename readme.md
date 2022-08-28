## Features
 - Generate Complete Crud With Livewire Component and Blade Files
 - Create / Update / Delete Functional
 - Real Time Validation Already Added
 - Fuzzy Search Functional

## Installation

Via Composer

``` bash
composer require imritesh/livecrud
```

## Prerequisites
- Models should be in `app/Models`  directory
- Crud of only $fillable property will be generated 
```php 
protected $fillable = ['name','username'];
``` 
- Add crudInfo array to all models for generator to appropriately link models, this can be removed once crud has been generated
```php
public $crudInfo = [
    'displayField' => 'name',
    'relations' => [
        'category' => 'belongsTo',
        'availability' => 'hasMany',
    ]
];
```

## Usage
```bash
php artisan crud:make Name_Of_Your_Model
```

- This Command Will Generate Two Files
    - First Will be in `app/HttpLivewire`
    - Second Will be in `resources/views/Livewire`