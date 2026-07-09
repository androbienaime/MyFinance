<?php
// app/Contracts/Deletable.php

namespace App\Contracts;

interface Deletable
{
    /**
     * Détermine si le modèle peut être supprimé.
     */
    public function canBeDeleted(): bool;

    /**
     * Message expliquant pourquoi la suppression est bloquée.
     */
    public function getDeletionGuardMessage(): string;
}