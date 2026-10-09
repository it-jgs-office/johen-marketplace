@extends('admin.layouts.app')

@section('title', 'Edit Admin — ' . $channel->name)

@section('content')
<div style="max-width:800px;margin:0 auto;padding:24px">

    {{-- Header --}}
    <div style="margin-bottom:24px">
        <h1 style="font-size:1.3rem;font-weight:700;color:var(--text);margin:0 0 4px">Edit Admin — {{ $channel->name }}</h1>
    </div>

    {{-- Back + Channel Info --}}
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:32px;padding:16px;background:var(--bg-card);border-radius:12px;border:1px solid var(--border)">
        <a href="{{ route('admin.live-chat.admins') }}" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--bg-input);color:var(--text-muted);text-decoration:none;font-size:1rem;flex-shrink:0;transition:all 0.2s" onmouseover="this.style.color='var(--text)';this.style.background='var(--sidebar-hover)'" onmouseout="this.style.color='var(--text-muted)';this.style.background='var(--bg-input)'">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div style="font-size:1.05rem;font-weight:700;color:var(--text)">{{ $channel->name }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted)">{{ $channel->name }} · {{ $channel->admins->first()->user->email ?? 'Belum ada admin' }}</div>
        </div>
    </div>

    @if($errors->any())
    <div style="margin-bottom:20px;padding:14px 18px;border-radius:10px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;font-size:0.85rem">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form id="editAdminForm" action="{{ route('admin.live-chat.admins.update', $channel) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Photo Section --}}
        <div style="margin-bottom:28px">
            <div style="font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:10px">Foto Admin</div>
            <div style="display:flex;align-items:flex-end;gap:16px">
                <div id="photoPreview" style="width:90px;height:90px;border-radius:50%;background:var(--bg-input);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    @if($channel->admins->first() && $channel->admins->first()->photo_path)
                        <img src="{{ asset('storage/' . $channel->admins->first()->photo_path) }}" style="width:100%;height:100%;object-fit:cover" alt="Admin Photo">
                    @else
                        <i class="fas fa-user" style="font-size:2rem;color:var(--text-dim)"></i>
                    @endif
                </div>
                <div>
                    <label for="photoInput" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:var(--bg-input);border:1px solid var(--border);color:var(--text);font-size:0.82rem;font-weight:500;cursor:pointer;transition:all 0.2s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                        <i class="fas fa-camera" style="font-size:0.8rem"></i> Ganti Foto
                    </label>
                    <input type="file" id="photoInput" name="admin_photo" accept="image/jpeg,image/png,image/webp" style="display:none">
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-top:6px">Format: JPG, PNG, WEBP. Maks 2MB.</div>
                    <div id="photoError" style="font-size:0.75rem;color:var(--error);margin-top:4px;display:none"></div>
                </div>
            </div>
        </div>

        {{-- Status Section --}}
        <div style="margin-bottom:28px">
            <div style="font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:10px">Status</div>
            <div style="display:flex;align-items:center;gap:12px">
                <label style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer">
                    <input type="checkbox" name="admin_is_active" id="statusToggle" value="1" {{ ($channel->admins->first() && $channel->admins->first()->is_active) ? 'checked' : '' }} style="opacity:0;width:0;height:0">
                    <span style="position:absolute;inset:0;background:var(--bg-input);border-radius:12px;transition:0.3s;border:1px solid var(--border)"></span>
                    <span id="statusDot" style="position:absolute;left:3px;top:3px;width:18px;height:18px;border-radius:50%;background:var(--text-dim);transition:0.3s"></span>
                </label>
                <span id="statusLabel" style="font-size:0.85rem;color:var(--text-muted)">{{ ($channel->admins->first() && $channel->admins->first()->is_active) ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
        </div>

        {{-- Operator Schedule Section --}}
        <div style="margin-bottom:28px;padding:20px;background:var(--bg-card);border-radius:12px;border:1px solid var(--border)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <div style="font-size:0.85rem;font-weight:600;color:var(--text)">
                    <i class="fas fa-clock" style="margin-right:6px;color:var(--accent)"></i> Jadwal Admin
                </div>
                <button type="button" id="addOperatorBtn" onclick="addOperator()" style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;border-radius:8px;border:1px solid var(--accent);background:transparent;color:var(--accent);font-size:0.8rem;font-weight:500;cursor:pointer;transition:all 0.2s" onmouseover="this.style.background='var(--accent)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--accent)'">
                    <i class="fas fa-plus" style="font-size:0.7rem"></i> Tambah
                </button>
            </div>

            <div id="operatorsContainer">
                @php $namedOperators = $channel->operators->whereNull('user_id')->values(); @endphp
                @forelse($namedOperators as $index => $operator)
                    <div class="operator-row" style="margin-bottom:16px;padding:14px;background:var(--bg-input);border-radius:10px;border:1px solid var(--border)" data-index="{{ $index }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                            <span style="font-size:0.75rem;color:var(--text-dim);font-weight:500">Admin {{ $index + 1 }}</span>
                            <button type="button" onclick="removeOperator(this)" style="background:none;border:none;color:var(--error);cursor:pointer;font-size:0.85rem;padding:4px" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                            <div>
                                <label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Nama Admin</label>
                                <input type="text" name="operators[{{ $index }}][name]" value="{{ $operator->name ?? '' }}" placeholder="Nama admin" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Jam Mulai</label>
                                <input type="text" name="operators[{{ $index }}][start_time]" value="{{ substr($operator->schedules->first()?->start_time ?? '', 0, 5) }}" placeholder="07:00" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none;font-family:'Poppins',monospace">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Jam Selesai</label>
                                <input type="text" name="operators[{{ $index }}][end_time]" value="{{ substr($operator->schedules->first()?->end_time ?? '', 0, 5) }}" placeholder="18:00" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none;font-family:'Poppins',monospace">
                            </div>
                        </div>
                    </div>
                @empty
                    <div id="emptyOperators" style="text-align:center;padding:20px;color:var(--text-dim);font-size:0.82rem">
Belum ada jadwal admin.
                    </div>
                @endforelse
            </div>

            <div style="font-size:0.75rem;color:var(--text-dim);margin-top:8px">Atur jam operasional per admin. Format: 24 jam (00:00 - 23:59). Kosongkan jika tidak ada jadwal.</div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">Admin akun (sesuai akun login admin) tidak ikut di sini dan tidak terhapus saat menyimpan.</div>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex;gap:12px">
            <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#0ea5e9);color:#fff;font-size:0.88rem;font-weight:600;border:none;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 14px -4px rgba(9,135,245,0.4)" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px -4px rgba(9,135,245,0.5)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 14px -4px rgba(9,135,245,0.4)'">
                <i class="fas fa-check"></i> Simpan
            </button>
            <a href="{{ route('admin.live-chat.admins') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;border-radius:10px;background:var(--bg-input);color:var(--text-muted);font-size:0.88rem;font-weight:500;text-decoration:none;border:1px solid var(--border);transition:all 0.2s" onmouseover="this.style.color='var(--text)';this.style.borderColor='var(--text-dim)'" onmouseout="this.style.color='var(--text-muted)';this.style.borderColor='var(--border)'">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const statusToggle = document.getElementById('statusToggle');
    const statusDot = document.getElementById('statusDot');
    const statusLabel = document.getElementById('statusLabel');

    statusToggle.addEventListener('change', function() {
        if (this.checked) {
            statusDot.style.transform = 'translateX(20px)';
            statusDot.style.background = 'var(--success)';
            statusLabel.textContent = 'Aktif';
        } else {
            statusDot.style.transform = '';
            statusDot.style.background = 'var(--text-dim)';
            statusLabel.textContent = 'Nonaktif';
        }
    });

    if (statusToggle.checked) {
        statusDot.style.transform = 'translateX(20px)';
        statusDot.style.background = 'var(--success)';
    }

    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const photoError = document.getElementById('photoError');

    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            photoError.textContent = 'Format tidak valid. Gunakan JPG, PNG, atau WEBP.';
            photoError.style.display = 'block';
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            photoError.textContent = 'Ukuran file maksimal 2MB.';
            photoError.style.display = 'block';
            this.value = '';
            return;
        }

        photoError.style.display = 'none';
        const reader = new FileReader();
        reader.onload = function(ev) {
            photoPreview.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover" alt="Preview">';
        };
        reader.readAsDataURL(file);
    });

    let operatorIndex = {{ count($namedOperators) }};
    const maxOperators = 10;

    function addOperator() {
        const container = document.getElementById('operatorsContainer');
        const rows = container.querySelectorAll('.operator-row');
        if (rows.length >= maxOperators) return;

        const empty = document.getElementById('emptyOperators');
        if (empty) empty.remove();

        const html = '<div class="operator-row" style="margin-bottom:16px;padding:14px;background:var(--bg-input);border-radius:10px;border:1px solid var(--border)" data-index="' + operatorIndex + '">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">' +
                '<span style="font-size:0.75rem;color:var(--text-dim);font-weight:500">Admin ' + (rows.length + 1) + '</span>' +
                '<button type="button" onclick="removeOperator(this)" style="background:none;border:none;color:var(--error);cursor:pointer;font-size:0.85rem;padding:4px" title="Hapus"><i class="fas fa-trash"></i></button>' +
            '</div>' +
            '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">' +
                '<div><label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Nama Admin</label>' +
                    '<input type="text" name="operators[' + operatorIndex + '][name]" placeholder="Nama admin" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none"></div>' +
                '<div><label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Jam Mulai</label>' +
                    '<input type="text" name="operators[' + operatorIndex + '][start_time]" placeholder="07:00" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none;font-family:Poppins,monospace"></div>' +
                '<div><label style="display:block;font-size:0.75rem;color:var(--text-muted);margin-bottom:4px">Jam Selesai</label>' +
                    '<input type="text" name="operators[' + operatorIndex + '][end_time]" placeholder="18:00" pattern="[0-9]{2}:[0-9]{2}" maxlength="5" style="width:100%;padding:8px 10px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);font-size:0.82rem;outline:none;font-family:Poppins,monospace"></div>' +
            '</div></div>';

        container.insertAdjacentHTML('beforeend', html);
        operatorIndex++;
        updateAddButton();
    }

    function removeOperator(btn) {
        btn.closest('.operator-row').remove();
        reindexOperators();
        updateAddButton();
    }

    function reindexOperators() {
        const container = document.getElementById('operatorsContainer');
        const rows = container.querySelectorAll('.operator-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-index', i);
            row.querySelector('span').textContent = 'Admin ' + (i + 1);
            row.querySelectorAll('input').forEach(function(el) {
                const name = el.getAttribute('name');
                if (name) {
                    el.setAttribute('name', name.replace(/operators\[\d+\]/, 'operators[' + i + ']'));
                }
            });
        });
        if (rows.length === 0) {
            container.innerHTML = '<div id="emptyOperators" style="text-align:center;padding:20px;color:var(--text-dim);font-size:0.82rem">Belum ada jadwal admin.</div>';
        }
    }

    function updateAddButton() {
        const rows = document.querySelectorAll('.operator-row');
        const btn = document.getElementById('addOperatorBtn');
        btn.disabled = rows.length >= maxOperators;
        btn.style.opacity = rows.length >= maxOperators ? '0.5' : '1';
        btn.style.cursor = rows.length >= maxOperators ? 'not-allowed' : 'pointer';
    }

    updateAddButton();
</script>
@endpush
