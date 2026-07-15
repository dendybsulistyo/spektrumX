<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderIndoorController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('permission:customers.view')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'edit'])->names('customers');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['create', 'store', 'update', 'destroy'])->names('customers');
    });

    Route::middleware('permission:produk.view')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['index', 'edit'])->names('produk');
    });
    Route::middleware('permission:produk.manage')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['create', 'store', 'update', 'destroy'])->names('produk');
    });

    Route::middleware('permission:operators.view')->group(function () {
        Route::resource('operators', OperatorController::class)->only(['index', 'edit'])->names('operators');
    });
    Route::middleware('permission:operators.manage')->group(function () {
        Route::resource('operators', OperatorController::class)->only(['create', 'store', 'update', 'destroy'])->names('operators');
    });

    Route::middleware('permission:printers.view')->group(function () {
        Route::resource('printers', PrinterController::class)->only(['index', 'edit'])->names('printers');
    });
    Route::middleware('permission:printers.manage')->group(function () {
        Route::resource('printers', PrinterController::class)->only(['create', 'store', 'update', 'destroy'])->names('printers');
    });

    Route::middleware('permission:order-indoor.view')->group(function () {
        Route::resource('order-indoor', OrderIndoorController::class)->only(['index', 'edit'])->names('order-indoor');
    });
    Route::middleware('permission:order-indoor.manage')->group(function () {
        Route::resource('order-indoor', OrderIndoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-indoor');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
    });
});

require __DIR__.'/auth.php';
