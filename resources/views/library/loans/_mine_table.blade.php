{{-- Tabel pinjaman milik user: dipakai untuk riwayat dan hasil filter. Butuh: $loans, $covers --}}
<div style="overflow-x:auto">
    <table class="lib-table">
        <thead><tr>
            <th class="py-3 px-4 text-left">No. Pinjam</th><th class="py-3 px-4 text-left">Buku</th>
            <th class="py-3 px-4 text-left">Tgl. pinjam</th><th class="py-3 px-4 text-left">Jatuh tempo</th>
            <th class="py-3 px-4 text-left">Dikembalikan</th><th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-right">Denda</th>
        </tr></thead>
        <tbody class="text-gray-600">
            @forelse ($loans as $loan)
                <tr class="border-b">
                    <td class="py-3 px-4 lib-nowrap">
                        {{ $loan->loan_number }}
                        <div class="text-xs text-gray-400">diajukan {{ $loan->created_at->format('d M Y') }}</div>
                    </td>
                    <td class="py-3 px-4">
                        @foreach ($loan->details as $detail)
                            <div class="ml-bookline">
                                <x-mini-cover :url="$covers[$detail->book_id]['cover_url'] ?? null" :title="$detail->book_title" :seed="$detail->book_id" width="2rem" />
                                <span>{{ $detail->book_title }}@if ($detail->quantity > 1) ×{{ $detail->quantity }}@endif <span class="text-xs text-gray-400">{{ $detail->book_isbn }}</span></span>
                            </div>
                        @endforeach
                        @if ($loan->notes)<div class="text-xs text-gray-400">Catatan: {{ $loan->notes }}</div>@endif
                    </td>
                    <td class="py-3 px-4 lib-nowrap">{{ $loan->borrowed_at?->format('d M Y') ?? '-' }}</td>
                    <td class="py-3 px-4 lib-nowrap">
                        {{ $loan->due_at?->format('d M Y') ?? '-' }}
                        @if ($loan->isBorrowed())
                            <div class="text-xs {{ $loan->isOverdue() ? 'text-red-600' : 'text-gray-400' }}">
                                @if ($loan->isOverdue()) terlambat {{ (int) ceil($loan->due_at->diffInDays(now(), true)) }} hari
                                @else {{ (int) ceil(now()->diffInDays($loan->due_at, true)) }} hari lagi @endif
                            </div>
                        @endif
                    </td>
                    <td class="py-3 px-4 lib-nowrap">{{ $loan->returned_at?->format('d M Y') ?? '-' }}</td>
                    <td class="py-3 px-4 text-center"><x-loan-status :loan="$loan" /></td>
                    <td class="py-3 px-4 text-right lib-nowrap">
                        Rp {{ number_format($loan->accruedFine(), 0, ',', '.') }}
                        @if ($loan->isBorrowed() && $loan->accruedFine() > 0)<div class="text-xs text-gray-400">berjalan</div>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-4 text-center text-gray-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
