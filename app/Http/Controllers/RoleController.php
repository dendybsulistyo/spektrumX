<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users')->orderBy('label')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        // Duplikat role: form Tambah Role terisi permission role sumber; kode & nama diubah dulu sebelum disimpan.
        $source = $request->filled('dari') ? Role::query()->find($request->integer('dari')) : null;
        $role = $source ? new Role([
            'name' => $this->copyName($source->name),
            'label' => mb_substr($source->label.' (salinan)', 0, 50),
            'permissions' => $source->permissions ?? [],
        ]) : null;

        return view('roles.create', ['permissionGroups' => Role::PERMISSION_GROUPS, 'role' => $role, 'source' => $source]);
    }

    /** Kode unik untuk salinan: "kasir-salinan", "kasir-salinan-2", dst. */
    private function copyName(string $name): string
    {
        $base = mb_substr($name.'-salinan', 0, 45);
        $candidate = $base;
        for ($n = 2; Role::query()->where('name', $candidate)->exists(); $n++) {
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create($request->validated());

        return redirect()->route('roles.index')->with('status', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', ['role' => $role, 'permissionGroups' => Role::PERMISSION_GROUPS]);
    }

    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        return redirect()->route('roles.index')->with('status', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Role ini masih dipakai oleh user, tidak bisa dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', 'Role berhasil dihapus.');
    }
}
