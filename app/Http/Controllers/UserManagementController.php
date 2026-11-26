<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isAdmin()) {
            abort(403, 'Forbidden. User management is restricted to system administrators.');
        }

        $query = User::withCount('shipments');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->isAdmin()) {
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,operator,customer'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        AuditService::log('USER_CREATED', $newUser, null, $newUser->toArray(), $actor);

        return redirect()->route('users.index')->with('success', "User {$newUser->name} ({$newUser->role}) created successfully.");
    }

    public function update(Request $request, int $id)
    {
        $actor = Auth::user();
        if (!$actor->isAdmin()) {
            abort(403, 'Forbidden.');
        }

        $targetUser = User::findOrFail($id);
        $oldValues = $targetUser->only(['name', 'email', 'role', 'company', 'phone']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$targetUser->id}"],
            'role' => ['required', 'in:admin,operator,customer'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($updateData);

        AuditService::log('USER_UPDATED', $targetUser, $oldValues, $targetUser->only(['name', 'email', 'role', 'company', 'phone']), $actor);

        return redirect()->route('users.index')->with('success', "User {$targetUser->name} updated successfully.");
    }

    public function destroy(int $id)
    {
        $actor = Auth::user();
        if (!$actor->isAdmin()) {
            abort(403, 'Forbidden.');
        }

        if ((int) $actor->id === $id) {
            return back()->with('error', 'You cannot delete your own active administrator account.');
        }

        $targetUser = User::findOrFail($id);
        $oldValues = $targetUser->toArray();
        $name = $targetUser->name;

        $targetUser->delete();

        AuditService::log('USER_DELETED', $targetUser, $oldValues, null, $actor);

        return redirect()->route('users.index')->with('success', "User {$name} has been removed.");
    }
}
