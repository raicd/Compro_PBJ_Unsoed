<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


// Controllers
use App\Http\Controllers\Unit\UnitController;
use App\Http\Controllers\PPK\PpkController;

/*
|--------------------------------------------------------------------------
| Public / Guest Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'Landing.Index')->name('landing');

// Login (GET form)
Route::get('/login', function () {
    return view('Auth.login');
})->name('login');

// Login (POST proses) ✅ CAPTCHA DIAKTIFKAN
Route::post('/login', function (Request $request) {

    // ✅ Validasi form + captcha
    $request->validate([
        'email'                 => ['required', 'email'],
        'password'              => ['required'],
        'g-recaptcha-response'  => ['required'],
    ], [
        'g-recaptcha-response.required' => 'Harap verifikasi captcha terlebih dahulu.',
    ]);

    // ✅ Verifikasi CAPTCHA ke Google
    $captchaResponse = $request->input('g-recaptcha-response');

    $verify = Http::asForm()->post(
        'https://www.google.com/recaptcha/api/siteverify',
        [
            'secret'   => config('services.recaptcha.secret_key'),
            'response' => $captchaResponse,
            'remoteip' => $request->ip(),
        ]
    )->json();

    if (!($verify['success'] ?? false)) {
        return back()
            ->withErrors(['g-recaptcha-response' => 'Captcha tidak valid. Silakan coba lagi.'])
            ->withInput($request->only('email'));
    }

    // ✅ Login normal
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->route('home');
    }

    return back()
        ->withErrors(['email' => 'Email atau kata sandi salah.'])
        ->withInput($request->only('email'));

})->name('login.post');


// Logout (POST)
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('landing');
})->name('logout');

// Logout (GET fallback)
Route::get('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('landing');
})->name('logout.get');

// Homepage & Preview
Route::view('/home', 'Home.index')->name('home');
Route::view('/home-preview', 'Home.index')->name('home.preview');

// Arsip Publik (Landing)
// ✅ Nama route tetap "ArsipPBJ" biar navbar lama tidak error
Route::view('/ArsipPBJ', 'Landing.pbj')->name('ArsipPBJ');

// ✅ Alias "landing.pbj" TANPA bentrok URI (pakai redirect ke /ArsipPBJ)
Route::redirect('/landing/ArsipPBJ', '/ArsipPBJ')->name('landing.pbj');

// Arsip Publik (Home)
Route::view('/home/ArsipPBJ', 'Home.pbj')->name('home.pbj');

// Redirect alias lama
Route::redirect('/home/arsippbj', '/home/ArsipPBJ')->name('home.arsippbj');
Route::redirect('/home/arsip-pbj', '/home/ArsipPBJ');

// Detail arsip publik
Route::get('/arsip/{id}', function ($id) {
    return view('Landing.LihatDetail', compact('id'));
})->name('arsip.detail');


/**
 * ✅✅ FILE VIEWER (PUBLIC)
 * Dipakai Landing (guest) dan juga bisa dipakai user login.
 * GET /file-viewer?file=...&mode=public
 */
Route::get('/file-viewer', [UnitController::class, 'fileViewer'])
    ->name('file.viewer');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    /**
     * ✅✅ BARU: DASHBOARD REDIRECT BERDASARKAN ROLE
     * Dipakai untuk link "Dasbor" di navbar Home.
     */
    Route::get('/home/dashboard', function () {
        $role = strtolower(trim((string)(auth()->user()->role ?? '')));

        // normalisasi nama role yang sering beda-beda
        if (in_array($role, ['ppk', 'ppk utama', 'ppk_utama'], true)) {
            return redirect()->route('ppk.dashboard');
        }

        if (in_array($role, ['unit', 'admin unit', 'admin_unit'], true)) {
            return redirect()->route('unit.dashboard');
        }

        return redirect()->route('home');
    })->name('home.dashboard');

    /*
    |--------------------------------------------------------------------------
    | UNIT ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('unit')->name('unit.')->group(function () {

        Route::get('/dashboard', [UnitController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/dashboard/stats', [UnitController::class, 'dashboardStats'])
            ->name('dashboard.stats');

        Route::get('/dashboard/data', [UnitController::class, 'dashboardStats'])
            ->name('dashboard.data');

        Route::get('/arsip', [UnitController::class, 'arsipIndex'])
            ->name('arsip');

        // alias
        Route::get('/arsippbj', [UnitController::class, 'arsipIndex'])
            ->name('arsippbj');

        Route::get('/arsip-pbj', [UnitController::class, 'arsipIndex'])
            ->name('arsip.pbj');

        /**
         * ✅✅ EXPORT ARSIP UNIT (CSV)
         * Penting: taruh sebelum route dinamis /arsip/{id}/...
         * URL contoh: /unit/arsip/export?status=Publik&tahun=2025&q=...
         */
        Route::get('/arsip/export', [UnitController::class, 'arsipExport'])
            ->name('arsip.export');

        // ✅ EDIT + UPDATE
        Route::get('/arsip/{id}/edit', [UnitController::class, 'arsipEdit'])
            ->name('arsip.edit');

        Route::put('/arsip/{id}', [UnitController::class, 'arsipUpdate'])
            ->name('arsip.update');

        // ✅ DELETE (bulk + single)
        Route::delete('/arsip', [UnitController::class, 'arsipBulkDestroy'])
            ->name('arsip.bulkDestroy');

        Route::delete('/arsip/{id}', [UnitController::class, 'arsipDestroy'])
            ->name('arsip.destroy');

        // ✅ CREATE
        Route::get('/pengadaan/tambah', [UnitController::class, 'pengadaanCreate'])
            ->name('pengadaan.create');

        Route::post('/pengadaan/store', [UnitController::class, 'pengadaanStore'])
            ->name('pengadaan.store');

        /**
         * ✅ LIHAT dokumen (INLINE)
         * showDokumen -> redirect ke route('file.viewer', ['file' => '/storage/...'])
         */
        Route::get('/arsip/{id}/dokumen/{field}/{file}', [UnitController::class, 'showDokumen'])
            ->where(['field' => '[A-Za-z0-9_\-]+', 'file' => '.+'])
            ->name('arsip.dokumen.show');

        Route::delete('/arsip/{id}/dokumen', [UnitController::class, 'hapusDokumenFile'])
            ->name('arsip.dokumen.hapus');

        Route::get('/arsip/{id}/dokumen-download', [UnitController::class, 'downloadDokumen'])
            ->name('arsip.dokumen.download');

        // ✅ AKUN
        Route::get('/kelola-akun', [UnitController::class, 'kelolaAkun'])
            ->name('kelola.akun');

        Route::put('/akun', [UnitController::class, 'updateAkun'])
            ->name('akun.update');
    });

    /*
    |--------------------------------------------------------------------------
    | PPK ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('ppk')->name('ppk.')->group(function () {

        Route::get('/dashboard', [PpkController::class, 'dashboard'])
            ->name('dashboard');

        // ✅ TAMBAHAN: endpoint statistik dashboard (biar route('ppk.dashboard.data') gak error)
        Route::get('/dashboard/data', [PpkController::class, 'dashboardData'])
            ->name('dashboard.data');

        Route::get('/arsip', [PpkController::class, 'arsipIndex'])
            ->name('arsip');

        Route::get('/arsip/{id}/edit', [PpkController::class, 'arsipEdit'])
            ->name('arsip.edit');

        Route::put('/arsip/{id}', [PpkController::class, 'arsipUpdate'])
            ->name('arsip.update');

        // ✅✅ FIX UTAMA: ROUTE HAPUS ARSIP PPK (sesuai yang dipanggil Blade: /ppk/arsip/{id}/delete)
        Route::delete('/arsip/{id}/delete', [PpkController::class, 'arsipDelete'])
            ->name('arsip.delete');

        Route::get('/pengadaan/tambah', [PpkController::class, 'pengadaanCreate'])
            ->name('pengadaan.create');

        Route::post('/pengadaan/store', [PpkController::class, 'pengadaanStore'])
            ->name('pengadaan.store');

        /**
         * ✅ ROUTE DOKUMEN PPK (WAJIB, dipakai buildDokumenList & showDokumen)
         * route('ppk.arsip.dokumen.show', ...)
         */
        Route::get('/arsip/{id}/dokumen/{field}/{file}', [PpkController::class, 'showDokumen'])
            ->where(['field' => '[A-Za-z0-9_\-]+', 'file' => '.+'])
            ->name('arsip.dokumen.show');

        Route::get('/kelola-akun', [PpkController::class, 'kelolaAkun'])
            ->name('kelola.akun');

        Route::put('/akun', [PpkController::class, 'updateAkun'])
            ->name('akun.update');
    });

});