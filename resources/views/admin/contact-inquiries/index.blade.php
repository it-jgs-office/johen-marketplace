@extends('admin.layouts.app')

@section('title', 'Pesan Masuk')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-lg font-semibold">Pesan Masuk</h2>
    <span class="badge badge-neutral">{{ $inquiries->total() }} total</span>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Nama</th>
                    <th>Email / Telepon</th>
                    <th>Kategori</th>
                    <th>Pesan</th>
                    <th>Tanggal</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inquiries as $inq)
                @php
                $catColors = [
                    'topup' => ['bg' => 'rgba(133,77,234,0.15)', 'text' => '#9d5cf5'],
                    'jual-beli-akun' => ['bg' => 'rgba(245,158,11,0.15)', 'text' => '#d97706'],
                    'pembayaran' => ['bg' => 'rgba(59,130,246,0.15)', 'text' => '#3b82f6'],
                    'keluhan' => ['bg' => 'rgba(239,68,68,0.15)', 'text' => '#dc2626'],
                    'saran' => ['bg' => 'rgba(16,185,129,0.15)', 'text' => '#059669'],
                ];
                $cc = $catColors[$inq->category] ?? ['bg' => 'rgba(100,116,139,0.15)', 'text' => '#64748b'];
                @endphp
                <tr class="{{ !$inq->is_read && !$inq->responded_at ? 'font-semibold' : '' }}" style="{{ !$inq->is_read && !$inq->responded_at ? 'background:var(--glass-bg)' : '' }}">
                    <td>
                        @if($inq->responded_at)
                            <span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(16,185,129,0.15);color:#059669">Direspon</span>
                        @elseif($inq->is_read)
                            <span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(75,85,99,0.12);color:var(--text-dim)">Dibaca</span>
                        @else
                            <span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(157,92,245,0.15);color:#9d5cf5">Belum dibaca</span>
                        @endif
                    </td>
                    <td>{{ $inq->name }}</td>
                    <td style="font-size:0.85rem;color:var(--text-muted)">
                        <div>{{ $inq->email }}</div>
                        <div>{{ $inq->phone }}</div>
                    </td>
                    <td>
                        <span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;text-transform:capitalize;background:{{ $cc['bg'] }};color:{{ $cc['text'] }}">{{ $inq->category }}</span>
                    </td>
                    <td style="max-width:250px;white-space:normal;font-size:0.85rem;color:var(--text-muted)">
                        {{ strlen($inq->message) > 100 ? substr($inq->message, 0, 100) . '...' : $inq->message }}
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap">
                        {{ $inq->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-ghost btn-xs"
                            data-inquiry='{{ json_encode($inq->only(['id','name','email','phone','category','message','admin_reply','is_read','responded_at','created_at'])) }}'
                            onclick="openDetailModal(this)">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada pesan masuk</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pagination-wrap">{{ $inquiries->links('vendor.pagination.admin') }}</div>

<!-- ===== MODAL DETAIL PESAN ===== -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="inquiryModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeInquiryModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:640px;margin:0 1rem;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Detail Pesan</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeInquiryModal()">&times;</button>
        </div>
        <div class="p-5">
            <input type="hidden" id="inquiryId" value="">

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Nama</div>
                    <div class="font-semibold" id="d_name"></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Kategori</div>
                    <span class="badge badge-neutral" id="d_category" style="text-transform:capitalize"></span>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Email</div>
                    <div id="d_email"></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Telepon</div>
                    <div id="d_phone"></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Dikirim</div>
                    <div id="d_date"></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.25rem">Status</div>
                    <div id="d_status"></div>
                </div>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1.25rem;margin-bottom:1.25rem">
                <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:0.5rem">Pesan</div>
                <div id="d_message" style="font-size:0.9rem;line-height:1.6;white-space:pre-wrap"></div>
                <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);display:none" id="d_reply_block">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#10b981;margin-bottom:0.6rem">
                        <i class="fas fa-headset"></i> Balasan CS
                    </div>
                    <div id="d_reply" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:6px 16px 16px 16px;padding:10px 16px;font-size:0.9rem;line-height:1.6;white-space:pre-wrap"></div>
                </div>
            </div>

            <div id="inquiryActions" style="border-top:1px solid var(--border);padding-top:1.25rem;margin-bottom:1.25rem;display:flex;gap:0.5rem">
                <button type="button" class="btn btn-primary btn-sm" id="markReadBtn" onclick="markAsRead()">
                    <i class="fas fa-check mr-1"></i> Tandai Sudah Dibaca
                </button>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--error)" onclick="deleteInquiry()">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1.25rem">
                <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:0.25rem">Balas Pesan</h3>
                <p style="font-size:0.78rem;color:var(--text-dim);margin-bottom:0.75rem">
                    Balasan akan dikirim ke <strong id="replyEmail"></strong>
                </p>
                <textarea id="replyText" rows="4" class="input-field w-full" placeholder="Tulis balasan anda..." style="resize:vertical;min-height:80px;margin-bottom:0.75rem"></textarea>
                <p class="text-red-400 text-xs mt-1 hidden" id="err_reply"></p>
                <div class="flex justify-end">
                    <button type="button" class="btn btn-primary" id="replyBtn" onclick="sendReply()">
                        <i class="fas fa-reply mr-1"></i> Kirim Balasan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentInquiryId = null;

function openDetailModal(btn) {
    const d = JSON.parse(btn.dataset.inquiry);
    currentInquiryId = d.id;
    document.getElementById('inquiryId').value = d.id;
    document.getElementById('d_name').textContent = d.name;
    const catColors = {
        'topup': { bg: 'rgba(133,77,234,0.15)', text: '#9d5cf5' },
        'jual-beli-akun': { bg: 'rgba(245,158,11,0.15)', text: '#d97706' },
        'pembayaran': { bg: 'rgba(59,130,246,0.15)', text: '#3b82f6' },
        'keluhan': { bg: 'rgba(239,68,68,0.15)', text: '#dc2626' },
        'saran': { bg: 'rgba(16,185,129,0.15)', text: '#059669' },
    };
    const c = catColors[d.category] || { bg: 'rgba(100,116,139,0.15)', text: '#64748b' };
    document.getElementById('d_category').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;text-transform:capitalize;background:' + c.bg + ';color:' + c.text + '">' + d.category + '</span>';
    document.getElementById('d_email').textContent = d.email;
    document.getElementById('d_phone').textContent = d.phone || '-';
    document.getElementById('d_date').textContent = d.created_at;
    document.getElementById('d_message').textContent = d.message;
    const replyBlock = document.getElementById('d_reply_block');
    if (d.admin_reply) {
      document.getElementById('d_reply').textContent = d.admin_reply;
      replyBlock.style.display = 'block';
    } else {
      replyBlock.style.display = 'none';
    }
    document.getElementById('replyEmail').textContent = d.email;
    document.getElementById('replyText').value = '';

    if (d.responded_at) {
        document.getElementById('d_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(16,185,129,0.15);color:#059669">Direspon</span>';
        document.getElementById('markReadBtn').style.display = 'none';
    } else if (d.is_read) {
        document.getElementById('d_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(75,85,99,0.12);color:var(--text-dim)">Dibaca</span>';
        document.getElementById('markReadBtn').style.display = 'none';
    } else {
        document.getElementById('d_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(157,92,245,0.15);color:#9d5cf5">Belum dibaca</span>';
        document.getElementById('markReadBtn').style.display = '';
    }

    document.getElementById('err_reply').classList.add('hidden');
    document.getElementById('inquiryModal').style.display = 'flex';
}

function closeInquiryModal() {
    document.getElementById('inquiryModal').style.display = 'none';
    currentInquiryId = null;
}

async function markAsRead() {
    if (!currentInquiryId) return;
    try {
        const res = await fetch('{{ route('admin.contact-inquiries.mark-read', '__ID__') }}'.replace('__ID__', currentInquiryId), {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('markReadBtn').style.display = 'none';
            document.getElementById('d_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(75,85,99,0.12);color:var(--text-dim)">Dibaca</span>';
            showModal('success', data.message || 'Pesan ditandai sudah dibaca.');
        } else {
            showModal('error', 'Gagal menandai pesan.');
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan.');
    }
}

async function deleteInquiry() {
    if (!currentInquiryId) return;
    if (!confirm('Hapus pesan ini?')) return;
    try {
        const res = await fetch('{{ route('admin.contact-inquiries.destroy', '__ID__') }}'.replace('__ID__', currentInquiryId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: new URLSearchParams({ _method: 'DELETE' })
        });
        const data = await res.json();
        if (res.ok) {
            closeInquiryModal();
            showModal('success', data.message || 'Pesan berhasil dihapus.');
            setTimeout(function () { location.reload(); }, 800);
        } else {
            showModal('error', 'Gagal menghapus pesan.');
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan.');
    }
}

async function sendReply() {
    if (!currentInquiryId) return;
    const reply = document.getElementById('replyText').value.trim();
    if (!reply) {
        document.getElementById('err_reply').textContent = 'Balasan tidak boleh kosong.';
        document.getElementById('err_reply').classList.remove('hidden');
        return;
    }

    const btn = document.getElementById('replyBtn');
    btn.disabled = true;
    btn.textContent = 'Mengirim...';
    document.getElementById('err_reply').classList.add('hidden');

    try {
        const formData = new FormData();
        formData.append('reply', reply);

        const res = await fetch('{{ route('admin.contact-inquiries.reply', '__ID__') }}'.replace('__ID__', currentInquiryId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await res.json();

        if (res.ok) {
            document.getElementById('markReadBtn').style.display = 'none';
            document.getElementById('d_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(16,185,129,0.15);color:#059669">Direspon</span>';
            document.getElementById('replyText').value = '';
            showModal('success', data.message || 'Balasan berhasil dikirim.');
        } else {
            const errors = data.errors || {};
            if (errors.reply) {
                document.getElementById('err_reply').textContent = errors.reply[0];
                document.getElementById('err_reply').classList.remove('hidden');
            } else {
                showModal('error', 'Gagal mengirim balasan.');
            }
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Kirim Balasan';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('inquiryModal').style.display === 'flex') {
            closeInquiryModal();
        }
    });
});
</script>
@endpush
