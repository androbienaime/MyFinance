<?php

namespace App\Console\Commands;

use App\Models\Core\Branch;
use App\Models\Core\Employee;
use App\Models\User;
use App\Models\Core\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeEmployeeUserCommand extends Command
{
    protected $signature = 'myfinance:make-user
        {--name= : Nom complet}
        {--email= : Email de connexion}
        {--password= : Mot de passe (genere aleatoirement si omis)}
        {--branch= : Code de la succursale existante}
        {--role= : Nom du role a attribuer (optionnel)}';

    protected $description = 'Cree un utilisateur ET sa fiche employe associee (contrairement a filament:make-user qui ne cree que le compte de connexion)';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nom complet', required: true);
        $email = $this->option('email') ?: text('Email de connexion', required: true, validate: fn ($v) => User::where('email', $v)->exists() ? 'Cet email est deja utilise.' : null);
        $plainPassword = $this->option('password') ?: password(
            'Mot de passe',
            required: true,
            validate: fn ($v) => Validator::make(['password' => $v], ['password' => Password::default()])->fails()
                ? 'Mot de passe trop faible (8 caracteres minimum recommandes, mix lettres/chiffres).'
                : null,
        );
        $branch = $this->resolveBranch();
        $role = $this->resolveRole();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        [$firstname, $lastname] = $this->splitName($name);

        Employee::create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        if ($role) {
            // Assignation directe ici, sans passer par AssignRoleToUserAction :
            // cette commande tourne en contexte console (acces serveur deja
            // privilegie, pas d'utilisateur Filament "acteur" a proteger
            // contre lui-meme), contrairement au formulaire employe du panel.
            $user->assignRole($role);
        }

        $this->newLine();
        $this->info("Utilisateur cree : {$email} (succursale : {$branch->name})");

        if ($role) {
            $this->info("Role attribue : {$role->name}");
        }

        $this->warn('L\'utilisateur devra changer son mot de passe et configurer la 2FA a sa premiere connexion.');

        return self::SUCCESS;
    }

    private function resolveBranch(): Branch
    {
        if ($code = $this->option('branch')) {
            return Branch::where('code', $code)->firstOrFail();
        }

        $branches = Branch::query()->orderBy('name')->get();

        if ($branches->isEmpty()) {
            $this->warn('Aucune succursale n\'existe encore. Creons-en une.');

            return Branch::create([
                'code' => text('Code de la succursale', default: 'SIEGE-01', required: true),
                'name' => text('Nom de la succursale', default: 'Siege central', required: true),
                'is_active' => true,
            ]);
        }

        $choice = select(
            label: 'Succursale de rattachement',
            options: $branches->pluck('name', 'code')->all(),
        );

        return $branches->firstWhere('code', $choice);
    }

    private function resolveRole(): ?Role
    {
        if ($name = $this->option('role')) {
            return Role::where('name', $name)->first();
        }

        $roles = Role::query()->orderBy('name')->get();

        if ($roles->isEmpty()) {
            return null;
        }

        if (! confirm('Attribuer un role a cet utilisateur maintenant ?', default: true)) {
            return null;
        }

        $choice = select(
            label: 'Role a attribuer',
            options: $roles->pluck('name', 'id')->all(),
        );

        return $roles->firstWhere('id', $choice);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}