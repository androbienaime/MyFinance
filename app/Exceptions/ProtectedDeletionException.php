<?php
// app/Exceptions/ProtectedDeletionException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ProtectedDeletionException extends Exception
{
    public function __construct(
        public readonly Model $model,
        string $message
    ) {
        parent::__construct($message);
    }

    public static function forModel(Model&\App\Contracts\Deletable $model): static
    {
        return new static($model, $model->getDeletionGuardMessage());
    }
}