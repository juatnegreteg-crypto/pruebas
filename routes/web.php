<?php

use App\Http\Controllers\Web\AgendaController;
use App\Http\Controllers\Web\AvailabilityController;
use App\Http\Controllers\Web\BundleController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\IamPageController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\QuoteController;
use App\Http\Controllers\Web\ScheduleController;
use App\Http\Controllers\Web\ServiceController;
use App\Http\Controllers\Web\SetupController;
use App\Http\Controllers\Web\TaxController;
use App\Http\Controllers\Web\TechnicianController;
use App\Http\Controllers\Web\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware('setup.available')->group(function () {
    Route::get('setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::get('appointment-request', [AvailabilityController::class, 'index'])->name('availability.index');
Route::redirect('availability', 'appointment-request')->name('availability.legacy');
Route::redirect('agenda', 'appointments')->name('agenda.legacy');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('appointments', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('bundles', [BundleController::class, 'index'])->name('bundles.index');
    Route::get('bundles/{bundle}', [BundleController::class, 'show'])->name('bundles.show');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('taxes', [TaxController::class, 'index'])->name('taxes.index');
    Route::post('services/import', [ServiceController::class, 'import'])->name('services.import');
    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

    // Quotes
    Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('quotes/{quote}/pdf/view', [QuoteController::class, 'viewPdf'])->name('quotes.pdf.view');

    // Schedule
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    // Technicians
    Route::get('technicians', [TechnicianController::class, 'index'])->name('technicians.index');
    Route::get('technicians/{technician}', [TechnicianController::class, 'show'])->name('technicians.show');

    // IAM
    Route::get('iam/users', [IamPageController::class, 'users'])->middleware('permission:users.view')->name('iam.users.index');
    Route::get('iam/users/{user}/capabilities', [IamPageController::class, 'userCapabilities'])->middleware('permission:profiles.assign_permissions')->name('iam.users.capabilities');
    Route::get('iam/profiles', [IamPageController::class, 'profiles'])->middleware('permission:profiles.view')->name('iam.profiles.index');
    Route::get('iam/profiles/{profile}/capabilities', [IamPageController::class, 'profileCapabilities'])->middleware('permission:profiles.assign_permissions')->name('iam.profiles.capabilities');
    Route::get('iam/permissions', [IamPageController::class, 'permissions'])->middleware('permission:permissions.view')->name('iam.permissions.index');
    Route::get('iam/skills', [IamPageController::class, 'skills'])->middleware('permission:skills.view')->name('iam.skills.index');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('products/template', [ProductController::class, 'template'])->name('products.template');
});

require __DIR__.'/settings.php';
