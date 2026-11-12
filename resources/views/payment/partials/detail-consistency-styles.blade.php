.pd-wrap{max-width:var(--layout-max);}
.pd-grid.pd-grid-single{grid-template-columns:minmax(0,1fr);}
.pd-breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-dim);margin:-.2rem 0 1.2rem;}
.pd-breadcrumb a{color:var(--text-dim);text-decoration:none;transition:color .2s;}
.pd-breadcrumb a:hover{color:var(--text);}
.pd-breadcrumb span{color:var(--text-mute);}
.pd-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.6rem;flex-wrap:wrap;}
.pd-header-titles{flex:1;min-width:0;}
.pd-status-card{border-bottom:1px solid var(--border);}

.pd-method-card{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,390px);}
.pd-method-card>.pd-card-head{grid-column:1/-1;}
.pd-method-card>.pd-detail-grid{grid-column:1;grid-row:2;align-self:stretch;padding:1rem 1.1rem;}
.pd-method-card>.pd-method{grid-column:2;grid-row:2;align-self:stretch;display:flex;flex-direction:column;justify-content:center;border-left:1px solid var(--border);background:var(--invoice-accent-soft);}
.pd-method-card .pd-method-placeholder{display:flex;flex-direction:column;align-items:stretch;text-align:left;padding:.35rem 0 0;}
.pd-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1.5rem;padding:.4rem 1.1rem .8rem;}
.pd-detail-col{display:flex;flex-direction:column;}
.pd-detail-item{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.55rem 0;border-bottom:1px dashed var(--border);}
.pd-detail-item:last-child{border-bottom:none;}
.pd-detail-label{color:var(--text-dim);font-size:.82rem;white-space:nowrap;flex-shrink:0;}
.pd-detail-value{color:var(--text);font-size:.84rem;font-weight:500;text-align:right;display:inline-flex;align-items:center;gap:.45rem;min-width:0;justify-content:flex-end;word-break:break-word;}
.pd-detail-mono{font-family:var(--font-display);font-weight:600;}
.pd-detail-paylogo{width:22px;height:22px;object-fit:contain;border-radius:5px;flex-shrink:0;background:#fff;padding:2px;border:1px solid rgba(15,23,42,.12);}
.pd-detail-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .65rem;border-radius:6px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.pd-detail-badge-dot{width:6px;height:6px;border-radius:50%;display:inline-block;}
.pd-pay-cta-head{display:flex;align-items:center;gap:.8rem;margin-bottom:1rem;}
.pd-pay-cta-icon{display:grid;place-items:center;width:48px;height:48px;flex-shrink:0;border-radius:12px;background:#fff;border:1px solid var(--invoice-accent-border);color:var(--invoice-accent);box-shadow:0 8px 20px -14px var(--invoice-accent);}
.pd-pay-cta-icon img{display:block;width:30px;height:30px;object-fit:contain;}
.pd-pay-cta-copy{display:flex;flex-direction:column;gap:.16rem;min-width:0;}
.pd-pay-cta-copy span{font-size:.68rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--invoice-accent);}
.pd-pay-cta-copy strong{font-family:var(--font-display);font-size:1.05rem;line-height:1.25;color:var(--text);}
.pd-pay-cta-meta{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;margin-bottom:.8rem;}
.pd-pay-cta-meta>div{display:flex;flex-direction:column;gap:.2rem;min-width:0;padding:.65rem .7rem;border:1px solid var(--border);border-radius:9px;background:var(--bg-card);}
.pd-pay-cta-meta span{font-size:.66rem;color:var(--text-mute);}
.pd-pay-cta-meta strong{font-size:.78rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-pay-action{width:100%;min-height:52px;justify-content:space-between;padding-inline:1.05rem;background:var(--invoice-accent)!important;color:var(--invoice-heading-fg)!important;box-shadow:0 12px 26px -14px var(--invoice-accent);}
.pd-pay-action:hover{transform:translateY(-2px);box-shadow:0 16px 30px -14px var(--invoice-accent);}
.pd-pay-cta-note{margin:.65rem 0 0;text-align:center;font-size:.7rem;color:var(--text-dim);}

.pd-steps{display:flex;align-items:flex-start;padding:1.4rem 1.1rem 1rem;position:relative;}
.pd-step{flex:1;display:flex;flex-direction:column;align-items:center;gap:.6rem;text-align:center;position:relative;min-width:0;}
.pd-step::before{content:"";position:absolute;top:12px;left:-50%;width:100%;height:2px;background:var(--border);z-index:0;}
.pd-step:first-child::before{display:none;}
.pd-step-dot{width:26px;height:26px;border-radius:50%;flex-shrink:0;position:relative;z-index:1;background:var(--surface);border:2px solid var(--border);transition:all .3s;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:.78rem;}
.pd-step-num{color:var(--text-mute);line-height:1;}
.pd-step[data-state="active"] .pd-step-dot{border-color:var(--invoice-accent);background:var(--invoice-accent);box-shadow:0 0 0 5px var(--invoice-accent-glow);animation:pdSharedStepPulse 1.8s infinite;}
.pd-step[data-state="done"] .pd-step-dot{border-color:var(--invoice-accent);background:var(--invoice-accent);box-shadow:0 0 0 4px var(--invoice-accent-glow);}
.pd-step[data-state="done"] .pd-step-num,.pd-step[data-state="active"] .pd-step-num{color:#fff;}
.pd-step[data-state="done"]::before,.pd-step[data-state="active"]::before{background:var(--invoice-accent);}
.pd-step-label{font-size:.72rem;font-weight:600;color:var(--text-dim);line-height:1.25;max-width:120px;}
.pd-step[data-state="active"] .pd-step-label{color:var(--invoice-accent);font-weight:700;}
.pd-step[data-state="done"] .pd-step-label{color:var(--text);}
@keyframes pdSharedStepPulse{0%,100%{box-shadow:0 0 0 0 rgba(133,77,234,.35)}50%{box-shadow:0 0 0 7px rgba(133,77,234,0)}}

@media(max-width:768px){
  .pd-method-card{grid-template-columns:1fr;}
  .pd-method-card>.pd-card-head{grid-column:1;grid-row:1;}
  .pd-method-card>.pd-method{grid-column:1;grid-row:2;border-left:0;}
  .pd-method-card>.pd-detail-grid{grid-column:1;grid-row:3;padding:.4rem 1.1rem .8rem;border-top:1px solid var(--border);}
  .pd-detail-grid{grid-template-columns:1fr;gap:0;}
  .pd-detail-col .pd-detail-item{border-bottom:1px dashed var(--border);}
}
@media(max-width:480px){
  .pd-steps{gap:.2rem;}
  .pd-step-label{font-size:.62rem;max-width:80px;}
}
