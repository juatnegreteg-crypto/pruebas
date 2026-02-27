<?php

use App\Http\Controllers\Api\V1\AuthorizationController;
use App\Http\Controllers\Api\V1\BundleController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\I18nController;
use App\Http\Controllers\Api\V1\Iam\PermissionController as IamPermissionController;
use App\Http\Controllers\Api\V1\Iam\ProfileController as IamProfileController;
use App\Http\Controllers\Api\V1\Iam\SkillController as IamSkillController;
use App\Http\Controllers\Api\V1\Iam\UserController as IamUserController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PublicTechnicianAppoimentController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ServiceExportController;
use App\Http\Controllers\Api\V1\ServiceImportController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\TechnicianAppoimentController;
use App\Http\Controllers\Api\V1\TechnicianAvailabilityController;
use App\Http\Controllers\Api\V1\TechnicianController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('i18n')
        ->middleware('api.client')
        ->group(function () {
            Route::get('/', [I18nController::class, 'index']);
            Route::get('/{locale}', [I18nController::class, 'show']);
        });

    Route::get('bundles', [BundleController::class, 'index']);
    Route::post('bundles', [BundleController::class, 'store']);
    Route::get('bundles/{bundle}', [BundleController::class, 'show']);
    Route::patch('bundles/{bundle}', [BundleController::class, 'update']);
    Route::get('products', [ProductController::class, 'index']);
    Route::post('product-exports', [ProductController::class, 'queueExport'])->name('api.v1.product-exports.store');
    Route::get('product-exports/{exportId}', [ProductController::class, 'exportStatus'])->name('api.v1.product-exports.show');
    Route::post('products', [ProductController::class, 'store']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::post('services', [ServiceController::class, 'store']);
    Route::patch('services/{service}', [ServiceController::class, 'update']);
    Route::get('taxes', [TaxController::class, 'index']);
    Route::post('taxes', [TaxController::class, 'store']);
    Route::patch('taxes/{tax}', [TaxController::class, 'update']);
    Route::delete('taxes/{tax}', [TaxController::class, 'destroy']);

    // Service imports — modeled as a resource with lifecycle
    Route::post('service-imports', [ServiceImportController::class, 'store']);
    Route::get('service-imports/template', [ServiceImportController::class, 'template']);
    Route::get('service-imports/{uuid}', [ServiceImportController::class, 'show']);

    // Service exports (async process with full lifecycle — own resource)
    Route::post('service-exports', [ServiceExportController::class, 'store']);
    Route::get('service-exports/{uuid}', [ServiceExportController::class, 'show']);

    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/export', [CustomerController::class, 'export']);
    Route::get('customers/template', [CustomerController::class, 'template']);
    Route::get('customers/imports/{customerImport}/status', [CustomerController::class, 'importStatus']);
    Route::post('customers', [CustomerController::class, 'store']);
    Route::post('customers/import', [CustomerController::class, 'import']);
    Route::put('customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::post('vehicles', [VehicleController::class, 'store']);
    Route::patch('vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::get('public/appointments/availability', [PublicTechnicianAppoimentController::class, 'availability']);
    Route::post('public/appointments', [PublicTechnicianAppoimentController::class, 'store']);

    Route::middleware(['web', 'auth', 'verified', 'active'])->group(function () {
        Route::get('appointments', [TechnicianAppoimentController::class, 'index']);
        Route::get('appointments/availability', [TechnicianAppoimentController::class, 'availability']);
        Route::post('appointments', [TechnicianAppoimentController::class, 'store']);
        Route::patch('appointments/{appointment}/reschedule', [TechnicianAppoimentController::class, 'reschedule']);
        Route::patch('appointments/{appointment}/reassign-technician', [TechnicianAppoimentController::class, 'reassignTechnician']);
        Route::patch('appointments/{appointment}/cancel', [TechnicianAppoimentController::class, 'cancel']);
        Route::patch('appointments/{appointment}/confirm', [TechnicianAppoimentController::class, 'confirm']);
        Route::patch('appointments/{appointment}/technician-observation', [TechnicianAppoimentController::class, 'addTechnicianObservation']);
    });

    Route::middleware(['web', 'auth', 'verified', 'active'])->group(function () {
        Route::get('me/authorization', [AuthorizationController::class, 'show']);
    });

    Route::prefix('users')
        ->middleware(['web', 'auth', 'verified', 'active', 'permission:users.view'])
        ->group(function () {
            Route::get('/', [IamUserController::class, 'index']);
            Route::get('/link-candidates', [IamUserController::class, 'linkCandidates'])->middleware('permission:users.create');
            Route::get('/{user}', [IamUserController::class, 'show']);
            Route::get('/{user}/permissions', [IamUserController::class, 'permissions'])->middleware('permission:profiles.assign_permissions');
            Route::post('/', [IamUserController::class, 'store'])->middleware('permission:users.create');
            Route::patch('/{user}', [IamUserController::class, 'update'])->middleware('permission:users.update');
            Route::patch('/{user}/status', [IamUserController::class, 'updateStatus'])->middleware('permission:users.manage_status');
            Route::put('/{user}/profile', [IamUserController::class, 'updateProfile'])->middleware('permission:users.update');
            Route::put('/{user}/permissions', [IamUserController::class, 'updatePermissions'])->middleware('permission:profiles.assign_permissions');
            Route::put('/{user}/skills', [IamUserController::class, 'updateSkills'])->middleware('permission:skills.assign');
        });

    Route::prefix('profiles')
        ->middleware(['web', 'auth', 'verified', 'active', 'permission:profiles.view'])
        ->group(function () {
            Route::get('/', [IamProfileController::class, 'index']);
            Route::post('/', [IamProfileController::class, 'store'])->middleware('permission:profiles.create');
            Route::patch('/{profile}', [IamProfileController::class, 'update'])->middleware('permission:profiles.update');
            Route::delete('/{profile}', [IamProfileController::class, 'destroy'])->middleware('permission:profiles.update');
            Route::put('/{profile}/permissions', [IamProfileController::class, 'updatePermissions'])->middleware('permission:profiles.assign_permissions');
            Route::put('/{profile}/skills', [IamProfileController::class, 'updateSkills'])->middleware('permission:skills.assign');
        });

    Route::prefix('permissions')
        ->middleware(['web', 'auth', 'verified', 'active', 'permission:permissions.view'])
        ->group(function () {
            Route::get('/', [IamPermissionController::class, 'index']);
        });

    Route::prefix('skills')
        ->middleware(['web', 'auth', 'verified', 'active', 'permission:skills.view'])
        ->group(function () {
            Route::get('/', [IamSkillController::class, 'index']);
        });

    // Quotes
    Route::get('quotes', [QuoteController::class, 'index']);
    Route::get('quotes/export', [QuoteController::class, 'export']);
    Route::get('quotes/export-detailed', [QuoteController::class, 'exportDetailed']);
    Route::post('quotes', [QuoteController::class, 'store']);
    Route::get('quotes/{quote}', [QuoteController::class, 'show']);
    Route::patch('quotes/{quote}', [QuoteController::class, 'update']);
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']);
    Route::post('quotes/{quote}/confirm', [QuoteController::class, 'confirm']);
    Route::post('quotes/{quote}/cancel', [QuoteController::class, 'cancel']);
    Route::post('quotes/{quote}/items', [QuoteController::class, 'addItem']);
    Route::delete('quotes/{quote}/items/{item}', [QuoteController::class, 'removeItem']);

    // Technicians
    Route::prefix('technicians')->group(function () {
        Route::get('/', [TechnicianController::class, 'index']);
        Route::post('/', [TechnicianController::class, 'store']);
        Route::patch('/{technician}', [TechnicianController::class, 'update']);
        Route::get('/{technician}/availability', [TechnicianAvailabilityController::class, 'show']);
        Route::put('/{technician}/availability', [TechnicianAvailabilityController::class, 'update']);
        Route::get('/{technician}/blocks', [TechnicianController::class, 'blocks']);
        Route::post('/{technician}/blocks', [TechnicianController::class, 'storeBlock']);
        Route::delete('/{technician}/blocks/{block}', [TechnicianController::class, 'destroyBlock']);
    });

    // Schedule
    Route::prefix('schedule')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::put('/', [ScheduleController::class, 'update']);
        Route::get('/overrides', [ScheduleController::class, 'overrides']);
        Route::post('/overrides', [ScheduleController::class, 'storeOverride']);
        Route::delete('/overrides/{override}', [ScheduleController::class, 'destroyOverride']);
        Route::get('/availability', [ScheduleController::class, 'availability']);
    });
});
