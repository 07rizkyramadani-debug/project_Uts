<?php

namespace Modules\Membership\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Modules\Membership\Models\MembershipType;
use Modules\Membership\Requests\StoreMembershipTypeRequest;
use Modules\Shared\Exceptions\ResourceInUseException;

/** CRUD Jenis Keanggotaan (khusus admin). */
class MembershipTypeController extends Controller
{
    public function index()
    {
        $types = MembershipType::withCount('members')->orderBy('max_loans')->get();

        return view('library.membership-types.index', compact('types'));
    }

    public function create()
    {
        return view('library.membership-types.form', ['type' => new MembershipType]);
    }

    public function store(StoreMembershipTypeRequest $request)
    {
        $data = $request->validated();

        MembershipType::create($data + ['slug' => Str::slug($data['name'])]);

        return redirect()->route('membership-types.index')->with('success', 'Jenis keanggotaan berhasil ditambahkan.');
    }

    public function edit(MembershipType $membership_type)
    {
        return view('library.membership-types.form', ['type' => $membership_type]);
    }

    public function update(StoreMembershipTypeRequest $request, MembershipType $membership_type)
    {
        $membership_type->update($request->validated());

        return redirect()->route('membership-types.index')->with('success', 'Jenis keanggotaan berhasil diperbarui.');
    }

    public function destroy(MembershipType $membership_type)
    {
        if ($membership_type->slug === MembershipType::DEFAULT_SLUG) {
            throw new ResourceInUseException('Jenis keanggotaan "Umum" adalah jenis bawaan untuk anggota baru dan tidak dapat dihapus.');
        }

        if ($membership_type->members()->exists()) {
            throw new ResourceInUseException("Jenis keanggotaan {$membership_type->name} tidak dapat dihapus karena masih dipakai oleh {$membership_type->members()->count()} anggota.");
        }

        $membership_type->delete();

        return redirect()->route('membership-types.index')->with('success', 'Jenis keanggotaan berhasil dihapus.');
    }
}
