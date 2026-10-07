<x-guest-layout>
@section('title', 'Verifikasi OTP — ' . config('app.name'))

<div class="auth-header">
    <h1>Verifikasi Email</h1>
    <p>Masukkan kode OTP yang telah dikirim ke <strong style="color:#f5f3fb;">{{ session('register_email') }}</strong></p>
</div>

<form method="POST" action="{{ route('verify-otp') }}" class="auth-form" id="otpForm">
    @csrf

    <div class="form-group" style="text-align:center;">
        <label for="otp" style="text-align:center;text-transform:none;letter-spacing:0;font-size:.85rem;">Kode OTP</label>
        <div style="display:flex;gap:.5rem;justify-content:center;margin-top:.3rem;">
            @for($i = 0; $i < 6; $i++)
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off"
                   class="otp-input" data-index="{{ $i }}"
                   style="width:44px;height:52px;text-align:center;font-size:1.3rem;font-weight:800;font-family:'Sora',sans-serif;
                          background:rgba(255,255,255,.04);border:1.5px solid rgba(255,255,255,.07);border-radius:12px;
                          color:#f5f3fb;outline:none;transition:border-color .2s;"
                   onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,.12)'"
                   onblur="if(!this.value)this.style.borderColor='rgba(255,255,255,.07)';this.style.boxShadow='none'"
                   oninput="otpInput(this)">
            @endfor
        </div>
        <input type="hidden" name="otp" id="otpHidden">
        @error('otp')
            <div class="error-text" style="justify-content:center;margin-top:.5rem;"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
        @enderror
        @if (session('error'))
            <div class="error-text" style="justify-content:center;margin-top:.5rem;"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif
    </div>

    <button type="submit" class="btn-primary" id="otpBtn">
        <span class="spinner"></span>
        <span class="btn-text"><i class="fas fa-check-circle"></i> Verifikasi</span>
    </button>
</form>

<div class="auth-footer-text" style="display:flex;flex-direction:column;align-items:center;gap:.4rem;">
    <span>Tidak menerima kode? <button type="button" id="resendBtn" class="btn-resend" onclick="resendOtp()" style="background:none;border:none;color:#00d4ff;font-weight:700;cursor:pointer;font-family:inherit;font-size:.85rem;text-decoration:underline;">Kirim Ulang</button></span>
    <span id="resendTimer" style="font-size:.75rem;color:#7c6ea3;display:none;">Kirim ulang dalam <span id="countdown">60</span> detik</span>
</div>

<style>
.otp-input { color:#ffffff !important; caret-color:#ffffff; background:rgba(255,255,255,.06) !important; padding:0 !important; box-sizing:content-box !important; }
.otp-input:focus { border-color:#2563eb !important; box-shadow:0 0 0 3px rgba(37,99,235,.12) !important; }
.btn-resend:disabled { opacity:.5; cursor:not-allowed; text-decoration:none !important; }
</style>

@push('scripts')
<script>
function otpInput(el) {
    const idx = parseInt(el.dataset.index);
    el.value = el.value.replace(/[^0-9]/g, '');
    if (el.value && idx < 5) {
        document.querySelector(`.otp-input[data-index="${idx + 1}"]`).focus();
    }
    combineOtp();
    if (idx === 5 && el.value) {
        document.getElementById('otpBtn').focus();
    }
}
function combineOtp() {
    let val = '';
    document.querySelectorAll('.otp-input').forEach(inp => val += inp.value);
    document.getElementById('otpHidden').value = val;
}

document.querySelectorAll('.otp-input').forEach(inp => {
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value) {
            const idx = parseInt(this.dataset.index);
            if (idx > 0) {
                const prev = document.querySelector(`.otp-input[data-index="${idx - 1}"]`);
                prev.value = '';
                prev.focus();
                combineOtp();
            }
        }
    });
    inp.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        document.querySelectorAll('.otp-input').forEach((inp, i) => {
            inp.value = paste[i] || '';
        });
        combineOtp();
        const lastFilled = Math.min(paste.length, 6) - 1;
        const target = document.querySelector(`.otp-input[data-index="${lastFilled}"]`);
        if (target) target.focus();
    });
});

document.getElementById('otpForm')?.addEventListener('submit', function(e) {
    combineOtp();
    if (document.getElementById('otpHidden').value.length !== 6) {
        e.preventDefault();
        return;
    }
    const btn = document.getElementById('otpBtn');
    btn.disabled = true;
    btn.classList.add('loading');
});

function resendOtp() {
    const btn = document.getElementById('resendBtn');
    btn.disabled = true;
    btn.style.opacity = '.5';
    fetch('{{ route("resend-otp") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Content-Type': 'application/json',
        },
    }).then(r => r.json()).then(data => {
        if (data.success) {
            startTimer(60);
        }
    }).catch(() => {}).finally(() => {
        btn.disabled = false;
        btn.style.opacity = '1';
    });
}

function startTimer(sec) {
    const timer = document.getElementById('resendTimer');
    const count = document.getElementById('countdown');
    const btn = document.getElementById('resendBtn');
    timer.style.display = 'inline';
    btn.disabled = true;
    btn.style.cursor = 'default';

    function tick() {
        count.textContent = sec;
        if (sec <= 0) {
            timer.style.display = 'none';
            btn.disabled = false;
            btn.style.cursor = 'pointer';
            return;
        }
        sec--;
        setTimeout(tick, 1000);
    }
    tick();
}
</script>
@endpush
</x-guest-layout>
