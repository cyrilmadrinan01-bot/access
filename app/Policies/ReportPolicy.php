<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report)
    {
        return $report->user_id === $user->id
            || $report->is_public
            || $report->shares()
                ->where('shared_to_user_id', $user->id)
                ->exists();
    }

    public function update(User $user, Report $report)
    {
        return $report->user_id === $user->id;
    }

    public function delete(User $user, Report $report)
    {
        return $report->user_id === $user->id;
    }
}