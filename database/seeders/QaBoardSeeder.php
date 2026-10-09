<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Seeder;

class QaBoardSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('email', 'student@certify-lms.test')->firstOrFail();

        Certification::query()
            ->where('status', CertificationStatus::Published)
            ->get()
            ->each(function (Certification $certification) use ($student): void {
                $unanswered = QaThread::factory()
                    ->forCertification($certification)
                    ->byUser($student)
                    ->create([
                        'title' => $certification->name.'についての質問',
                        'created_at' => now()->subDays(3),
                        'updated_at' => now()->subDays(3),
                    ]);

                $answered = QaThread::factory()
                    ->forCertification($certification)
                    ->byUser($student)
                    ->create([
                        'created_at' => now()->subDays(2),
                        'updated_at' => now()->subDays(2),
                    ]);

                QaReply::factory()
                    ->count(2)
                    ->forThread($answered)
                    ->create();

                $resolved = QaThread::factory()
                    ->resolved()
                    ->forCertification($certification)
                    ->byUser($student)
                    ->create([
                        'created_at' => now()->subDay(),
                        'updated_at' => now()->subDay(),
                        'resolved_at' => now()->subHours(12),
                    ]);

                QaReply::factory()
                    ->count(3)
                    ->forThread($resolved)
                    ->create();
            });
    }
}