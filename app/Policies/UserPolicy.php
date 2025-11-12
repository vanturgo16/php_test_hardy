<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function edit(User $authUser, User $targetUser): bool
    {
        if ($authUser->role === 'administrator') return true;
        if ($authUser->role === 'manager' && $targetUser->role === 'user') return true;
        return $authUser->id === $targetUser->id;
    }
}
