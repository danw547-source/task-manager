<?php

namespace App\Providers;

use App\Repositories\TaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->bind(TaskRepositoryInterface::class, TaskRepository::class); // When we ask for TaskRepositoryInterface, give us TaskRepository. This is called dependency injection, and it allows us to use the interface instead of the implementation, which makes our code more flexible and easier to test.
	}

}
