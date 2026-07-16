<?php

use App\Http\Controllers\BahanOutdoorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HargaCetakOutdoorController;
use App\Http\Controllers\KategoriBahanOutdoorController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderIndoorController;
use App\Http\Controllers\OrderOutdoorController;
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
        Route::get('/customers-suggest-code', [CustomerController::class, 'suggestCode'])->name('customers.suggest-code');
    });

    Route::middleware('permission:produk.view')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['index', 'edit'])->names('produk');
    });
    Route::middleware('permission:produk.manage')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['create', 'store', 'update', 'destroy'])->names('produk');
    });

    Route::middleware('permission:kategori.view')->group(function () {
        Route::resource('kategori', KategoriController::class)->only(['index', 'edit'])->names('kategori');
    });
    Route::middleware('permission:kategori.manage')->group(function () {
        Route::resource('kategori', KategoriController::class)->only(['create', 'store', 'update', 'destroy'])->names('kategori');
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

    Route::middleware('permission:kategori-bahan-outdoor.view')->group(function () {
        Route::resource('kategori-bahan-outdoor', KategoriBahanOutdoorController::class)->only(['index', 'edit'])->names('kategori-bahan-outdoor');
    });
    Route::middleware('permission:kategori-bahan-outdoor.manage')->group(function () {
        Route::resource('kategori-bahan-outdoor', KategoriBahanOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('kategori-bahan-outdoor');
    });

    Route::middleware('permission:bahan-outdoor.view')->group(function () {
        Route::resource('bahan-outdoor', BahanOutdoorController::class)->only(['index', 'edit'])->names('bahan-outdoor');
    });
    Route::middleware('permission:bahan-outdoor.manage')->group(function () {
        Route::resource('bahan-outdoor', BahanOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('bahan-outdoor');
    });

    Route::middleware('permission:harga-cetak-outdoor.view')->group(function () {
        Route::resource('harga-cetak-outdoor', HargaCetakOutdoorController::class)->only(['index', 'edit'])->names('harga-cetak-outdoor');
    });
    Route::middleware('permission:harga-cetak-outdoor.manage')->group(function () {
        Route::resource('harga-cetak-outdoor', HargaCetakOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('harga-cetak-outdoor');
    });

    Route::middleware('permission:order-outdoor.view')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['index', 'edit'])->names('order-outdoor');
    });
    Route::middleware('permission:order-outdoor.manage')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-outdoor');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
    });
});

require __DIR__.'/auth.php';
