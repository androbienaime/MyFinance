<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Core\Role;
use App\Models\Core\PermissionLevelRequirement;

class TransferAndAuditPermissionsSeeder extends Seeder
{
    /**
     * name => min_level_to_assign
     */
    protected array $permissions = [
        // Virements compte a compte (guichet + P2P)
        'transactions.transfer' => 10,

        // Historique des attributions de roles (audit)
        'role_assignment_logs.view' => 40,
        'role_assignment_logs.view_any' => 40,

        // Configuration des niveaux requis par permission
        'permission_level_requirements.view' => 90,
        'permission_level_requirements.view_any' => 90,
        'permission_level_requirements.update' => 90,

        // Paliers de frais P2P - configuration financiere sensible
        'p2p_transfer_fee_tiers.view_any' => 60,
        'p2p_transfer_fee_tiers.view' => 60,
        'p2p_transfer_fee_tiers.create' => 70,
        'p2p_transfer_fee_tiers.update' => 70,
        'p2p_transfer_fee_tiers.delete' => 80,

        // Limites P2P (montant/nombre quotidien, mensuel) - meme sensibilite
        'p2p_transfer_limits.view_any' => 60,
        'p2p_transfer_limits.view' => 60,
        'p2p_transfer_limits.create' => 70,
        'p2p_transfer_limits.update' => 70,
        'p2p_transfer_limits.delete' => 80,

        // Demandes de virement P2P (OTP, en attente/confirme/expire...) -
        // donnees generees par le systeme, jamais creees/modifiees/
        // supprimees manuellement, uniquement consultables pour audit
        // et support client.
        'p2p_transfer_requests.view_any' => 40,
        'p2p_transfer_requests.view' => 40,

        // Settings
        'settings.view_any' => 70,
        'settings.view' => 70,
        'settings.create' => 90,
        'settings.update' => 90,
        'settings.delete' => 100,
    ];

    public function run(): void
    {
        $allPermissions = collect();

        foreach ($this->permissions as $name => $minLevel) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );

            PermissionLevelRequirement::updateOrCreate(
                ['permission_id' => $permission->id],
                ['min_level_to_assign' => $minLevel]
            );

            $allPermissions->push($permission);

            $this->command?->info("Permission prete : {$name} (min_level_to_assign = {$minLevel})");
        }

        $this->resyncSuperAdmin();
    }

    /**
     * Le role super_admin (level 100, protege) doit toujours detenir
     * l'integralite des permissions existantes en base - on resynchronise
     * TOUTES les permissions plutot que de ne rajouter que les nouvelles,
     * pour garantir qu'aucun oubli passe (present ou futur) ne laisse
     * super_admin incomplet.
     */
    protected function resyncSuperAdmin(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();

        if (! $superAdmin) {
            $this->command?->warn('Role super_admin introuvable - resynchronisation ignoree. Lancez myfinance:make-user pour le creer.');
            return;
        }

        $superAdmin->syncPermissions(Permission::all());

        $this->command?->info(
            'super_admin resynchronise avec la totalite des ' . Permission::count() . ' permissions existantes.'
        );
    }
}