<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')->orderBy('name')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', ['roles' => Role::orderBy('label')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        User::create($data);

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::orderBy('label')->get()]);
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Akun yang punya jurnal manual / tutup buku / gaji tidak boleh dihapus:
        // relasinya CASCADE sehingga data akuntansinya ikut terhapus diam-diam.
        $accountingRows = DB::table('jurnal_manual')->where('user_id', $user->id)->count()
            + DB::table('periode_tutup_buku')->where('ditutup_oleh', $user->id)->count()
            + DB::table('slip_gaji')->where('user_id', $user->id)->count()
            + DB::table('gaji_pegawai')->where('user_id', $user->id)->count();
        if ($accountingRows > 0) {
            return back()->with('error', "User {$user->name} punya riwayat jurnal/akuntansi, jadi tidak dihapus. Gunakan tombol Nonaktifkan.");
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', "User {$user->name} masih punya riwayat transaksi, jadi tidak bisa dihapus. Gunakan tombol Nonaktifkan.");
        }

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }

    /**
     * Nonaktifkan / aktifkan akun. Akun nonaktif tidak bisa login dan semua
     * sesinya langsung diputus; riwayat transaksinya tetap utuh.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->isActive()) {
            $otherAdmins = User::query()->where('is_active', true)->whereKeyNot($user->id)->with('role')->get()
                ->filter(fn (User $other) => $other->hasPermission('roles.manage'))->count();
            if ($user->hasPermission('roles.manage') && $otherAdmins === 0) {
                return back()->with('error', 'Tidak bisa menonaktifkan admin terakhir yang bisa mengelola user.');
            }

            DB::transaction(function () use ($user) {
                $user->forceFill([
                    'is_active' => false,
                    'deactivated_at' => now(),
                    'deactivated_by' => auth()->id(),
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->tokens()->delete();
            });

            return back()->with('status', "Akun {$user->name} dinonaktifkan. Semua sesi login-nya sudah diputus.");
        }

        $user->forceFill(['is_active' => true, 'deactivated_at' => null, 'deactivated_by' => null])->save();

        return back()->with('status', "Akun {$user->name} diaktifkan kembali.");
    }
}
