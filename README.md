# MyFinance (LTFINANCE v2)

Application de gestion financiere multi-succursale — depots, retraits, paiements,
comptes a cases (sol/tanti), gestion des roles et permissions dynamiques.
Reecriture complete sur **Laravel 12 + Filament 5.6**, avec une architecture
pensee des le depart pour la securite et la tracabilite.

## Stack technique

- **Laravel 12**
- **Filament 5.6** (panel admin, Resources, Custom Pages)
- **spatie/laravel-permission** — roles et permissions entierement dynamiques
- **Livewire 3 / Alpine.js** — interactivite (grille de cases du livret, formulaires)
- **MySQL** (InnoDB requis — voir section Securite)

## Installation

Une seule commande gere tout : cle applicative, migrations, seeders, assets
Filament, et creation du premier utilisateur.

```bash
composer install
npm update
php artisan myfinance:install
```

Options utiles :

```bash
# Reinitialise completement la base avant d'installer (developpement uniquement)
php artisan myfinance:install --fresh

# Installation automatisee (CI/deploiement), sans prompt de creation d'utilisateur
php artisan myfinance:install --skip-user
```

A la fin de l'installation, connectez-vous sur `/adminFinance` avec l'utilisateur
cree pendant le prompt. La 2FA (application TOTP) sera exigee des la premiere
connexion.

```bash
composer run dev
```

### Ajouter un utilisateur/employe ensuite

`php artisan myfinance:make-user` cree a la fois le compte de connexion **et**
la fiche employe associee (contrairement a `filament:make-user`, qui ne cree
que le compte). Utilisable seule, en dehors de l'installation initiale :

```bash
php artisan myfinance:make-user
```

## Architecture — points cles

### Visibilite hybride (succursales)

- **Clients, comptes, transactions** : visibles par **toutes** les succursales,
  mais tries pour faire remonter en premier ceux de la succursale/l'employe
  courant (`scopeOrderByRelevanceTo` sur `Customer` et `Transaction`).
- **Succursales, employes, seuils d'approbation** : filtres strictement par
  succursale pour les roles non-siege (`EmployeeResource::getEloquentQuery()`),
  ou reserves entierement au siege (`Branch`, `ApprovalThreshold`).

Ce choix est deliberement different du multi-tenancy natif de Filament, qui
aurait scope *tous* les Resources du panel de la meme facon.

### Roles et permissions 100% dynamiques

Aucun role n'est code en dur. La seule chose fixe dans le code est la liste
des **permissions** (`transactions.approve`, `accounts.toggle-active`,
`system.full-access`, etc.) — les **roles** qui les regroupent sont creables
et modifiables librement depuis `/admin/roles`, avec une interface de
permissions groupees par domaine (inspiree de Filament Shield, mais adaptee a
notre convention de nommage).

Le role "siege central" n'est pas un nom fixe (`SuperAdmin`) mais une
permission (`system.full-access`) — n'importe quel role portant cette
permission obtient l'acces complet via `User::isHeadOffice()`.

### State machine des transactions

```
pending -> approved -> completed
        -> rejected
```

Chaque depot/retrait/paiement passe par une des 3 Actions dediees
(`DepositAction`, `WithdrawAction`, `PaymentAction`). Si le montant depasse un
seuil configure (`ApprovalThresholdResource`), la transaction reste `pending`
et **le solde ne bouge pas** tant que le nombre de niveaux d'approbation requis
n'est pas atteint (`ApproveTransactionAction`). Toute approbation/rejet est
tracee (`transaction_approvals` : qui, quand, quel niveau, quel commentaire).

### Comptes a cases (livret sol/tanti)

Pour les types de compte avec `active_case_payments = true`, le depot se fait
via une grille de cases numerotees `1` a `duration * 30`. Le prix d'une case
est **progressif** : `numero_de_la_case x prix_unitaire`, jamais un prix fixe
par case. Le montant final est **toujours recalcule cote serveur**
(`DepositAction::assertValidTagsAndComputeAmount`) a partir des cases
selectionnees — jamais fait confiance a un calcul cote client, meme si
l'interface (Alpine.js) affiche deja un total estime pour l'ergonomie.

# Intégration WhatsApp — MyFinance

Code complet pour envoyer des notifications WhatsApp automatiques (confirmation de transaction, alerte de dépôt/retrait, etc.) via l'API WhatsApp Cloud de Meta.

## 1. Configuration Meta (une seule fois)

1. Va sur https://developers.facebook.com/ et crée une app de type "Business"
2. Ajoute le produit "WhatsApp"
3. Dans le tableau de bord WhatsApp > Getting Started, note :
   - `Phone number ID`
   - `Temporary access token` (valide 24h — génère un token permanent ensuite via un System User)
4. Pour un token permanent : Business Settings > System Users > créer un system user > générer un token avec la permission `whatsapp_business_messaging`
5. Crée tes templates de message dans Business Manager > WhatsApp Manager > Message Templates (obligatoire pour les notifs "à froid", donc pour presque tout ce qui est automatique). Exemple de template `transaction_confirmee` :

   ```
   Bonjour {{1}}, votre {{2}} de {{3}} sur le compte {{3}} a été confirmée.
   Nouveau solde : {{5}}.
   ```

   Les templates doivent être approuvés par Meta (généralement quelques minutes à quelques heures).

## 2. .env

```env
WHATSAPP_PHONE_NUMBER_ID=xxxxxxxxxxxxxxx
WHATSAPP_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WHATSAPP_API_VERSION=v20.0
```



## 5. Utilisation dans MyFinance(Tout fonctionne déjà très bien. Après chaque transaction, le système enverra automatiquement le message. Cependant, vous pouvez utiliser ces méthodes pour personnaliser l’envoi ou effectuer un renvoi manuel.)

### Depuis une Action existante (ex: après confirmation de dépôt)

```php
use App\Notifications\TransactionConfirmed;

class DepositAction
{
    public function execute(Account $account, float $amount): Transaction
    {
        $transaction = DB::transaction(function () use ($account, $amount) {
            // ... ta logique existante de dépôt (cases-grid, etc.)
            $transaction = Transaction::create([...]);
            $account->increment('balance', $amount);
            return $transaction;
        });

        // Envoi automatique, ne bloque pas la transaction si WhatsApp échoue
        $account->customer->notify(new TransactionConfirmed($transaction));

        return $transaction;
    }
}
```

Puis enregistre l'observer dans `app/Providers/AppServiceProvider.php` :

```php
public function boot(): void
{
    Transaction::observe(TransactionObserver::class);
}
```

### Depuis une action Filament (ex: bouton "Renvoyer la confirmation")

```php
use Filament\Tables\Actions\Action;

Action::make('resend_whatsapp')
    ->label('Renvoyer par WhatsApp')
    ->icon('heroicon-o-chat-bubble-left-right')
    ->action(function (Transaction $record) {
        $record->account->customer->notify(new TransactionConfirmed($record));
        Notification::make()
            ->title('Message WhatsApp envoyé')
            ->success()
            ->send();
    });
```

## 6. Files d'attente (queue)

`TransactionConfirmed` implémente déjà `ShouldQueue` — les envois ne ralentissent pas tes requêtes. Assure-toi que ton worker tourne :

```bash
php artisan queue:work
```

En production, utilise Supervisor pour garder `queue:work` actif en permanence.

## 7. Test rapide en tinker

```bash
php artisan tinker
```

```php
app(App\Services\WhatsAppService::class)->sendTemplate(
    '509XXXXXXXX',
    'transaction_confirmee',
    ['Dépôt', '1 500 HTG', 'A00123', '25 000 HTG']
);
```

## Notes importantes

- **Numéro de test Meta** : gratuit, mais limité à 5 destinataires pré-enregistrés dans le dashboard. Pour envoyer à n'importe quel client, il faut passer en production (vérification business Meta requise).
- **Templates obligatoires** hors fenêtre de 24h — c'est le cas pour ~100% des notifications automatiques d'une app financière.
- **Coût** : les conversations "utility" (comme une confirmation de transaction) sont peu coûteuses sur l'API Meta, mais vérifie la tarification pour Haïti sur https://developers.facebook.com/docs/whatsapp/pricing
- **Multi-branch** : si tu veux des numéros WhatsApp différents par branche (`BranchScope`), il suffit de stocker plusieurs `phone_number_id` par branche en base et de les injecter dynamiquement dans `WhatsAppService` au lieu de tout lire depuis `config()`.

### Securite — recapitulatif des protections en place

| Risque | Protection |
|---|---|
| Race condition sur le solde (deux operations simultanees) | `lockForUpdate()` dans chaque Action, transaction DB englobante |
| Collision de code de compte/transaction | Insertion optimiste + retry, contrainte `UNIQUE` en base en dernier filet |
| Autorisation absente ou incoherente | Policy complete par modele sensible, permissions Spatie granulaires |
| Auto-approbation d'une transaction | Verifiee en dur dans `ApproveTransactionAction`, non contournable par la Policy |
| Escalade de privileges (attribuer un role siege sans en etre) | `AssignRoleToUserAction` : verifie cote serveur, jamais fait confiance a un champ de formulaire cache |
| Double paiement d'une case du livret | Contrainte `UNIQUE(account_id, tags)` + revalidation depuis la base a chaque depot |
| Compte desactive qui continue d'operer | Verifie dans chaque Action ET dans le middleware de connexion (`EnsureUserIsActive`) |
| Suppression d'une transaction completee | Le mouvement de solde est inverse automatiquement, action tracee (`deleted_by`, `deletion_reason`), reservee au siege |

**Important pour la base de donnees** : le moteur doit etre **InnoDB**, jamais
MyISAM — `lockForUpdate()` ne fonctionne que si le moteur supporte reellement
le verrouillage de ligne.

## Structure du projet

```
app/
  Actions/              # Deposit, Withdraw, Payment, ApproveTransaction,
                         # DeleteTransaction, AssignRoleToUser, SyncUserPermissions
  Console/Commands/      # myfinance:install, myfinance:make-user
  Enums/                 # TransactionType, TransactionStatus
  Exceptions/            # TransactionRejectedException
  Filament/
    Pages/               # DepositPage, WithdrawPage, PaymentPage (form + tableau)
    Resources/           # Un dossier par entite (Schemas/Tables/Pages separes)
  Models/Core/           # Person, Employee, Customer, Account, Transaction, ...
  Policies/              # Une Policy par modele sensible
database/
  migrations/
  seeders/               # RolesAndPermissionsSeeder, ApprovalThresholdSeeder, DatabaseSeeder
resources/
  js/filament/           # case-grid.js (enregistre via FilamentAsset::register)
  views/filament/        # Vues Blade des pages et champs personnalises
```

## Notes de developpement

- **Filament v4/v5** : les Resources separent `XResource.php` (config des
  pages/navigation), `Schemas/XForm.php` (formulaire), `Tables/XTable.php`
  (tableau), `Pages/` (Create/Edit/List) — convention suivie partout dans ce
  projet.
- **JS personnalise dans le panel** : ne jamais compter sur le `resources/js/app.js`
  / Vite standard de Laravel — Filament a son propre systeme d'assets
  (`FilamentAsset::register()` + `php artisan filament:assets`), completement
  separe. Voir `AppServiceProvider::boot()`.
- **Getters vs methodes dans les composants Alpine.js** : eviter les `get x()`
  dans un objet destine a etre fusionne via l'operateur spread (`{...obj}`) —
  le spread evalue les getters immediatement, avant que les autres proprietes
  de l'objet final n'existent. Preferer des methodes normales (`x()`).