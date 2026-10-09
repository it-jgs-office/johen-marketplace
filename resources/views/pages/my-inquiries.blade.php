@extends('layouts.topup')

@section('title', 'Riwayat Pesan - ' . config('app.name'))

@push('styles')
<style>
.inq-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:2rem var(--layout-gutter) 4rem;animation:inqFadeIn .4s ease}
@keyframes inqFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.inq-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap}
.inq-header-left{min-width:0}
.inq-title{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:800;letter-spacing:-.02em;margin:0}
.inq-subtitle{font-size:.85rem;color:var(--text-dim);margin-top:.15rem}
.inq-list{display:flex;flex-direction:column;gap:16px}
.inq-card{display:flex;gap:20px;background:var(--surface);border:1px solid var(--glass-border);border-radius:16px;padding:20px 24px;transition:all .25s ease;cursor:pointer;animation:inqFadeIn .35s ease both}
.inq-card:hover{border-color:var(--accent);box-shadow:0 6px 24px -10px rgba(157,92,245,0.15)}
.inq-card-read{opacity:.7}
.inq-card-read:hover{opacity:1}
.inq-card-responded{border-left:4px solid #059669!important;background:linear-gradient(135deg,rgba(16,185,129,0.04),transparent)!important}
.inq-modal{position:fixed;inset:0;z-index:50;display:none;align-items:center;justify-content:center}
.inq-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
.inq-modal-box{position:relative;background:var(--surface);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:560px;margin:0 1rem;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,.5)}
.inq-modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--glass-border);position:sticky;top:0;background:var(--surface);z-index:1;border-radius:20px 20px 0 0}
.inq-modal-title{font-family:'Sora',sans-serif;font-size:1.05rem;font-weight:700}
.inq-modal-close{background:none;border:none;color:var(--text-dim);font-size:1.5rem;cursor:pointer;line-height:1;padding:.25rem;transition:color .2s}
.inq-modal-close:hover{color:var(--text)}
.inq-modal-body{padding:1.5rem}
.inq-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem 1.5rem;margin-bottom:1.5rem}
.inq-modal-label{font-size:.73rem;color:var(--text-dim);margin-bottom:.15rem}
.inq-modal-value{font-size:.85rem;font-weight:600}
.inq-modal-divider{border:0;border-top:1px solid var(--glass-border);margin:0 0 1.25rem}
.inq-modal-message{font-size:.88rem;line-height:1.65;white-space:pre-wrap;color:var(--text)}
.inq-reply-block{margin-top:.75rem;padding-top:1rem;border-top:1px solid var(--glass-border)}
.inq-reply-label{display:flex;align-items:center;gap:.5rem;font-size:.73rem;color:var(--text-dim);margin-bottom:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.inq-reply-label i{color:#10b981}
.inq-reply-bubble{position:relative;background:linear-gradient(135deg,rgba(16,185,129,.10),rgba(16,185,129,.04));border:1px solid rgba(16,185,129,.25);border-radius:6px 16px 16px 16px;padding:8px 14px;font-size:.88rem;line-height:1.65;white-space:pre-wrap;color:var(--text)}
.inq-reply-time{font-size:.68rem;color:var(--text-dim);margin-top:.35rem}
.inq-icon-wrap{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.65rem;font-weight:700;color:#fff}
.inq-icon-wrap--topup{background:linear-gradient(135deg,#854DEA,#a575ff)}
.inq-icon-wrap--jual-beli-akun{background:linear-gradient(135deg,#f59e0b,#fbbf24)}
.inq-icon-wrap--pembayaran{background:linear-gradient(135deg,#3b82f6,#60a5fa)}
.inq-icon-wrap--keluhan{background:linear-gradient(135deg,#ef4444,#f87171)}
.inq-icon-wrap--saran{background:linear-gradient(135deg,#10b981,#34d399)}
.inq-icon-wrap--lainnya{background:linear-gradient(135deg,#64748b,#94a3b8)}
.inq-body{flex:1;min-width:0}
.inq-body-top{display:flex;align-items:center;gap:.5rem;margin-bottom:6px;flex-wrap:wrap}
.inq-category-badge{display:inline-block;padding:.15rem .6rem;border-radius:6px;font-size:.68rem;font-weight:600;text-transform:capitalize;background:rgba(157,92,245,0.12);color:#9d5cf5}
.inq-date{font-size:.73rem;color:var(--text-dim)}
.inq-message{font-size:.85rem;color:var(--text);line-height:1.55;margin:0;white-space:pre-wrap;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.inq-status-badge{display:inline-block;padding:.15rem .6rem;border-radius:6px;font-size:.68rem;font-weight:600;white-space:nowrap;margin-left:auto}
.inq-status-badge--unread{background:rgba(157,92,245,0.15);color:#9d5cf5}
.inq-status-badge--read{background:rgba(134,142,161,0.12);color:var(--text-dim)}
.inq-status-badge--responded{background:rgba(16,185,129,0.15);color:#059669}
.inq-empty{text-align:center;padding:5rem 1.5rem;background:var(--surface);border-radius:16px;border:1px solid var(--glass-border)}
.inq-empty-icon{display:flex;align-items:center;justify-content:center;color:var(--text-dim);margin-bottom:1.25rem;opacity:.4}
.inq-empty-title{font-size:1.15rem;font-weight:600;margin-bottom:.35rem}
.inq-empty-desc{color:var(--text-dim);font-size:.9rem;margin-bottom:1.5rem;max-width:360px;margin-left:auto;margin-right:auto}
.inq-empty-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.5rem;border-radius:10px;background:var(--accent);color:#fff;text-decoration:none;font-size:.85rem;font-weight:600;transition:all .2s}
.inq-empty-btn:hover{filter:brightness(1.1);transform:translateY(-2px);box-shadow:0 6px 20px -6px rgba(157,92,245,0.3)}
.inq-pagination{display:flex;align-items:center;justify-content:center;gap:.35rem;margin-top:1.5rem;flex-wrap:wrap}
.inq-page-btn{display:inline-flex;align-items:center;gap:.25rem;padding:.4rem .75rem;border-radius:8px;font-size:.78rem;font-weight:600;background:var(--surface-2);border:1px solid var(--glass-border);color:var(--text-dim);cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none}
.inq-page-btn:hover{background:var(--surface);border-color:var(--accent);color:var(--text)}
.inq-page-btn.active{background:var(--accent);color:#fff;border-color:var(--accent);box-shadow:0 0 16px -4px rgba(157,92,245,0.3)}
.inq-page-dots{padding:.3rem .2rem;color:var(--text-dim);font-size:.85rem;letter-spacing:.1em}
.inq-help-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:2.5rem;padding:1.25rem 1.5rem;background:var(--surface);border:1px solid var(--glass-border);border-radius:16px;flex-wrap:wrap}
.inq-help-left{display:flex;align-items:center;gap:.85rem}
.inq-help-icon{width:44px;height:44px;border-radius:12px;background:rgba(157,92,245,0.12);display:flex;align-items:center;justify-content:center;color:var(--accent);flex-shrink:0}
.inq-help-text{min-width:0}
.inq-help-title{font-size:.9rem;font-weight:700}
.inq-help-desc{font-size:.78rem;color:var(--text-dim)}
.inq-help-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.45rem 1rem;border-radius:8px;font-size:.78rem;font-weight:700;text-decoration:none;transition:all .2s;height:36px;border:1px solid var(--glass-border);color:var(--text-dim);background:transparent}
.inq-help-btn:hover{border-color:var(--accent);color:var(--accent)}
@media(max-width:640px){
.inq-page{padding:1.5rem var(--layout-gutter) 3rem}
.inq-card{flex-direction:column;gap:12px;padding:16px}
.inq-icon-wrap{width:36px;height:36px}
.inq-header{flex-direction:column;align-items:stretch}
.inq-help-footer{flex-direction:column;text-align:center}
.inq-help-left{flex-direction:column;text-align:center}
}
</style>
@endpush

@section('content')
<div class="inq-page">
  <div class="inq-header">
    <div class="inq-header-left">
      <h1 class="inq-title">RIWAYAT PESAN</h1>
      <p class="inq-subtitle">Lihat pesan yang telah kamu kirimkan ke tim CS.</p>
    </div>
  </div>

  @if($inquiries->count())
    <div class="inq-list">
      @foreach($inquiries as $inq)
        @php
          $icons = [
            'topup' => 'TP', 'jual-beli-akun' => 'JB', 'pembayaran' => 'BY',
            'keluhan' => 'KL', 'saran' => 'SR', 'lainnya' => 'LN',
          ];
          $iconText = $icons[$inq->category] ?? 'PS';
          $wrapClass = 'inq-icon-wrap--' . str_replace('_', '-', $inq->category);
          if (!str_contains($inq->category, '-') && !in_array($inq->category, ['topup','pembayaran','keluhan','saran','lainnya'])) {
            $wrapClass = 'inq-icon-wrap--lainnya';
          }
        @endphp
        <div class="inq-card {{ $inq->responded_at ? 'inq-card-responded' : ($inq->is_read ? 'inq-card-read' : '') }}"
             data-inquiry='{{ json_encode($inq->only(['id','name','email','phone','category','message','admin_reply','is_read','responded_at','created_at'])) }}'
             onclick="openInqModal(this)">
          <div class="inq-icon-wrap {{ $wrapClass }}">{{ $iconText }}</div>
          <div class="inq-body">
            <div class="inq-body-top">
              <span class="inq-category-badge">{{ str_replace('-', ' ', $inq->category) }}</span>
              <span class="inq-date">{{ $inq->created_at->format('d M Y H:i') }}</span>
              <span class="inq-status-badge
                @if($inq->responded_at) inq-status-badge--responded
                @elseif($inq->is_read) inq-status-badge--read
                @else inq-status-badge--unread
                @endif
              ">{{ $inq->responded_at ? 'Direspon' : ($inq->is_read ? 'Dibaca' : 'Belum dibaca') }}</span>
            </div>
            <p class="inq-message">{{ $inq->message }}</p>
          </div>
        </div>
      @endforeach
    </div>

    @if($inquiries->hasPages())
    <div class="inq-pagination">
      @if($inquiries->onFirstPage())
        <span class="inq-page-btn" style="opacity:.4;cursor:default">&lsaquo; Sebelumnya</span>
      @else
        <a href="{{ $inquiries->previousPageUrl() }}" class="inq-page-btn">&lsaquo; Sebelumnya</a>
      @endif

      @foreach($inquiries->getUrlRange(1, $inquiries->lastPage()) as $page => $url)
        @if($page == $inquiries->currentPage())
          <span class="inq-page-btn active">{{ $page }}</span>
        @elseif($page == 1 || $page == $inquiries->lastPage() || abs($page - $inquiries->currentPage()) <= 2)
          <a href="{{ $url }}" class="inq-page-btn">{{ $page }}</a>
        @elseif($page == 2 || $page == $inquiries->lastPage() - 1)
          <span class="inq-page-dots">…</span>
        @endif
      @endforeach

      @if($inquiries->hasMorePages())
        <a href="{{ $inquiries->nextPageUrl() }}" class="inq-page-btn">Selanjutnya &rsaquo;</a>
      @else
        <span class="inq-page-btn" style="opacity:.4;cursor:default">Selanjutnya &rsaquo;</span>
      @endif
    </div>
    @endif
  @else
    <div class="inq-empty">
      <div class="inq-empty-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <h3 class="inq-empty-title">Belum Ada Pesan</h3>
      <p class="inq-empty-desc">Kamu belum mengirimkan pesan ke tim CS. Kami siap membantu 24 jam.</p>
      <a href="{{ route('kontak') }}" class="inq-empty-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Hubungi CS
      </a>
    </div>
  @endif

  <!-- ===== MODAL DETAIL PESAN ===== -->
  <div class="inq-modal" id="inqModal">
    <div class="inq-modal-overlay" onclick="closeInqModal()"></div>
    <div class="inq-modal-box">
      <div class="inq-modal-header">
        <span class="inq-modal-title">Detail Pesan</span>
        <button type="button" class="inq-modal-close" onclick="closeInqModal()">&times;</button>
      </div>
      <div class="inq-modal-body">
        <div class="inq-modal-grid">
          <div>
            <div class="inq-modal-label">Nama</div>
            <div class="inq-modal-value" id="m_name"></div>
          </div>
          <div>
            <div class="inq-modal-label">Kategori</div>
            <div id="m_category"></div>
          </div>
          <div>
            <div class="inq-modal-label">Email</div>
            <div class="inq-modal-value" id="m_email"></div>
          </div>
          <div>
            <div class="inq-modal-label">Telepon</div>
            <div class="inq-modal-value" id="m_phone"></div>
          </div>
          <div>
            <div class="inq-modal-label">Dikirim</div>
            <div class="inq-modal-value" id="m_date"></div>
          </div>
          <div>
            <div class="inq-modal-label">Status</div>
            <div id="m_status"></div>
          </div>
        </div>
        <hr class="inq-modal-divider">
        <div style="margin-bottom:.5rem">
          <div class="inq-modal-label">Pesan</div>
          <div class="inq-modal-message" id="m_message"></div>
        </div>
        <div class="inq-reply-block" id="m_reply_block" style="display:none">
          <div class="inq-reply-label"><i class="fas fa-headset"></i> Balasan CS</div>
          <div class="inq-reply-bubble" id="m_reply"></div>
          <div class="inq-reply-time" id="m_reply_time"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="inq-help-footer">
    <div class="inq-help-left">
      <div class="inq-help-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div class="inq-help-text">
        <div class="inq-help-title">Butuh Bantuan?</div>
        <div class="inq-help-desc">Tim support kami siap membantu 24 jam.</div>
      </div>
    </div>
    <a href="{{ route('kontak') }}" class="inq-help-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Kirim Pesan Baru
    </a>
  </div>
</div>
@endsection

@push('scripts')
<script>
function openInqModal(el) {
  const d = JSON.parse(el.dataset.inquiry);
  document.getElementById('m_name').textContent = d.name;
  document.getElementById('m_email').textContent = d.email;
  document.getElementById('m_phone').textContent = d.phone || '-';
  document.getElementById('m_date').textContent = d.created_at;
  document.getElementById('m_message').textContent = d.message;

  const replyBlock = document.getElementById('m_reply_block');
  if (d.admin_reply) {
    document.getElementById('m_reply').textContent = d.admin_reply;
    document.getElementById('m_reply_time').textContent = d.responded_at ? 'Direspon ' + d.responded_at : '';
    replyBlock.style.display = 'block';
  } else {
    replyBlock.style.display = 'none';
  }

  const catColors = {
    topup: { bg: 'rgba(133,77,234,0.15)', text: '#9d5cf5' },
    'jual-beli-akun': { bg: 'rgba(245,158,11,0.15)', text: '#d97706' },
    pembayaran: { bg: 'rgba(59,130,246,0.15)', text: '#3b82f6' },
    keluhan: { bg: 'rgba(239,68,68,0.15)', text: '#dc2626' },
    saran: { bg: 'rgba(16,185,129,0.15)', text: '#059669' },
  };
  const c = catColors[d.category] || { bg: 'rgba(100,116,139,0.15)', text: '#64748b' };
  document.getElementById('m_category').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;text-transform:capitalize;background:' + c.bg + ';color:' + c.text + '">' + d.category + '</span>';

  if (d.responded_at) {
    document.getElementById('m_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(16,185,129,0.15);color:#059669">Direspon</span>';
  } else if (d.is_read) {
    document.getElementById('m_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(75,85,99,0.12);color:var(--text-dim)">Dibaca</span>';
  } else {
    document.getElementById('m_status').innerHTML = '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(157,92,245,0.15);color:#9d5cf5">Belum dibaca</span>';
  }

  document.getElementById('inqModal').style.display = 'flex';
}

function closeInqModal() {
  document.getElementById('inqModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('inqModal');
      if (modal.style.display === 'flex') closeInqModal();
    }
  });
});
</script>
@endpush
