<?php

namespace App\Providers;

use App\Models\Bundle;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\TechnicianAppoiment;
use App\Models\User;
use App\Models\Vehicle;
use App\Observers\BundleObserver;
use App\Observers\ProductObserver;
use App\Observers\ServiceObserver;
use App\Services\Technicians\AgendaClient;
use App\Services\Technicians\DatabaseAgendaClient;
use App\Services\Technicians\HttpAgendaClient;
use App\Services\Technicians\InMemoryAgendaClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AgendaClient::class, function (): AgendaClient {
            $driver = (string) config('agenda.driver', 'database');

            return match ($driver) {
                'database' => new DatabaseAgendaClient,
                'in-memory' => new InMemoryAgendaClient,
                'http' => new HttpAgendaClient(
                    baseUrl: (string) config('agenda.http.base_url', ''),
                    timeout: (int) config('agenda.http.timeout', 10),
                    connectTimeout: (int) config('agenda.http.connect_timeout', 5),
                    retryTimes: (int) config('agenda.http.retry_times', 1),
                    retrySleepMs: (int) config('agenda.http.retry_sleep_ms', 100),
                    token: config('agenda.http.token') !== null ? (string) config('agenda.http.token') : null,
                    endpoints: [
                        'create' => (string) config('agenda.http.endpoints.create', '/api/v1/appointments'),
                        'reschedule' => (string) config('agenda.http.endpoints.reschedule', '/api/v1/appointments/{appointment}/reschedule'),
                        'reassign_technician' => (string) config('agenda.http.endpoints.reassign_technician', '/api/v1/appointments/{appointment}/reassign-technician'),
                        'cancel' => (string) config('agenda.http.endpoints.cancel', '/api/v1/appointments/{appointment}/cancel'),
                        'availability' => (string) config('agenda.http.endpoints.availability', '/api/v1/appointments/availability'),
                    ],
                ),
                default => throw new InvalidArgumentException("Driver de Agenda no soportado: {$driver}"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureObservers();
        $this->configureMorphMap();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureObservers(): void
    {
        Product::observe(ProductObserver::class);
        Service::observe(ServiceObserver::class);
        Bundle::observe(BundleObserver::class);
    }

    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'catalog_item' => CatalogItem::class,
            'product' => Product::class,
            'service' => Service::class,
            'bundle' => Bundle::class,
            'customer' => Customer::class,
            'user' => User::class,
            'vehicle' => Vehicle::class,
            'technician_appoiment' => TechnicianAppoiment::class,
        ]);
    }
}
