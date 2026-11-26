@props(['loan'])
@if ($loan->isPending())
    <span class="lib-badge lib-badge-amber">● Menunggu persetujuan</span>
@elseif ($loan->isRejected())
    <span class="lib-badge lib-badge-gray">✕ Ditolak</span>@if ($loan->rejection_reason)<div class="ml-reason">{{ $loan->rejection_reason }}</div>@endif
@elseif ($loan->isReturned())
    <span class="lib-badge lib-badge-green">✓ Dikembalikan</span>
@elseif ($loan->isOverdue())
    <span class="lib-badge lib-badge-red">● Terlambat</span>
@else
    <span class="lib-badge lib-badge-blue">● Dipinjam</span>
@endif
