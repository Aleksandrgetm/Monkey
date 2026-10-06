<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $record): bool
    {
        return $user->status === 1 && $user->id === $record->user_id;
    }

    public function update(User $user, Product $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Product $record): bool
    {
        return $this->view($user, $record);
    }

    public function moderate(User $user, Product $record): bool
    {
        return $user->isAdmin();
    }
}
