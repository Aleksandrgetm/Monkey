<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function view(User $user, Notification $record): bool
    {
        return $user->status === 1 && $user->id === $record->user_id;
    }

    public function update(User $user, Notification $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Notification $record): bool
    {
        return $this->view($user, $record);
    }

    public function moderate(User $user, Notification $record): bool
    {
        return $user->isAdmin();
    }
}
