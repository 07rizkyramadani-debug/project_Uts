<?php

namespace Modules\Membership\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\MembershipType;
use Modules\Membership\Requests\StoreMemberRequest;
use Modules\Shared\Contracts\LoanQueryInterface;
use Modules\Shared\Exceptions\ResourceInUseException;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        // Eager loading: role dan keanggotaan aktif dimuat sekaligus
        $members = Member::with(['user:id,email', 'membershipType:id,name,max_loans,loan_days'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $byStatus = Member::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = [
            'all' => (int) $byStatus->sum(),
            'active' => (int) ($byStatus['active'] ?? 0),
            'borrowing' => (int) ($byStatus['borrowing'] ?? 0),
            'suspended' => (int) ($byStatus['suspended'] ?? 0),
        ];

        return view('library.members.index', compact('members', 'summary'));
    }

    public function create()
    {
        return view('library.members.form', ['member' => new Member, 'types' => MembershipType::orderBy('max_loans')->get()]);
    }

    public function store(StoreMemberRequest $request)
    {
        $data = $request->validated();

        Member::create([
            'membership_type_id' => $data['membership_type_id'] ?? null,
            'member_number' => Member::generateNumber(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Member $member)
    {
        return view('library.members.form', ['member' => $member, 'types' => MembershipType::orderBy('max_loans')->get()]);
    }

    public function update(StoreMemberRequest $request, Member $member)
    {
        $data = $request->validated();

        $member->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? $member->status,
            'membership_type_id' => $data['membership_type_id'] ?? $member->membership_type_id,
        ]);

        return redirect()->route('members.index')->with('success', 'Data anggota berhasil diperbarui.');
    }

    /** Soft delete */
    public function destroy(Member $member, LoanQueryInterface $loans)
    {
        if ($loans->hasActiveLoansForMember($member->id)) {
            throw new ResourceInUseException("Anggota {$member->name} tidak dapat dihapus karena masih memiliki pinjaman aktif atau pengajuan yang menunggu.");
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Anggota dipindahkan ke daftar terhapus (soft delete).');
    }

    public function trashed()
    {
        $members = Member::onlyTrashed()->latest('deleted_at')->paginate(10);

        return view('library.members.trashed', compact('members'));
    }

    public function restore(int $id)
    {
        $member = Member::onlyTrashed()->findOrFail($id);

        // Akun yang sudah dipakai anggota lain saat anggota ini terhapus tidak boleh dipakai ganda
        $released = $member->user_id && Member::where('user_id', $member->user_id)->exists();

        if ($released) {
            $member->user_id = null;
            $member->save();
        }

        $member->restore();

        return redirect()->route('members.trashed')->with('success', 'Anggota berhasil dipulihkan.'
            .($released ? ' Akun user dilepas karena sudah dipakai anggota lain.' : ''));
    }
}
