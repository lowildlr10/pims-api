<?php

namespace App\Helpers;

class RequiredFieldsValidationHelper
{
    public static function validate(array $fields, object $model): void
    {
        $missingFields = [];

        foreach ($fields as $field => $label) {
            if (empty($model->{$field})) {
                $missingFields[] = $label;
            }
        }

        if (! empty($missingFields)) {
            throw new \Exception('Please fill out the following fields first: '.implode(', ', $missingFields));
        }
    }

    public static function getMissingFields(array $fields, object $model): array
    {
        $missingFields = [];

        foreach ($fields as $field => $label) {
            if (empty($model->{$field})) {
                $missingFields[] = $label;
            }
        }

        return $missingFields;
    }
}
