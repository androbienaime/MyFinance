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