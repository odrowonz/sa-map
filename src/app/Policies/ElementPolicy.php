<?php

namespace App\Policies;

use App\Models\Element;
use App\Models\User;

class ElementPolicy
{
    public function update(User $user, Element $element): bool
    {
        return $user->id === $element->project->user_id;
    }

    public function delete(User $user, Element $element): bool
    {
        return $user->id === $element->project->user_id;
    }
}
