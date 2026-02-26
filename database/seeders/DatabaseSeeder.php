<?php

namespace Database\Seeders;

use App\Models\TaskComment;
use App\Models\TaskCommentReceipt;
use App\Models\User;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->ensurePassportPersonalAccessClient();

        $rangeStart = now()->subMonths(12)->startOfDay();
        $rangeEnd = now();

        $adminUser = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('admin'),
            'role' => 'admin',
        ]);
        if ($adminUser->wasRecentlyCreated) {
            $this->assignModelTimestamps($adminUser, $this->randomMomentBetween($rangeStart, $rangeEnd), $rangeEnd);
        }

        $standardUser = User::updateOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Standard User',
            'password' => Hash::make('user'),
            'role' => 'user',
        ]);
        if ($standardUser->wasRecentlyCreated) {
            $this->assignModelTimestamps($standardUser, $this->randomMomentBetween($rangeStart, $rangeEnd), $rangeEnd);
        }

        User::factory(48)->create()->each(function (User $user) use ($rangeStart, $rangeEnd) {
            $this->assignModelTimestamps($user, $this->randomMomentBetween($rangeStart, $rangeEnd), $rangeEnd);
        });

        $users = User::query()->get(['id', 'name', 'created_at']);

        $defaultTasks = collect([
            Task::create([
                'user_id' => $adminUser->id,
                'title' => 'Review API access controls',
                'description' => 'Verify admin-only routes and role middleware behavior.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(2)->toDateString(),
                'position' => 1,
            ]),
            Task::create([
                'user_id' => $adminUser->id,
                'title' => 'Audit outstanding task escalations',
                'description' => 'Check overdue items and assign follow-up owners.',
                'status' => 'pending',
                'due_date' => now()->addDays(3)->toDateString(),
                'position' => 2,
            ]),
            Task::create([
                'user_id' => $adminUser->id,
                'title' => 'Approve notification templates',
                'description' => 'Review tone and wording before next release.',
                'status' => 'pending',
                'due_date' => now()->addDays(4)->toDateString(),
                'position' => 3,
            ]),
            Task::create([
                'user_id' => $standardUser->id,
                'title' => 'Refine dashboard card spacing',
                'description' => 'Adjust spacing around metrics and quick actions.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(2)->toDateString(),
                'position' => 4,
            ]),
            Task::create([
                'user_id' => $standardUser->id,
                'title' => 'Update onboarding checklist copy',
                'description' => 'Make checklist labels clearer for first-time users.',
                'status' => 'pending',
                'due_date' => now()->addDays(5)->toDateString(),
                'position' => 5,
            ]),
            Task::create([
                'user_id' => $standardUser->id,
                'title' => 'Test mobile task interactions',
                'description' => 'Confirm follow, react, and comment flows on small screens.',
                'status' => 'pending',
                'due_date' => now()->addDays(6)->toDateString(),
                'position' => 6,
            ]),
        ]);

        $defaultTasks->each(function (Task $task) use ($users, $rangeEnd) {
            $owner = $users->firstWhere('id', $task->user_id);
            $createdAt = $this->randomMomentBetween($owner?->created_at ?? now()->subMonths(12), $rangeEnd);

            $task->forceFill([
                'due_date' => Carbon::instance($createdAt)->copy()->addDays(random_int(2, 14))->toDateString(),
            ]);

            $this->assignModelTimestamps($task, $createdAt, $rangeEnd);
        });

        $generatedTaskCount = 100 - $defaultTasks->count();

        for ($index = 0; $index < $generatedTaskCount; $index++) {
            $owner = $users->random();
            $task = Task::factory()->create([
                'user_id' => $owner->id,
            ]);

            $createdAt = $this->randomMomentBetween($owner->created_at, $rangeEnd);
            $task->forceFill([
                'due_date' => Carbon::instance($createdAt)->copy()->addDays(random_int(2, 14))->toDateString(),
            ]);
            $this->assignModelTimestamps($task, $createdAt, $rangeEnd);
        }

        $tasks = Task::query()->get(['id', 'user_id', 'title', 'created_at']);
        $defaultConversationCount = $this->seedDefaultUserConversations($adminUser, $standardUser, $tasks);

        $this->seedTaskFollowers($tasks, $users);
        $this->seedTaskConversations($tasks, $users, max(0, 250 - $defaultConversationCount));
        $this->seedDefaultUnreadNotifications($adminUser, $standardUser, $tasks);

        $adminTaskCount = Task::query()->where('user_id', $adminUser->id)->count();
        $userTaskCount = Task::query()->where('user_id', $standardUser->id)->count();
        $adminCommentCount = TaskComment::query()->where('user_id', $adminUser->id)->count();
        $userCommentCount = TaskComment::query()->where('user_id', $standardUser->id)->count();
        $adminUnreadCount = TaskCommentReceipt::query()
            ->where('recipient_user_id', $adminUser->id)
            ->whereNull('read_at')
            ->count();
        $userUnreadCount = TaskCommentReceipt::query()
            ->where('recipient_user_id', $standardUser->id)
            ->whereNull('read_at')
            ->count();

        $this->command?->info(sprintf(
            'Seeded users=%d, tasks=%d, comments=%d',
            User::count(),
            Task::count(),
            TaskComment::count()
        ));
        $this->command?->info(sprintf(
            'Default test users -> admin tasks=%d, admin comments=%d, admin unread=%d, user tasks=%d, user comments=%d, user unread=%d',
            $adminTaskCount,
            $adminCommentCount,
            $adminUnreadCount,
            $userTaskCount,
            $userCommentCount,
            $userUnreadCount
        ));
    }

    private function seedDefaultUnreadNotifications(User $adminUser, User $standardUser, Collection $tasks): void
    {
        $adminTask = $tasks->firstWhere('user_id', $adminUser->id) ?? $tasks->first();
        $userTask = $tasks->firstWhere('user_id', $standardUser->id) ?? $tasks->last();
        $rangeEnd = now();

        if (!$adminTask || !$userTask) {
            return;
        }

        $seedNotifications = [
            [
                'task' => $adminTask,
                'author' => $standardUser,
                'recipient' => $adminUser,
                'body' => 'I validated the API permission checks and documented the two endpoints that still need stricter role guards.',
            ],
            [
                'task' => $userTask,
                'author' => $adminUser,
                'recipient' => $standardUser,
                'body' => 'I reviewed your dashboard spacing update; the cards read better now, but the action row still needs 8px more vertical padding.',
            ],
        ];

        foreach ($seedNotifications as $notification) {
            $task = $notification['task'];
            $author = $notification['author'];
            $recipient = $notification['recipient'];

            $earliestAllowed = $this->laterMoment($task->created_at, $author->created_at);
            $createdAt = $this->randomMomentBetween($earliestAllowed, $rangeEnd);

            $comment = TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $author->id,
                'body' => $notification['body'],
                'created_at' => $createdAt,
                'updated_at' => $this->randomMomentBetween($createdAt, $rangeEnd),
            ]);

            TaskCommentReceipt::create([
                'task_comment_id' => $comment->id,
                'task_id' => $task->id,
                'recipient_user_id' => $recipient->id,
                'read_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $this->randomMomentBetween($createdAt, $rangeEnd),
            ]);
        }
    }

    private function ensurePassportPersonalAccessClient(): void
    {
        $clientRepository = app(ClientRepository::class);
        $provider = (string) config('auth.guards.api.provider', 'users');

        try {
            $clientRepository->personalAccessClient($provider);
        } catch (\RuntimeException $exception) {
            $clientRepository->createPersonalAccessGrantClient(
                'Task Manager Personal Access Client',
                $provider
            );
        }
    }

    private function seedDefaultUserConversations(User $adminUser, User $standardUser, Collection $tasks): int
    {
        $adminTask = $tasks->firstWhere('user_id', $adminUser->id) ?? $tasks->first();
        $userTask = $tasks->firstWhere('user_id', $standardUser->id) ?? $tasks->last();
        $rangeEnd = now();

        $seedComments = [
            [
                'task_id' => $adminTask->id,
                'user_id' => $adminUser->id,
                'body' => 'I would keep this implementation simple first and iterate after feedback.',
            ],
            [
                'task_id' => $adminTask->id,
                'user_id' => $standardUser->id,
                'body' => 'Makes sense. I can prepare a lighter visual variant so we can compare quickly.',
            ],
            [
                'task_id' => $adminTask->id,
                'user_id' => $adminUser->id,
                'body' => 'Perfect, share that in the next update and we will decide in standup.',
            ],
            [
                'task_id' => $adminTask->id,
                'user_id' => $standardUser->id,
                'body' => 'Will do. Also, I think reducing the heading spacing will improve readability.',
            ],
            [
                'task_id' => $userTask->id,
                'user_id' => $standardUser->id,
                'body' => 'I think this card would look better with softer colors instead of bright red.',
            ],
            [
                'task_id' => $userTask->id,
                'user_id' => $adminUser->id,
                'body' => 'Good call. Pink might clash with red though, so we should test contrast first.',
            ],
            [
                'task_id' => $userTask->id,
                'user_id' => $standardUser->id,
                'body' => 'Agreed. I will try a neutral accent and post screenshots by end of day.',
            ],
            [
                'task_id' => $userTask->id,
                'user_id' => $adminUser->id,
                'body' => 'Great, that should give us enough signal to choose a final direction.',
            ],
        ];

        foreach ($seedComments as $comment) {
            $author = (int) $comment['user_id'] === (int) $adminUser->id ? $adminUser : $standardUser;
            $task = $tasks->firstWhere('id', $comment['task_id']);
            $earliestAllowed = $this->laterMoment($author->created_at, $task?->created_at ?? $author->created_at);
            $createdAt = $this->randomMomentBetween(
                $earliestAllowed,
                $rangeEnd
            );

            TaskComment::create([
                ...$comment,
                'created_at' => $createdAt,
                'updated_at' => $this->randomMomentBetween($createdAt, $rangeEnd),
            ]);
        }

        return count($seedComments);
    }

    private function seedTaskFollowers(Collection $tasks, Collection $users): void
    {
        foreach ($tasks as $task) {
            $eligibleFollowers = $users
                ->where('id', '!=', $task->user_id)
                ->shuffle();

            $followCount = min($eligibleFollowers->count(), random_int(2, 7));
            $task->followers()->syncWithoutDetaching(
                $eligibleFollowers->take($followCount)->pluck('id')->all()
            );
        }
    }

    private function seedTaskConversations(Collection $tasks, Collection $users, int $commentCount): void
    {
        $conversationHistory = [];
        $rangeEnd = now();

        for ($index = 0; $index < $commentCount; $index++) {
            $task = $tasks->random();
            $author = $users->random();
            $taskHistory = $conversationHistory[$task->id] ?? [];

            $body = $this->buildCommentBody($task->title, $author->name, $taskHistory);

            $earliestAllowed = $this->laterMoment($task->created_at, $author->created_at);
            $createdAt = $this->randomMomentBetween(
                $earliestAllowed,
                $rangeEnd
            );

            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $author->id,
                'body' => $body,
                'created_at' => $createdAt,
                'updated_at' => $this->randomMomentBetween($createdAt, $rangeEnd),
            ]);

            $taskHistory[] = [
                'name' => $author->name,
                'body' => $body,
            ];

            $conversationHistory[$task->id] = array_slice($taskHistory, -6);
        }
    }

    private function buildCommentBody(string $taskTitle, string $authorName, array $taskHistory): string
    {
        $topic = $this->buildTopicSnippet($taskTitle);
        $authorFirstName = $this->firstName($authorName);

        $starterComments = [
            "For {$topic}, I think a soft pink accent could work if we keep the buttons neutral.",
            "Quick thought on {$topic}: we should keep the copy shorter so it's easier to scan.",
            "I'd keep {$topic} simple and ship this first version before adding extra polish.",
            "Maybe for {$topic} we can move the main action higher so users don't miss it.",
            "{$topic} looks close. I would only tweak spacing around the heading and save button.",
            "For {$topic}, let's keep the red but tone it down a little so it feels less harsh.",
            "I tested {$topic} on mobile; the current layout works, but the CTA could be clearer.",
            "Could we align {$topic} with the dashboard style so it feels more consistent overall?",
            "I like where {$topic} is headed. A compact version would probably feel cleaner.",
            "For {$topic}, maybe we should keep the labels more descriptive for first-time users.",
        ];

        $replyComments = [
            "@%s good point. Pink might work, but I'd avoid pairing it with strong red on the same card.",
            "@%s I agree on the direction. If we simplify the text, this will read much better.",
            "@%s fair take. We can try that quickly and compare both versions before deciding.",
            "@%s I like that idea. We should keep the spacing tight so the content stays focused.",
            "@%s that makes sense. I'd keep the current structure and only adjust colors slightly.",
            "@%s +1 from me. This feels like the safest improvement for now.",
            "@%s totally. I can see this helping readability, especially on smaller screens.",
            "@%s agreed. Let's keep the change minimal so we don't risk breaking flow.",
            "@%s nice suggestion. A lighter accent would probably feel more balanced here.",
            "@%s yes, and if we keep contrast high, accessibility should still be fine.",
        ];

        if (count($taskHistory) === 0 || fake()->boolean(42)) {
            return $starterComments[array_rand($starterComments)];
        }

        $target = $taskHistory[array_rand($taskHistory)];
        $targetFirstName = $this->firstName($target['name']);

        $reply = sprintf(
            $replyComments[array_rand($replyComments)],
            $targetFirstName
        );

        if (fake()->boolean(35)) {
            $followUp = [
                "{$authorFirstName} here — I can test this in staging after lunch.",
                "I'll mock this quickly so we can decide in today's standup.",
                "If everyone agrees, I can include this in the next pass.",
                "I can take this as an action item and post screenshots.",
            ];

            return $reply . ' ' . $followUp[array_rand($followUp)];
        }

        return $reply;
    }

    private function firstName(string $fullName): string
    {
        $name = trim(Str::before($fullName, ' '));

        return $name !== '' ? $name : $fullName;
    }

    private function buildTopicSnippet(string $taskTitle): string
    {
        $cleanTitle = trim($taskTitle);

        if ($cleanTitle === '') {
            return 'this task';
        }

        return '"' . Str::limit($cleanTitle, 48, '...') . '"';
    }

    private function assignModelTimestamps($model, Carbon $createdAt, Carbon $rangeEnd): void
    {
        $model->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $this->randomMomentBetween($createdAt, $rangeEnd),
        ])->saveQuietly();
    }

    private function randomMomentBetween(Carbon $start, Carbon $end): Carbon
    {
        $safeStart = $start->copy();
        $safeEnd = $end->copy();

        if ($safeStart->greaterThanOrEqualTo($safeEnd)) {
            return $safeStart;
        }

        return Carbon::instance(fake()->dateTimeBetween($safeStart, $safeEnd));
    }

    private function laterMoment(Carbon $first, Carbon $second): Carbon
    {
        return $first->greaterThan($second) ? $first->copy() : $second->copy();
    }
}
