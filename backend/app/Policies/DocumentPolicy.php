<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $record): bool
    {
        return $user->status === 1 && $user->id === $record->user_id;
    }

    public function update(User $user, Document $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Document $record): bool
    {
        return $this->view($user, $record);
    }

    public function moderate(User $user, Document $record): bool
    {
        return $user->isAdmin();
    }
}
