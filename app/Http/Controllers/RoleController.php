<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users')->orderBy('label')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create', ['permissionGroups' => Role::PERMISSION_GROUPS]);
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
