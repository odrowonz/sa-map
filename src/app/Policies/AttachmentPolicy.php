<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->id === $attachment->project->user_id;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->id === $attachment->project->user_id;
    }
}
