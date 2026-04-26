<?php

namespace App\Policies;

use App\Models\NjkTemplate;
use App\Models\User;

class NjkTemplatePolicy
{
    public function view(User $user, NjkTemplate $njkTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $njkTemplate->is_system || $njkTemplate->user_id === $user->id;
    }

    public function update(User $user, NjkTemplate $njkTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ! $njkTemplate->is_system && $njkTemplate->user_id === $user->id;
    }

    public function delete(User $user, NjkTemplate $njkTemplate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ! $njkTemplate->is_system && $njkTemplate->user_id === $user->id;
    }
}
