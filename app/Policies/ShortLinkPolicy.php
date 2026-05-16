<?php

namespace App\Policies;

use App\Models\ShortLink;
use App\Models\User;

class ShortLinkPolicy
{
    public function view(User $user, ShortLink $link): bool
    {
        return $user->id === $link->user_id || $user->isAdmin();
    }

    public function update(User $user, ShortLink $link): bool
    {
        return $user->id === $link->user_id || $user->isAdmin();
    }

    public function delete(User $user, ShortLink $link): bool
    {
        return $user->id === $link->user_id || $user->isAdmin();
    }
}
