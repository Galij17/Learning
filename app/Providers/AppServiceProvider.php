<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Quiz;
use App\Policies\AssignmentPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\CoursePolicy;
use App\Policies\DiscussionPolicy;
use App\Policies\QuizPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Discussion::class, DiscussionPolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->input('email', '') . '|' . $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('exam-autosave', function (Request $request) {
            return Limit::perMinute(120)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
