<?php
/**
 * Created by Artyom Manchenkov
 * artyom@manchenkoff.me
 * manchenkoff.me © 2019
 */

namespace manchenkov\yii\validators;

use yii\base\DynamicModel;
use yii\base\InvalidArgumentException;
use yii\validators\Validator;

/**
 * Class ArrayValidator for apply model validation rules to array keys or array item keys
 * Supports checking **each** item of the array, **JSON** en/decode operations, and default Yii validators also with 'when' conditions
 *
 * Usage:
 *
 * ```php
 * ['array_column', ArrayValidator::class, 'json' => ArrayValidator::JSON_BOTH, 'rules' => [
 *      [['id', 'title', 'content'], 'required'],
 *      ['id', 'int'],
 *      ['title', 'trim'],
 *      ['content', 'default', 'value' => 'empty body example'],
 * ]],
 * ['users', ArrayValidator::class, 'each' => true, 'rules' => [
 *      [['id', 'login', 'active'], 'required'],
 * ]],
 * ```
 * @package Manchenkov\Yii\Validators
 */
class ArrayValidator extends Validator
{
    /** @var int Encode to JSON after validation */
    const JSON_ENCODE = 1;
    /** @var int Decode to JSON before validation */
    const JSON_DECODE = 2;
    /** @var int Use both decode/encode operations with validation */
    const JSON_BOTH = 3;

    /**
     * @var array Validation rules for array attributes
     */
    public $rules;

    /**
     * @var int JSON processing mode (use class constants)
     */
    public $json;
    /**
     * @var bool Use validation rules for each item in array
     */
    public $each = false;
    /**
     * @var array Validation errors
     * [attribute => error]
     */
    private $_errors = [];

    /**
     * Validate model array attribute by rules
     *
     * @param \yii\base\Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        // get original value from model
        $origin = $model->{$attribute};
        // prepare result array
        $validated = [];

        // if JSON mode is enabled, decode data to array
        if ($this->json == self::JSON_DECODE || $this->json == self::JSON_BOTH) {
            $origin = json_decode($origin, true);
        }

        // if the attribute contains exactly array value
        if (is_array($origin)) {
            if ($this->each) {
                // validate each value in model attribute array
                foreach ($origin as $item) {
                    $validated[] = $this->validateDynamicModel($item);
                }
            } else {
                // if array is not associative
                if (array_keys($origin)[0] !== 0) {
                    // validate value with dynamic model
                    $validated = $this->validateDynamicModel($origin);
                } else {
                    throw new InvalidArgumentException("Attribute '{$attribute}' seems to contains different objects, use 'each' property");
                }
            }
        } else {
            throw new InvalidArgumentException("Attribute '{$attribute}' must be an instance of array");
        }

        // encode array to JSON if enabled
        if ($this->json == self::JSON_ENCODE || $this->json == self::JSON_BOTH) {
            $validated = json_encode($validated);
        }

        // reassign value to the model or add some validation errors
        if (empty($this->_errors)) {
            $model->{$attribute} = $validated;
        } else {
            foreach ($this->_errors as $errorAttribute => $errorMessage) {
                $model->addError($attribute, $errorMessage);
            }
        }
    }

    /**
     * Validation process of DynamicModel
     *
     * @param array $properties
     *
     * @return array
     */
    private function validateDynamicModel(array $properties)
    {
        // instantiate new model with necessary attributes
        $model = new DynamicModel($properties);

        // assign dynamic rules
        foreach ($this->rules as $rule) {
            $attributes = array_shift($rule);
            $validator = array_shift($rule);
            $options = $rule ?: null;

            if (!is_array($attributes)) {
                $attributes = [$attributes];
            }

            foreach ($attributes as $attr) {
                $model->addRule($attr, $validator, $options);

                // define an attribute if required but doesn't exist yet
                if (!isset($model->{$attr})) {
                    $required = isset($options['when']) ? $options['when']() : true;

                    if ($required) {
                        $model->defineAttribute($attr, null);
                    }
                }
            }
        }

        if ($model->validate()) {
            // return changes to the root model
            return $model->toArray();
        } else {
            // or set up some errors with empty changes array
            $this->_errors = array_merge(
                $this->_errors, $model->errors
            );

            return [];
        }
    }
}