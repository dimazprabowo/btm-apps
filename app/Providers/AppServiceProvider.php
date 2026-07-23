<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Project;
use App\Models\SystemConfiguration;
use App\Models\Task;
use App\Models\User;
use App\Policies\ChatPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\SystemConfigurationPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(SystemConfiguration::class, SystemConfigurationPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Chat::class, ChatPolicy::class);
        Gate::policy(ChatMessage::class, ChatPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        // Dashboard policy — bound to a string key (no Eloquent model)
        Gate::define('viewStats', [DashboardPolicy::class, 'viewStats']);

        // Super admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super admin') ? true : null;
        });

        // Override Laravel config with database system configurations
        $this->applySystemConfigurations();
    }

    /**
     * Apply system configurations from database to Laravel config.
     */
    private function applySystemConfigurations(): void
    {
        try {
            if (!Schema::hasTable('system_configurations')) {
                return;
            }

            // Map of system_configuration keys to Laravel config keys
            $configMap = [
                'app.name' => 'app.name',
                'app.timezone' => 'app.timezone',
            ];

            foreach ($configMap as $dbKey => $laravelKey) {
                $value = SystemConfiguration::get($dbKey);
                if ($value !== null) {
                    config([$laravelKey => $value]);
                }
            }

            // Apply timezone if set
            $timezone = config('app.timezone');
            if ($timezone) {
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $e) {
            // Silently fail if database is not available (e.g. during migrations)
        }
    }
}
