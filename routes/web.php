<?php

use App\Http\Controllers\BahanCetakOutdoorController;
use App\Http\Controllers\BahanOutdoorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataWarehouseController;
use App\Http\Controllers\DetailIndoorController;
use App\Http\Controllers\FileMonitorController;
use App\Http\Controllers\HargaArtworkController;
use App\Http\Controllers\HargaCetakOutdoorController;
use App\Http\Controllers\JasaPotongController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KategoriBahanOutdoorController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KategoriProdukIndoorController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderArtworkController;
use App\Http\Controllers\OrderCetakController;
use App\Http\Controllers\OrderDesainController;
use App\Http\Controllers\OrderIndoorController;
use App\Http\Controllers\OrderOutdoorController;
use App\Http\Controllers\OrderQcController;
use App\Http\Controllers\PengambilanController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\PrinterOutdoorController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers-quick-create', [CustomerController::class, 'quickCreate'])->name('customers.quick-create');

    Route::middleware('permission:data-warehouse.view')->group(function () {
        Route::get('/data-warehouse', [DataWarehouseController::class, 'index'])->name('data-warehouse.index');
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'edit'])->names('customers');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['create', 'store', 'update', 'destroy'])->names('customers');
        Route::get('/customers-suggest-code', [CustomerController::class, 'suggestCode'])->name('customers.suggest-code');
    });

    Route::middleware('permission:produk.view')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['index', 'edit'])->names('produk');
        Route::get('/detail-indoor', [DetailIndoorController::class, 'index'])->name('detail-indoor.index');
    });
    Route::middleware('permission:produk.manage')->group(function () {
        Route::resource('produk', ProdukController::class)->only(['create', 'store', 'update', 'destroy'])->names('produk');
    });

    Route::middleware('permission:harga-artwork.view')->group(function () {
        Route::resource('harga-artwork', HargaArtworkController::class)->only(['index', 'edit'])->names('harga-artwork');
    });
    Route::middleware('permission:harga-artwork.manage')->group(function () {
        Route::resource('harga-artwork', HargaArtworkController::class)->only(['create', 'store', 'update', 'destroy'])->names('harga-artwork');
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

    Route::middleware('permission:printer-outdoor.view')->group(function () {
        Route::resource('printer-outdoor', PrinterOutdoorController::class)->only(['index', 'edit'])->names('printer-outdoor');
    });
    Route::middleware('permission:printer-outdoor.manage')->group(function () {
        Route::resource('printer-outdoor', PrinterOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('printer-outdoor');
    });

    Route::middleware('permission:bahan-cetak-outdoor.view')->group(function () {
        Route::resource('bahan-cetak-outdoor', BahanCetakOutdoorController::class)->only(['index', 'edit'])->names('bahan-cetak-outdoor');
    });
    Route::middleware('permission:bahan-cetak-outdoor.manage')->group(function () {
        Route::resource('bahan-cetak-outdoor', BahanCetakOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('bahan-cetak-outdoor');
    });

    Route::middleware('permission:order-indoor.view')->group(function () {
        Route::resource('order-indoor', OrderIndoorController::class)->only(['index', 'edit'])->names('order-indoor');
    });
    Route::middleware('permission:order-indoor.manage')->group(function () {
        Route::resource('order-indoor', OrderIndoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-indoor');
    });

    Route::middleware('permission:order-artwork.view')->group(function () {
        Route::resource('order-artwork', OrderArtworkController::class)->only(['index', 'edit'])->names('order-artwork');
    });
    Route::middleware('permission:order-artwork.manage')->group(function () {
        Route::resource('order-artwork', OrderArtworkController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-artwork');
    });

    Route::middleware('permission:kategori-bahan-outdoor.view')->group(function () {
        Route::resource('kategori-bahan-outdoor', KategoriBahanOutdoorController::class)->only(['index', 'edit'])->names('kategori-bahan-outdoor');
    });
    Route::middleware('permission:kategori-bahan-outdoor.manage')->group(function () {
        Route::resource('kategori-bahan-outdoor', KategoriBahanOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('kategori-bahan-outdoor');
    });

    Route::middleware('permission:kategori-produk-indoor.view')->group(function () {
        Route::resource('kategori-produk-indoor', KategoriProdukIndoorController::class)->only(['index', 'edit'])->names('kategori-produk-indoor');
    });
    Route::middleware('permission:kategori-produk-indoor.manage')->group(function () {
        Route::resource('kategori-produk-indoor', KategoriProdukIndoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('kategori-produk-indoor');
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
        Route::post('/harga-cetak-outdoor-matrix', [HargaCetakOutdoorController::class, 'updateMatrix'])->name('harga-cetak-outdoor.update-matrix');
    });

    Route::middleware('permission:order-outdoor.view')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['index', 'edit'])->names('order-outdoor');
    });
    Route::middleware('permission:order-outdoor.manage')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-outdoor');
    });
    Route::middleware('permission:order-cetak.manage')->group(function () {
        Route::post('/order-outdoor/{orderOutdoor}/request-cancel', [OrderOutdoorController::class, 'requestCancel'])->name('order-outdoor.request-cancel');
    });
    Route::middleware('permission:order-outdoor.approve-cancel')->group(function () {
        Route::post('/order-outdoor/{orderOutdoor}/approve-cancel', [OrderOutdoorController::class, 'approveCancel'])->name('order-outdoor.approve-cancel');
        Route::post('/order-outdoor/{orderOutdoor}/reject-cancel', [OrderOutdoorController::class, 'rejectCancel'])->name('order-outdoor.reject-cancel');
    });

    Route::middleware('permission:file-monitor.view')->group(function () {
        Route::get('/file', [FileMonitorController::class, 'index'])->name('file.index');
    });

    Route::middleware('permission:kasir.view')->group(function () {
        Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::get('/kasir/{type}/{id}', [KasirController::class, 'show'])->name('kasir.show');
        Route::get('/invoice/{type}/{id}', [InvoiceController::class, 'show'])->name('invoice.show');
    });
    Route::middleware('permission:kasir.manage')->group(function () {
        Route::post('/kasir/{type}/{id}/bayar', [KasirController::class, 'bayar'])->name('kasir.bayar');
        Route::post('/kasir/{type}/{id}/lunasi', [KasirController::class, 'lunasi'])->name('kasir.lunasi');
    });

    Route::middleware('permission:order-desain.view')->group(function () {
        Route::get('/order-desain', [OrderDesainController::class, 'index'])->name('order-desain.index');
    });
    Route::middleware('permission:order-desain.manage')->group(function () {
        Route::post('/order-desain/{type}/{id}', [OrderDesainController::class, 'update'])->name('order-desain.update');
    });

    Route::middleware('permission:order-cetak.view')->group(function () {
        Route::get('/order-cetak', [OrderCetakController::class, 'index'])->name('order-cetak.index');
    });
    Route::middleware('permission:order-cetak.manage')->group(function () {
        Route::post('/order-cetak/{type}/{id}', [OrderCetakController::class, 'update'])->name('order-cetak.update');
    });

    Route::middleware('permission:order-qc.view')->group(function () {
        Route::get('/order-qc', [OrderQcController::class, 'index'])->name('order-qc.index');
    });
    Route::middleware('permission:order-qc.manage')->group(function () {
        Route::post('/order-qc/{type}/{id}', [OrderQcController::class, 'update'])->name('order-qc.update');
    });

    Route::middleware('permission:pengambilan.view')->group(function () {
        Route::get('/pengambilan', [PengambilanController::class, 'index'])->name('pengambilan.index');
    });
    Route::middleware('permission:pengambilan.manage')->group(function () {
        Route::post('/pengambilan/{type}/{id}', [PengambilanController::class, 'serahkan'])->name('pengambilan.serahkan');
    });

    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{user}', [ChatController::class, 'show'])->name('show');
        Route::post('/{user}', [ChatController::class, 'store'])->name('store');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
    });

    Route::middleware('permission:jasa-potong.manage')->group(function () {
        Route::get('/jasa-potong', [JasaPotongController::class, 'edit'])->name('jasa-potong.edit');
        Route::put('/jasa-potong', [JasaPotongController::class, 'update'])->name('jasa-potong.update');
    });
});

require __DIR__.'/auth.php';
