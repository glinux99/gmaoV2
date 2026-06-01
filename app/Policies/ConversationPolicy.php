<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Conversation;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation)
    {
        return $conversation->users->contains($user);
    }
    public function send(User $user, Conversation $conversation)
    {
        return $conversation->users->contains($user);
    }
    public function update(User $user, Conversation $conversation)
    {
        return $conversation->type === 'channel' && $conversation->created_by === $user->id;
    }
    public function delete(User $user, Conversation $conversation)
    {
        return $conversation->type === 'channel' && $conversation->created_by === $user->id;
    }
}
