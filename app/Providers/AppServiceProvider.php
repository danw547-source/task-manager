<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Repositories\DashboardRepository;
use App\Repositories\DashboardRepositoryInterface;
use App\Repositories\TaskRepository;
use App\Repositories\TaskEngagementRepository;
use App\Repositories\TaskEngagementRepositoryInterface;
use App\Repositories\TaskRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Wires up app-level dependency bindings and authorization setup.
 * In Laravel, providers are the normal place for interface bindings and policy mapping.
 */
class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		// Bind interfaces to implementations so services stay decoupled and easy to test.
		$this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
		$this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
		$this->app->bind(TaskEngagementRepositoryInterface::class, TaskEngagementRepository::class);
		$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
	}

	public function boot(): void
	{
		// Explicit policy mapping keeps authorization rules in one place
		// instead of repeating role/ownership checks inside controllers.
		Gate::policy(Task::class, TaskPolicy::class);
		Gate::policy(User::class, UserPolicy::class);
	}
}
