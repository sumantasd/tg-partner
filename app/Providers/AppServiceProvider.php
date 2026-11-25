<?php

namespace App\Providers;

use App\Support\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
        // Carbon Macros
        Carbon::macro('toIst', function () {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(DateTimeHelper::TIMEZONE);
        });

        Carbon::macro('formatIst', function (string $format = 'd M Y, h:i A') {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(DateTimeHelper::TIMEZONE)->format($format);
        });

        Carbon::macro('formatIstFull', function () {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(DateTimeHelper::TIMEZONE)->format('d M Y, h:i A') . ' ' . DateTimeHelper::TIMEZONE_LABEL;
        });

        // Blade Directives for Standardized Indian Timezone & Currency Formatting
        Blade::directive('istDate', function ($expression) {
            return "<?php echo \\App\\Support\\DateTimeHelper::formatDate({$expression}); ?>";
        });

        Blade::directive('istNumericDate', function ($expression) {
            return "<?php echo \\App\\Support\\DateTimeHelper::formatNumericDate({$expression}); ?>";
        });

        Blade::directive('istTime', function ($expression) {
            return "<?php echo \\App\\Support\\DateTimeHelper::formatTime({$expression}); ?>";
        });

        Blade::directive('istDateTime', function ($expression) {
            return "<?php echo \\App\\Support\\DateTimeHelper::formatDateTime({$expression}); ?>";
        });

        Blade::directive('inr', function ($expression) {
            return "<?php echo \\App\\Support\\DateTimeHelper::formatIndianCurrency({$expression}); ?>";
        });
    }
}
