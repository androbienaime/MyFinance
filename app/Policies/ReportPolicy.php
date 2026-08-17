<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Core\Report;
use App\Models\User;

class ReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'delete') {
            return false;
        }

        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('reports.view_any');
    }

    public function view(User $user, Report $report): bool
    {
        if ($report->status === ReportStatus::Draft) {
            return $report->employee_id === $user->employee?->id
                || $user->can('reports.view_all_drafts'); // admins/superviseurs, permission dediee
        }

        if ($user->can('reports.view')) {
            return true;
        }

        return $report->employee_id === $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    /**
     * Un rapport automatique n'est jamais modifiable a la main. Un rapport
     * manuel n'est modifiable que par son auteur, tant qu'il n'a pas
     * encore ete revu.
     */
    public function update(User $user, Report $report): bool
    {
        if ($report->type !== ReportType::Manual || $report->status !== ReportStatus::Pending) {
            return false;
        }

        return $report->employee_id === $user->employee?->id;
    }

    public function review(User $user, Report $report): bool
    {
        return $report->status === ReportStatus::Pending && $user->can('reports.manage');
    }

    public function archive(User $user, Report $report): bool
    {
        return $report->status !== ReportStatus::Archived && $user->can('reports.manage');
    }

    /**
     * Un rapport, meme manuel, reste une piece d'audit une fois cree :
     * jamais supprimable, pas meme par le siege (before() l'exclut deja).
     */
    public function delete(User $user, Report $report): bool
    {
        return false;
    }
}