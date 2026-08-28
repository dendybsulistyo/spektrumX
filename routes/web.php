<?php

use App\Http\Controllers\BahanCetakOutdoorController;
use App\Http\Controllers\BahanOutdoorController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\AccountingFixedAssetController;
use App\Http\Controllers\AccountingSupplierController;
use App\Http\Controllers\AccountingPurchaseController;
use App\Http\Controllers\InventoryHppController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataWarehouseController;
use App\Http\Controllers\PreviewCetakController;
use App\Http\Controllers\MonitoringKinerjaController;
use App\Http\Controllers\MonitoringTransaksiController;
use App\Http\Controllers\DetailIndoorController;
use App\Http\Controllers\FileMonitorController;
use App\Http\Controllers\GunggunganController;
use App\Http\Controllers\GunggunganHistoricalJournalController;
use App\Http\Controllers\HargaArtworkController;
use App\Http\Controllers\HargaCetakOutdoorController;
use App\Http\Controllers\HargaCetakOutdoorKhususController;
use App\Http\Controllers\HutangApprovalController;
use App\Http\Controllers\JasaPotongArtworkController;
use App\Http\Controllers\JasaPotongController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\LaporanAkuntansiController;
use App\Http\Controllers\KategoriBahanOutdoorController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KategoriProdukIndoorController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderArtworkController;
use App\Http\Controllers\OrderBungkusController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OrderCetakController;
use App\Http\Controllers\OrderDesainController;
use App\Http\Controllers\OrderFinishingController;
use App\Http\Controllers\OrderIndoorController;
use App\Http\Controllers\OrderCommentController;
use App\Http\Controllers\OrderOutdoorController;
use App\Http\Controllers\OrderQcController;
use App\Http\Controllers\OrderReworkController;
use App\Http\Controllers\PapanPantauController;
use App\Http\Controllers\DiskonApprovalController;
use App\Http\Controllers\PembatalanController;
use App\Http\Controllers\PengambilanController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\PrinterOutdoorController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JurnalManualController;
use App\Http\Controllers\PengaturanKeuanganController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\TutupBukuController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/order-progress/{type}/{id}', [DashboardController::class, 'orderProgress'])->middleware(['auth', 'verified'])->name('dashboard.order-progress');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers-quick-create', [CustomerController::class, 'quickCreate'])->name('customers.quick-create');
    Route::get('/customers/{customer:KdCust}/harga-cetak-outdoor-khusus', [CustomerController::class, 'hargaCetakOutdoorKhusus'])->name('customers.harga-cetak-outdoor-khusus');

    Route::middleware('permission:preview-cetak.view')->group(function () {
        Route::get('/preview-cetak', [PreviewCetakController::class, 'index'])->name('preview-cetak.index');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/monitor-server', [ServerMonitorController::class, 'index'])->name('server-monitor.index');
    });

    Route::middleware('permission:data-warehouse.view')->group(function () {
        Route::get('/data-warehouse', [DataWarehouseController::class, 'index'])->name('data-warehouse.index');
    });

    Route::middleware('permission:monitoring-kinerja.view')->group(function () {
        Route::get('/monitoring-kinerja', [MonitoringKinerjaController::class, 'index'])->name('monitoring-kinerja.index');
    });

    Route::middleware('permission:monitoring-transaksi.view')->group(function () {
        Route::get('/monitoring-transaksi', [MonitoringTransaksiController::class, 'index'])->name('monitoring-transaksi.index');
    });

    Route::middleware('permission:papan-pantau.view')->group(function () {
        Route::get('/papan-pantau', [PapanPantauController::class, 'index'])->name('papan-pantau.index');
    });

    Route::middleware('permission:keuangan.view')->group(function () {
        Route::get('/akuntansi/akun', [AkunController::class, 'index'])->name('akuntansi.akun.index');
        Route::get('/akuntansi/suppliers', [AccountingSupplierController::class, 'index'])->name('akuntansi.suppliers.index');
        Route::get('/akuntansi/pembelian', [AccountingPurchaseController::class, 'index'])->name('akuntansi.purchases.index');
        Route::get('/akuntansi/laporan-pembelian', [AccountingPurchaseController::class, 'report'])->name('akuntansi.purchases.report');
        Route::get('/akuntansi/gunggungan', [GunggunganController::class, 'index'])->name('akuntansi.gunggungan');
        Route::get('/akuntansi/rekap-omset', [GunggunganController::class, 'rekapOmset'])->name('akuntansi.rekap-omset');
        Route::get('/akuntansi/import-gunggungan', [GunggunganHistoricalJournalController::class, 'index'])->name('akuntansi.import-gunggungan');
        Route::get('/akuntansi/jurnal-umum', [LaporanAkuntansiController::class, 'jurnalUmum'])->name('akuntansi.jurnal-umum');
        Route::get('/akuntansi/buku-besar', [LaporanAkuntansiController::class, 'bukuBesar'])->name('akuntansi.buku-besar');
        Route::get('/akuntansi/neraca-saldo', [LaporanAkuntansiController::class, 'neracaSaldo'])->name('akuntansi.neraca-saldo');
        Route::get('/akuntansi/hutang-supplier', [LaporanAkuntansiController::class, 'hutangSupplier'])->name('akuntansi.hutang-supplier');
        Route::get('/akuntansi/piutang-customer', [LaporanAkuntansiController::class, 'piutangCustomer'])->name('akuntansi.piutang-customer');
        Route::get('/akuntansi/kas-bank', [LaporanAkuntansiController::class, 'kasBank'])->name('akuntansi.kas-bank');
        Route::get('/akuntansi/neraca', [LaporanAkuntansiController::class, 'neraca'])->name('akuntansi.neraca');
        Route::get('/akuntansi/perubahan-modal', [LaporanAkuntansiController::class, 'perubahanModal'])->name('akuntansi.perubahan-modal');
        Route::get('/akuntansi/persediaan-hpp', [InventoryHppController::class, 'index'])->name('akuntansi.inventory-hpp');
        Route::get('/akuntansi/laporan-hpp', [InventoryHppController::class, 'report'])->name('akuntansi.hpp-report');
        Route::get('/akuntansi/aset-tetap', [AccountingFixedAssetController::class, 'index'])->name('akuntansi.fixed-assets.index');
        Route::get('/keuangan/kas-harian', [KeuanganController::class, 'kasHarian'])->name('keuangan.kas-harian');
        Route::get('/keuangan/rekap-kasir', [KeuanganController::class, 'rekapKasir'])->name('keuangan.rekap-kasir');
        Route::get('/keuangan/rekap-kasir/{kasir}/customer', [KeuanganController::class, 'rekapKasirCustomer'])->name('keuangan.rekap-kasir.customer');
        Route::get('/keuangan/rekap-customer', [KeuanganController::class, 'rekapCustomer'])->name('keuangan.rekap-customer');
        Route::get('/keuangan/piutang', [KeuanganController::class, 'piutang'])->name('keuangan.piutang');
        Route::get('/keuangan/laba-rugi', [KeuanganController::class, 'labaRugi'])->name('keuangan.laba-rugi');
        Route::get('/keuangan/tutup-buku', [TutupBukuController::class, 'index'])->name('keuangan.tutup-buku');
        Route::get('/keuangan/tutup-buku/preview', [TutupBukuController::class, 'preview'])->name('keuangan.tutup-buku.preview');
        Route::get('/keuangan/jurnal-manual', [JurnalManualController::class, 'index'])->name('keuangan.jurnal-manual');
        Route::get('/keuangan/laporan-ppn', [KeuanganController::class, 'laporanPpn'])->name('keuangan.laporan-ppn');
        Route::get('/keuangan/laporan-ppn/export', [KeuanganController::class, 'exportPpn'])->name('keuangan.laporan-ppn.export');
    });

    Route::middleware('permission:keuangan.tutup-buku')->group(function () {
        Route::post('/keuangan/tutup-buku', [TutupBukuController::class, 'store'])->name('keuangan.tutup-buku.store');
        Route::delete('/keuangan/tutup-buku/{periodeTutupBuku}', [TutupBukuController::class, 'destroy'])->name('keuangan.tutup-buku.destroy');
    });

    Route::middleware('permission:keuangan.jurnal-manual')->group(function () {
        Route::post('/keuangan/jurnal-manual', [JurnalManualController::class, 'store'])->name('keuangan.jurnal-manual.store');
        Route::post('/keuangan/jurnal-manual/{jurnalManual}/batalkan', [JurnalManualController::class, 'batalkan'])->name('keuangan.jurnal-manual.batalkan');
    });

    Route::middleware('permission:keuangan.pengaturan')->group(function () {
        Route::post('/keuangan/laporan-ppn/draft', [KeuanganController::class, 'simpanDraftPpn'])->name('keuangan.laporan-ppn.draft');
        Route::post('/keuangan/laporan-ppn/{laporanPpnFinal}/finalkan', [KeuanganController::class, 'finalkanPpn'])->name('keuangan.laporan-ppn.finalkan');
    });

    Route::middleware('permission:keuangan.pengaturan')->group(function () {
        Route::post('/akuntansi/pembelian', [AccountingPurchaseController::class, 'store'])->name('akuntansi.purchases.store');
        Route::post('/akuntansi/pembelian/{purchase}/pelunasan', [AccountingPurchaseController::class, 'pay'])->name('akuntansi.purchases.pay');
        Route::post('/akuntansi/pembelian/{purchase}/retur', [AccountingPurchaseController::class, 'return'])->name('akuntansi.purchases.return');
        Route::post('/akuntansi/persediaan', [InventoryHppController::class, 'storeItem'])->name('akuntansi.inventory.store-item');
        Route::post('/akuntansi/persediaan/{item}/opname', [InventoryHppController::class, 'storeCount'])->name('akuntansi.inventory.store-count');
        Route::post('/akuntansi/suppliers', [AccountingSupplierController::class, 'store'])->name('akuntansi.suppliers.store');
        Route::put('/akuntansi/suppliers/{supplier}', [AccountingSupplierController::class, 'update'])->name('akuntansi.suppliers.update');
        Route::delete('/akuntansi/suppliers/{supplier}', [AccountingSupplierController::class, 'destroy'])->name('akuntansi.suppliers.destroy');
        Route::post('/akuntansi/import-gunggungan', [GunggunganHistoricalJournalController::class, 'store'])->name('akuntansi.import-gunggungan.store');
        Route::post('/akuntansi/akun', [AkunController::class, 'store'])->name('akuntansi.akun.store');
        Route::put('/akuntansi/akun/{akun}', [AkunController::class, 'update'])->name('akuntansi.akun.update');
        Route::delete('/akuntansi/akun/{akun}', [AkunController::class, 'destroy'])->name('akuntansi.akun.destroy');
        Route::post('/akuntansi/aset-tetap', [AccountingFixedAssetController::class, 'store'])->name('akuntansi.fixed-assets.store');
        Route::delete('/akuntansi/aset-tetap/{fixedAsset}', [AccountingFixedAssetController::class, 'destroy'])->name('akuntansi.fixed-assets.destroy');
        Route::get('/keuangan/pengaturan', [PengaturanKeuanganController::class, 'edit'])->name('keuangan.pengaturan.edit');
        Route::put('/keuangan/pengaturan', [PengaturanKeuanganController::class, 'update'])->name('keuangan.pengaturan.update');
    });

    Route::middleware('permission:pengeluaran.view')->group(function () {
        Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
    });
    Route::middleware('permission:pengeluaran.manage')->group(function () {
        Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('pengeluaran.store');
        Route::put('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'update'])->name('pengeluaran.update');
        Route::delete('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'destroy'])->name('pengeluaran.destroy');
    });

    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    });
    Route::middleware('permission:payroll.manage')->group(function () {
        Route::post('/payroll/gaji/{user}', [PayrollController::class, 'updateGaji'])->name('payroll.update-gaji');
        Route::post('/payroll/proses', [PayrollController::class, 'proses'])->name('payroll.proses');
        Route::post('/payroll/{slipGaji}/bayar', [PayrollController::class, 'bayar'])->name('payroll.bayar');
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'edit'])->names('customers');
        Route::get('/customers-aktif', [CustomerController::class, 'aktif'])->name('customers.aktif');
        Route::get('/customers/{customer:KdCust}', [CustomerController::class, 'show'])->name('customers.show');
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
    Route::middleware('permission:order-desain.manage')->group(function () {
        Route::post('/order-indoor/{orderIndoor}/request-cancel', [OrderIndoorController::class, 'requestCancel'])->name('order-indoor.request-cancel');
    });
    Route::middleware('permission:order-indoor.approve-cancel')->group(function () {
        Route::post('/order-indoor/{orderIndoor}/approve-cancel', [OrderIndoorController::class, 'approveCancel'])->name('order-indoor.approve-cancel');
        Route::post('/order-indoor/{orderIndoor}/reject-cancel', [OrderIndoorController::class, 'rejectCancel'])->name('order-indoor.reject-cancel');
    });

    Route::middleware('permission:order-artwork.view')->group(function () {
        Route::resource('order-artwork', OrderArtworkController::class)->only(['index', 'edit'])->names('order-artwork');
    });
    Route::middleware('permission:order-artwork.manage')->group(function () {
        Route::resource('order-artwork', OrderArtworkController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-artwork');
    });
    Route::middleware('permission:order-desain.manage')->group(function () {
        Route::post('/order-artwork/{orderArtwork}/request-cancel', [OrderArtworkController::class, 'requestCancel'])->name('order-artwork.request-cancel');
    });
    Route::middleware('permission:order-artwork.approve-cancel')->group(function () {
        Route::post('/order-artwork/{orderArtwork}/approve-cancel', [OrderArtworkController::class, 'approveCancel'])->name('order-artwork.approve-cancel');
        Route::post('/order-artwork/{orderArtwork}/reject-cancel', [OrderArtworkController::class, 'rejectCancel'])->name('order-artwork.reject-cancel');
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

    Route::middleware('permission:harga-cetak-outdoor-khusus.view')->group(function () {
        Route::get('/harga-cetak-outdoor-khusus', [HargaCetakOutdoorKhususController::class, 'index'])->name('harga-cetak-outdoor-khusus.index');
    });
    Route::middleware('permission:harga-cetak-outdoor-khusus.manage')->group(function () {
        Route::post('/harga-cetak-outdoor-khusus-matrix', [HargaCetakOutdoorKhususController::class, 'updateMatrix'])->name('harga-cetak-outdoor-khusus.update-matrix');
    });

    Route::middleware('permission:order-outdoor.view')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['index', 'edit'])->names('order-outdoor');
    });
    Route::middleware('permission:order-outdoor.manage')->group(function () {
        Route::resource('order-outdoor', OrderOutdoorController::class)->only(['create', 'store', 'update', 'destroy'])->names('order-outdoor');
    });
    Route::middleware('permission:order-desain.manage')->group(function () {
        Route::post('/order-outdoor/{orderOutdoor}/request-cancel', [OrderOutdoorController::class, 'requestCancel'])->name('order-outdoor.request-cancel');
    });
    Route::middleware('permission:order-outdoor.approve-cancel')->group(function () {
        Route::post('/order-outdoor/{orderOutdoor}/approve-cancel', [OrderOutdoorController::class, 'approveCancel'])->name('order-outdoor.approve-cancel');
        Route::post('/order-outdoor/{orderOutdoor}/reject-cancel', [OrderOutdoorController::class, 'rejectCancel'])->name('order-outdoor.reject-cancel');
    });

    Route::middleware('permission:file-monitor.view')->group(function () {
        Route::get('/file', [FileMonitorController::class, 'index'])->name('file.index');
    });

    // Not gated to a single permission group — Kasir and Pengambilan operators
    // both need to check the nota; InvoiceController::show() checks the
    // permission itself (kasir.view OR pengambilan.view).
    Route::get('/invoice/{type}/{id}', [InvoiceController::class, 'show'])->name('invoice.show');

    Route::middleware('permission:kasir.view')->group(function () {
        Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::get('/kasir/{type}/{id}', [KasirController::class, 'show'])->name('kasir.show');
    });
    Route::middleware('permission:kasir.manage')->group(function () {
        Route::post('/kasir/{type}/{id}/bayar', [KasirController::class, 'bayar'])->name('kasir.bayar');
        Route::post('/kasir/{type}/{id}/lunasi', [KasirController::class, 'lunasi'])->name('kasir.lunasi');
        Route::post('/kasir/{type}/{id}/lunasi-hutang', [KasirController::class, 'lunasiHutang'])->name('kasir.lunasi-hutang');
        Route::post('/kasir/{type}/{id}/diskon/request', [KasirController::class, 'requestDiskon'])->name('kasir.diskon.request');
    });
    // Nota pengganti is created by Operator File, not by Kasir — its own
    // permission instead of piggybacking on kasir.manage.
    Route::middleware('permission:kasir.replacement.manage')->group(function () {
        Route::get('/kasir/outdoor/{orderOutdoor}/nota-pengganti', [OrderOutdoorController::class, 'createReplacement'])->name('kasir.replacement.create');
        Route::post('/kasir/outdoor/nota-pengganti', [OrderOutdoorController::class, 'store'])->name('kasir.replacement.store');
        Route::get('/kasir/indoor/{orderIndoor}/nota-pengganti', [OrderIndoorController::class, 'createReplacement'])->name('kasir.replacement.create.indoor');
        Route::post('/kasir/indoor/nota-pengganti', [OrderIndoorController::class, 'store'])->name('kasir.replacement.store.indoor');
        Route::get('/kasir/artwork/{orderArtwork}/nota-pengganti', [OrderArtworkController::class, 'createReplacement'])->name('kasir.replacement.create.artwork');
        Route::post('/kasir/artwork/nota-pengganti', [OrderArtworkController::class, 'store'])->name('kasir.replacement.store.artwork');
    });
    Route::middleware('permission:kasir.approve-diskon')->group(function () {
        Route::post('/kasir/{type}/{id}/diskon/approve', [KasirController::class, 'approveDiskon'])->name('kasir.diskon.approve');
        Route::post('/kasir/{type}/{id}/diskon/reject', [KasirController::class, 'rejectDiskon'])->name('kasir.diskon.reject');
    });
    Route::middleware('permission:kasir.approve-hutang')->group(function () {
        Route::post('/kasir/{type}/{id}/hutang/approve', [KasirController::class, 'approveHutang'])->name('kasir.hutang.approve');
        Route::post('/kasir/{type}/{id}/hutang/reject', [KasirController::class, 'rejectHutang'])->name('kasir.hutang.reject');
    });

    Route::middleware('permission:order-desain.view')->group(function () {
        Route::get('/order-desain', [OrderDesainController::class, 'index'])->name('order-desain.index');
        Route::get('/order-desain/version', [OrderDesainController::class, 'version'])->name('order-desain.version');
    });
    Route::middleware('permission:order-desain.manage')->group(function () {
        Route::post('/order-desain/gabungan/{item}', [OrderDesainController::class, 'updateGabungan'])->name('order-desain.gabungan');
        Route::post('/order-desain/progress/{type}/{id}', [OrderDesainController::class, 'updateItem'])->name('order-desain.progress');
    });
    Route::middleware('permission:order-desain.nmfile-manage')->group(function () {
        Route::post('/order-desain/nmfile/{item}', [OrderDesainController::class, 'updateNmFile'])->name('order-desain.nmfile');
    });

    Route::middleware('permission:order-cetak.view')->group(function () {
        Route::get('/order-cetak', [OrderCetakController::class, 'index'])->name('order-cetak.index');
    });
    Route::middleware('permission:order-cetak.manage')->group(function () {
        Route::post('/order-cetak/{type}/{id}', [OrderCetakController::class, 'updateItem'])->name('order-cetak.update');
    });

    Route::post('/order-comments/{type}/{id}', [OrderCommentController::class, 'store'])->name('order-comments.store');
    Route::post('/order-comments/{type}/{id}/read', [OrderCommentController::class, 'markRead'])->name('order-comments.read');

    Route::middleware('permission:order-rework.approve')->group(function () {
        Route::post('/order-rework/{orderReworkRequest}/approve', [OrderReworkController::class, 'approve'])->name('order-rework.approve');
        Route::post('/order-rework/{orderReworkRequest}/reject', [OrderReworkController::class, 'reject'])->name('order-rework.reject');
    });

    // Access controlled inside the controller (any of the 3 approve-cancel
    // permissions) rather than a single route-level gate — this is a
    // consolidated view of data each type's routes already protect.
    Route::get('/pembatalan', [PembatalanController::class, 'index'])->name('pembatalan.index');
    Route::post('/order-rework/{type}/{id}', [OrderReworkController::class, 'store'])->name('order-rework.store');

    // Access controlled inside the controller (kasir.approve-diskon).
    Route::get('/approval-diskon', [DiskonApprovalController::class, 'index'])->name('diskon-approval.index');

    // Access controlled inside the controller (kasir.approve-hutang).
    Route::get('/approval-hutang', [HutangApprovalController::class, 'index'])->name('hutang-approval.index');

    Route::middleware('permission:order-finishing.view')->group(function () {
        Route::get('/order-finishing', [OrderFinishingController::class, 'index'])->name('order-finishing.index');
    });
    Route::middleware('permission:order-finishing.manage')->group(function () {
        Route::post('/order-finishing/{type}/{id}', [OrderFinishingController::class, 'updateItem'])->name('order-finishing.update');
    });

    Route::middleware('permission:order-qc.view')->group(function () {
        Route::get('/order-qc', [OrderQcController::class, 'index'])->name('order-qc.index');
    });
    Route::middleware('permission:order-qc.manage')->group(function () {
        Route::post('/order-qc/{type}/{id}', [OrderQcController::class, 'updateItem'])->name('order-qc.update');
    });

    Route::middleware('permission:order-bungkus.view')->group(function () {
        Route::get('/order-bungkus', [OrderBungkusController::class, 'index'])->name('order-bungkus.index');
    });
    Route::middleware('permission:order-bungkus.manage')->group(function () {
        Route::post('/order-bungkus/{type}/{id}', [OrderBungkusController::class, 'updateItem'])->name('order-bungkus.update');
    });

    Route::middleware('permission:pengambilan.view')->group(function () {
        Route::get('/pengambilan', [PengambilanController::class, 'index'])->name('pengambilan.index');
    });
    Route::middleware('permission:pengambilan.manage')->group(function () {
        Route::post('/pengambilan/{type}/{id}', [PengambilanController::class, 'updateItem'])->name('pengambilan.serahkan');
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

    Route::middleware('permission:jasa-potong-artwork.manage')->group(function () {
        Route::get('/jasa-potong-artwork', [JasaPotongArtworkController::class, 'edit'])->name('jasa-potong-artwork.edit');
        Route::put('/jasa-potong-artwork', [JasaPotongArtworkController::class, 'update'])->name('jasa-potong-artwork.update');
    });

    Route::middleware('permission:harga-cetak-outdoor.view')->group(function () {
        Route::get('/report/price-list-outdoor', [ReportController::class, 'priceListOutdoor'])->name('report.price-list-outdoor');
    });
});

require __DIR__.'/auth.php';
