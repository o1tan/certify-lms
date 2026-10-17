<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\QaThread\StoreRequest;
use App\Http\Requests\QaThread\UpdateRequest;
use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use App\UseCases\QaThread\IndexAction;
use App\UseCases\QaThread\ResolveAction;
use App\UseCases\QaThread\StoreAction;
use App\UseCases\QaThread\UnresolveAction;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QaThreadController extends Controller
{
    public function index(Request $request, IndexAction $action): View
    {
        $user = $request->user();

        $this->authorize('viewAny', QaThread::class);

        $filters = [
            'certification_id' => $request->query('certification_id'),
            'status' => $request->query('status'),
            'keyword' => $request->query('keyword'),
        ];

        $certifications = Certification::query()
            ->published()
            ->when(
                $user->role === UserRole::Coach,
                fn ($query) => $query->assignedTo($user),
            )
            ->orderBy('name')
            ->get();

        return view('qa-thread.index', [
            'threads' => $action($user, $filters),
            'filters' => $filters,
            'certifications' => $certifications,
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', QaThread::class);

        return view('qa-thread.create', [
            'certifications' => Certification::query()
                ->published()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $thread = $action($request->user(), $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を投稿しました。');
    }

    public function show(QaThread $thread): View
    {
        $this->authorize('view', $thread);

        $thread->load([
            'certification',
            'user',
            'replies.user',
        ])->loadCount('replies');

        return view('qa-thread.show', [
            'thread' => $thread,
        ]);
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        $thread->load('certification');

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(
        QaThread $thread,
        UpdateRequest $request,
        UpdateAction $action,
    ): RedirectResponse {
        $action($thread, $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を更新しました。');
    }

    public function destroy(
        QaThread $thread,
        DestroyAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function resolve(
        QaThread $thread,
        ResolveAction $action,
    ): RedirectResponse {
        $this->authorize('resolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を解決済みにしました。');
    }

    public function unresolve(
        QaThread $thread,
        UnresolveAction $action,
    ): RedirectResponse {
        $this->authorize('unresolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を未解決に戻しました。');
    }
}