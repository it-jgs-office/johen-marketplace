<?php

namespace App\Providers;

use App\Services\ThemeResolverService;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        RedirectIfAuthenticated::redirectUsing(function () {
            if (Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }
            return route('home');
        });

        // Tema event untuk halaman publik marketplace. Diresolusi sekali per request.
        if (! $this->app->runningInConsole() && ! $this->isAdminRequest()) {
            $resolver = app(ThemeResolverService::class);
            $activeTheme = $resolver->resolveActiveTheme(request()->query('preview_theme'));

            if ($activeTheme) {
                $decorationUrls = array_map(fn ($img) => media_url($img), (array) ($activeTheme->decorative_images ?: []));

                View::share([
                    'activeTheme' => $activeTheme,
                    'activeThemeCss' => ThemeResolverService::cssVars($activeTheme),
                    'activeDecorationImages' => array_values(array_filter($decorationUrls)),
                    'activeParticleEffect' => $activeTheme->particle_effect ?: null,
                    'activeThemeLogoUrl' => media_url($activeTheme->logo_override),
                ]);
            }
        }
    }

    protected function isAdminRequest(): bool
    {
        $path = request()->path();
        $routeName = request()->route()?->getName();

        return str_starts_with($path, 'admin')
            || str_starts_with($routeName ?? '', 'admin.');
    }
}
