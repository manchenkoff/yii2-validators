# Yii 2 Model validators

Collection of Yii2 Model data validators

## Installation 

You have to run following command to add a dependency to your project

```bash
composer require manchenkov/yii2-validators
```

or you can add this line to `require` section of `composer.json`

```
"manchenkov/yii2-validators": "*"
```

## Validators

- ArrayValidator (apply different rules to each item in array)

## Usage

#### ArrayValidator 
Use ArrayValidator for apply model validation rules to array keys or array item keys

Supports checking **each** item of the array, **JSON** en/decode operations, and default Yii 2 validators also with 'when' conditions

Examples:

```php
public function rules()
{
    return [
        ['array_column', ArrayValidator::class, 'json' => ArrayValidator::JSON_BOTH, 'rules' => [
          [['id', 'title', 'content'], 'required'],
          ['id', 'int'],
          ['title', 'trim'],
          ['content', 'default', 'value' => 'empty body example'],
        ]],
        
        ['users', ArrayValidator::class, 'each' => true, 'rules' => [
          [['id', 'login', 'active'], 'required'],
        ]],
    ];
}
```
 
see `src/ArrayValidator.php` for more details