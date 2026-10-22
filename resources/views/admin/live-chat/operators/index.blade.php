@extends('admin.layouts.app')
@section('title', 'Live Chat Admins')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Admins</h1>
        <p class="page-subtitle">Kelola admin live chat</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('add-operator-modal').style.display='flex'">
        + Tambah Admin
    </button>
</div>

<div class="card-glass">
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Jadwal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operators as $op)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <x-user-avatar :user="$op->user" :name="$op->user->name ?? 'N/A'" :size="36" radius="10px" />
                            <div>
                                <div style="font-weight:600;font-size:13px;color:var(--text)">{{ $op->user->name ?? 'N/A' }}</div>
                                <div style="font-size:11px;color:var(--text-mute)">{{ $op->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-info">{{ $op->channel->name ?? '' }}</span></td>
                    <td>
                        @if($op->is_active)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-neutral">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @forelse($op->schedules as $sched)
                        <div style="font-size:12px;color:var(--text-dim);margin-bottom:2px">
                            {{ ucfirst($sched->day_of_week) }}: {{ $sched->start_time }} - {{ $sched->end_time }}
                        </div>
                        @empty
                        <span style="color:var(--text-mute);font-size:12px">Belum ada jadwal</span>
                        @endforelse
                    </td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <button class="btn btn-sm" onclick="toggleOperator({{ $op->id }})">
                                {{ $op->is_active ? 'Disable' : 'Enable' }}
                            </button>
                            <button class="btn btn-sm" onclick="editSchedule({{ $op->id }}, {{ $op->schedules->toJson() }})">Jadwal</button>
                            @if($op->user_id === null)
                            <button class="btn btn-sm btn-danger" onclick="deleteOperator({{ $op->id }})">Hapus</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">ðŸ‘¤</div>
                            <div>Belum ada admin</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center" id="add-operator-modal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('add-operator-modal').style.display='none'"></div>
    <div class="relative" style="background:var(--bg-card);border-radius:20px;max-width:480px;width:90%;max-height:90vh;overflow-y:auto;padding:24px">
        <h3 style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--text);margin-bottom:16px">Tambah Admin</h3>
        <form id="add-operator-form" onsubmit="submitAddOperator(event)">
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:12px;color:var(--text-dim);margin-bottom:4px">User</label>
                <select name="user_id" class="input-field" required style="width:100%">
                    <option value="">Pilih User</option>
                    @foreach($availableUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:12px;color:var(--text-dim);margin-bottom:4px">Channel</label>
                <select name="channel_id" class="input-field" required style="width:100%">
                    <option value="">Pilih Channel</option>
                    @foreach($channels as $channel)
                    <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('add-operator-modal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center" id="schedule-modal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('schedule-modal').style.display='none'"></div>
    <div class="relative" style="background:var(--bg-card);border-radius:20px;max-width:560px;width:90%;max-height:90vh;overflow-y:auto;padding:24px">
        <h3 style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--text);margin-bottom:16px">Atur Jadwal</h3>
        <form id="schedule-form" onsubmit="submitSchedule(event)">
            <div id="schedule-rows"></div>
            <button type="button" class="btn btn-sm" onclick="addScheduleRow()" style="margin-bottom:16px">+ Tambah Hari</button>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn" onclick="document.getElementById('schedule-modal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
let editingOperatorId = null;

async function submitAddOperator(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    try {
        const res = await fetch('/admin/live-chat/operators', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        });
        const result = await res.json();
        if (res.ok) {
            location.reload();
        } else {
            alert(result.error || 'Gagal menambahkan admin');
        }
    } catch (e) { alert('Terjadi kesalahan'); }
}

async function toggleOperator(id) {
    try {
        const res = await fetch(`/admin/live-chat/operators/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.ok) location.reload();
    } catch (e) {}
}

async function deleteOperator(id) {
    if (!confirm('Hapus admin ini?')) return;
    try {
        const res = await fetch(`/admin/live-chat/operators/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.ok) {
            location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Gagal menghapus admin');
        }
    } catch (e) {}
}

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

function editSchedule(operatorId, schedules) {
    editingOperatorId = operatorId;
    const container = document.getElementById('schedule-rows');
    container.innerHTML = '';

    if (schedules && schedules.length > 0) {
        schedules.forEach(s => addScheduleRow(s.day_of_week, s.start_time, s.end_time, s.is_active));
    } else {
        addScheduleRow('monday', '08:00', '16:00', true);
    }

    document.getElementById('schedule-modal').style.display = 'flex';
}

function addScheduleRow(day = 'monday', start = '08:00', end = '16:00', active = true) {
    const container = document.getElementById('schedule-rows');
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center';
    div.innerHTML = `
        <select name="day" class="input-field" style="flex:1">
            ${days.map((d, i) => `<option value="${d}" ${d === day ? 'selected' : ''}>${dayLabels[i]}</option>`).join('')}
        </select>
        <input type="time" name="start_time" value="${start}" class="input-field" style="flex:1">
        <span style="color:var(--text-mute)">-</span>
        <input type="time" name="end_time" value="${end}" class="input-field" style="flex:1">
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--error);cursor:pointer">âœ•</button>
    `;
    container.appendChild(div);
}

async function submitSchedule(e) {
    e.preventDefault();
    const rows = document.querySelectorAll('#schedule-rows > div');
    const schedules = [];

    rows.forEach(row => {
        const inputs = row.querySelectorAll('input, select');
        schedules.push({
            day_of_week: inputs[0].value,
            start_time: inputs[1].value,
            end_time: inputs[2].value,
            is_active: true
        });
    });

    if (schedules.length === 0) { alert('Tambahkan minimal 1 jadwal'); return; }

    try {
        const res = await fetch(`/admin/live-chat/operators/${editingOperatorId}/schedule`, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ schedules })
        });
        if (res.ok) location.reload();
        else {
            const data = await res.json();
            alert(data.message || 'Gagal menyimpan jadwal');
        }
    } catch (e) { alert('Terjadi kesalahan'); }
}
</script>
@endsection
