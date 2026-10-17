<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

class QaReplyPolicy
{
    public function create(User $auth, QaThread $thread): bool
    {
        if ($thread->certification->status !== CertificationStatus::Published) {
            return false;
        }

        return match ($auth->role) {
            UserRole::Student => true,
            UserRole::Coach => $thread->certification
                ->coaches()
                ->whereKey($auth->id)
                ->exists(),
            default => false,
        };
    }

    public function update(User $auth, QaReply $reply): bool
    {
        return in_array($auth->role, [UserRole::Student, UserRole::Coach], true)
            && $reply->user_id === $auth->id;
    }

    public function delete(User $auth, QaReply $reply): bool
    {
        if ($auth->role === UserRole::Admin) {
            return true;
        }

        return in_array($auth->role, [UserRole::Student, UserRole::Coach], true)
            && $reply->user_id === $auth->id;
    }
}