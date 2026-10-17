<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;

class QaThreadPolicy
{
    public function viewAny(User $auth): bool
    {
        return in_array(
            $auth->role,
            [UserRole::Student, UserRole::Coach, UserRole::Admin],
            true,
        );
    }

    public function view(User $auth, QaThread $thread): bool
    {
        if ($auth->role === UserRole::Admin) {
            return true;
        }

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

    public function create(User $auth): bool
    {
        return $auth->role === UserRole::Student;
    }

    public function update(User $auth, QaThread $thread): bool
    {
        return $auth->role === UserRole::Student
            && $thread->user_id === $auth->id;
    }

    public function delete(User $auth, QaThread $thread): bool
    {
        if ($auth->role === UserRole::Admin) {
            return true;
        }

        return $auth->role === UserRole::Student
            && $thread->user_id === $auth->id
            && ! $thread->replies()->exists();
    }

    public function resolve(User $auth, QaThread $thread): bool
    {
        return $auth->role === UserRole::Student
            && $thread->user_id === $auth->id;
    }

    public function unresolve(User $auth, QaThread $thread): bool
    {
        return $this->resolve($auth, $thread);
    }
}