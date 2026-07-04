<?php

namespace App\Exceptions;

use Exception;

/**
 * Levee pour toute regle metier qui empeche une transaction d'aboutir
 * (compte inactif, solde insuffisant, doublon...). Le message est
 * ecrit en langage clair car il sera affiche tel quel a l'utilisateur.
 */
class TransactionRejectedException extends Exception
{
    //
}
