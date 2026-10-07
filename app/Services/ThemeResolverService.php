<?php

namespace App\Services;

use App\Models\EventTheme;

class ThemeResolverService
{
    /**
     * Resolusi tema aktif untuk tampilan publik marketplace.
     *
     * Prioritas:
     * 1. Tema preview via query param ?preview_theme=slug
     * 2. Tema dengan is_active = true ATAU dalam jadwal (start <= today <= end)
     * 3. Jika lebih dari satu kandidat, ambil priority tertinggi
     * 4. Fallback ke tema is_default = true
     */
    public function resolveActiveTheme(?string $previewSlug = null): ?EventTheme
    {
        if ($previewSlug) {
            $preview = EventTheme::where('slug', $previewSlug)->first();
            if ($preview) {
                return $preview;
            }
        }

        $today = now()->toDateString();

        $candidates = EventTheme::where(function ($q) use ($today) {
            $q->where('is_active', true)
                ->orWhere(function ($q2) use ($today) {
                    $q2->whereNotNull('start_date')
                        ->where('start_date', '<=', $today)
                        ->where(function ($q3) use ($today) {
                            $q3->whereNull('end_date')->orWhere('end_date', '>=', $today);
                        });
                });
        })
            ->orderByDesc('priority')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        if ($candidates->isNotEmpty()) {
            return $candidates->first();
        }

        return EventTheme::where('is_default', true)->first();
    }

    /**
     * Generate string CSS custom properties dari tema aktif.
     * Variabel `--theme-*` ini dipakai untuk me-refresh seluruh token
     * tampilan publik (purple/background/surface/gold dll).
     */
    public static function cssVars(EventTheme $theme): string
    {
        $colors = is_array($theme->colors) ? $theme->colors : [];
        $colors = array_merge(self::defaultColors(), array_filter($colors, fn ($v) => $v !== null && $v !== ''));

        $primary = $colors['primary'] ?? '#2563eb';
        $accent = $colors['accent'] ?? '#00d4ff';
        $bg = $colors['bg'] ?? '#01203c';
        $bgSoft = $colors['bg_soft'] ?? '#052a48';
        $cardBg = $colors['card_bg'] ?? '#0A1E50';
        $text = $colors['text'] ?? '#f0f4ff';
        $textOnPrimary = $colors['text_on_primary'] ?? '#ffffff';
        $secondary = $colors['secondary']
            ?? ($theme->slug === 'default' ? '#7c3aed' : "color-mix(in srgb, {$primary} 78%, #ffffff)");

        $lines = [];
        $lines[] = "--theme-primary: {$primary};";
        $lines[] = "--theme-secondary: {$secondary};";
        $lines[] = "--theme-accent: {$accent};";
        $lines[] = "--theme-bg: {$bg};";
        $lines[] = "--theme-bg-soft: {$bgSoft};";
        $lines[] = "--theme-card-bg: {$cardBg};";
        $lines[] = "--theme-text: {$text};";
        $lines[] = "--theme-text-on-primary: {$textOnPrimary};";
        $lines[] = "--theme-primary-light: color-mix(in srgb, {$primary} 78%, #ffffff);";
        $lines[] = "--theme-primary-dark: color-mix(in srgb, {$primary} 70%, #000000);";
        $lines[] = "--theme-primary-glow: color-mix(in srgb, {$primary} 45%, transparent);";
        $lines[] = "--theme-accent-dark: color-mix(in srgb, {$accent} 75%, #000000);";
        $lines[] = "--theme-shadow-primary: 0 8px 30px -8px color-mix(in srgb, {$primary} 55%, transparent);";

        return implode("\n", $lines);
    }

    public static function defaultColors(): array
    {
        return [
            'primary' => '#2563eb',
            'accent' => '#00d4ff',
            'bg' => '#01203c',
            'bg_soft' => '#052a48',
            'card_bg' => '#0A1E50',
            'text' => '#f0f4ff',
            'text_on_primary' => '#ffffff',
        ];
    }
}
