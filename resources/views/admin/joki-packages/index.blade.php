@extends('admin.layouts.app')
@section('title', 'Paket Joki MLBB')
@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
  <div><h2 class="text-lg font-semibold">Paket Joki Mobile Legends</h2><p style="color:var(--text-dim);font-size:.78rem;margin-top:.25rem">Paket di halaman joki diambil langsung dari data ini.</p></div>
  <button class="btn btn-primary" type="button" onclick="openJokiPackage()"><i class="fas fa-plus"></i> Tambah Paket</button>
</div>

<div class="table-wrap"><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Urutan</th><th>Paket</th><th>Estimasi</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($packages as $package)
<tr>
  <td>{{ $package->joki_sort_order }}</td>
  <td><div class="flex items-center gap-3">@if($package->photo_url)<img src="{{ $package->photo_url }}" alt="" style="width:42px;height:42px;object-fit:contain">@endif<div><strong>{{ $package->product_name }}</strong><small style="display:block;color:var(--text-dim)">{{ $package->buyer_sku_code }}</small></div></div></td>
  <td>{{ $package->estimated_duration ?: '-' }}</td><td>Rp {{ number_format($package->selling_price,0,',','.') }}</td><td>{{ $package->stock }}</td>
  <td><span class="badge {{ $package->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
  <td><div class="flex gap-2"><button class="btn btn-ghost btn-xs" type="button" data-package='{{ json_encode($package->only(['id','product_name','selling_price','estimated_duration','joki_sort_order','stock','is_active'])) }}' onclick="openJokiPackage(JSON.parse(this.dataset.package))"><i class="fas fa-edit"></i></button><form method="POST" action="{{ route('admin.joki-packages.destroy',$package) }}" onsubmit="return confirm('Hapus paket ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button></form></div></td>
</tr>
@empty<tr><td colspan="7"><div class="empty-state"><p>Belum ada paket joki.</p></div></td></tr>@endforelse
</tbody></table></div></div>

<div class="fixed inset-0 z-50 flex items-center justify-center" id="jokiPackageModal" style="display:none">
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeJokiPackage()"></div>
  <div class="relative" style="width:min(100% - 2rem,560px);max-height:92vh;overflow:auto;padding:1.25rem;border:1px solid var(--glass-border);border-radius:18px;background:var(--bg-card)">
    <div class="flex justify-between items-center mb-4"><h3 class="font-bold" id="jokiPackageTitle">Tambah Paket</h3><button type="button" onclick="closeJokiPackage()">&times;</button></div>
    <form id="jokiPackageForm" method="POST" enctype="multipart/form-data" action="{{ route('admin.joki-packages.store') }}">@csrf<input type="hidden" name="_method" id="jokiPackageMethod" value="POST">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2"><label class="block text-sm mb-1">Nama paket</label><input class="input-field" id="jp_name" name="product_name" required></div>
        <div><label class="block text-sm mb-1">Harga jual</label><input class="input-field" id="jp_price" name="selling_price" type="number" min="0" required></div>
        <div><label class="block text-sm mb-1">Estimasi</label><input class="input-field" id="jp_duration" name="estimated_duration" placeholder="Contoh: 12 jam"></div>
        <div><label class="block text-sm mb-1">Urutan</label><input class="input-field" id="jp_sort" name="joki_sort_order" type="number" min="0" value="0"></div>
        <div><label class="block text-sm mb-1">Stok</label><input class="input-field" id="jp_stock" name="stock" type="number" min="0" value="9999"></div>
        <div class="md:col-span-2"><label class="block text-sm mb-1">Icon rank</label><input class="input-field" name="photo" type="file" accept="image/png,image/jpeg,image/webp"></div>
      </div>
      <label class="flex items-center gap-2 mt-4"><input id="jp_active" name="is_active" type="checkbox" value="1" checked><span>Aktif</span></label>
      <div class="flex justify-end gap-2 mt-5"><button type="button" class="btn btn-ghost" onclick="closeJokiPackage()">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
function openJokiPackage(data){
 const form=document.getElementById('jokiPackageForm');form.reset();
 document.getElementById('jp_stock').value=9999;document.getElementById('jp_active').checked=true;
 if(data){form.action=@json(url('/admin/joki-packages'))+'/'+data.id;document.getElementById('jokiPackageMethod').value='PUT';document.getElementById('jokiPackageTitle').textContent='Edit Paket';document.getElementById('jp_name').value=data.product_name;document.getElementById('jp_price').value=parseInt(data.selling_price);document.getElementById('jp_duration').value=data.estimated_duration||'';document.getElementById('jp_sort').value=data.joki_sort_order||0;document.getElementById('jp_stock').value=data.stock;document.getElementById('jp_active').checked=!!data.is_active;}
 else{form.action=@json(route('admin.joki-packages.store'));document.getElementById('jokiPackageMethod').value='POST';document.getElementById('jokiPackageTitle').textContent='Tambah Paket';}
 document.getElementById('jokiPackageModal').style.display='flex';
}
function closeJokiPackage(){document.getElementById('jokiPackageModal').style.display='none';}
</script>
@endpush
