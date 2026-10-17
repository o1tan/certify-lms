<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class IndexAction
{
    public function __invoke(User $user, array $filters): LengthAwarePaginator
    {
        return QaThread::query()
            ->with(['user', 'certification'])
            ->withCount('replies')
            ->when(
                $user->role === UserRole::Student,
                fn ($query) => $query->whereHas(
                    'certification',
                    fn ($q) => $q->where('status', CertificationStatus::Published->value),
                ),
            )
            ->when(
                $user->role === UserRole::Coach,
                fn ($query) => $query
                    ->whereHas(
                        'certification',
                        fn ($q) => $q
                            ->where('status', CertificationStatus::Published->value)
                            ->assignedTo($user),
                    ),
            )
            ->when(
                ($filters['status'] ?? null) === 'unresolved',
                fn ($query) => $query->unresolved(),
            )
            ->when(
                ($filters['status'] ?? null) === 'resolved',
                fn ($query) => $query->resolved(),
            )
            ->forCertification($filters['certification_id'] ?? null)
            ->keyword($filters['keyword'] ?? null)
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}