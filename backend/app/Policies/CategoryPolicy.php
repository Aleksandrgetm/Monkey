<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $record): bool
    {
        return $user->status === 1 && $user->id === $record->user_id;
    }

    public function update(User $user, Category $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Category $record): bool
    {
        return $this->view($user, $record);
    }

    public function moderate(User $user, Category $record): bool
    {
        return $user->isAdmin();
    }
}
