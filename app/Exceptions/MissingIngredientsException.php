<?php

namespace App\Exceptions;

use Exception;

class MissingIngredientsException extends Exception
{
    protected $missingIngredients;

    public function __construct(array $missingIngredients)
    {
        $this->missingIngredients = $missingIngredients;
        parent::__construct('Missing ingredients detected');
    }

    public function getMissingIngredients(): array
    {
        return $this->missingIngredients;
    }
}