<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JbaGameBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_supported_game_page_uses_its_stock_background(): void
    {
        $backgrounds = [
            'mlbb' => 'assets/backround/bg-ml.jpg',
            'pubg' => 'assets/backround/bg-pubg.png',
            'fcm' => 'assets/backround/bg-fcmobile.png',
            'ff' => 'assets/backround/bg-ff.png',
            'valorant' => 'assets/backround/bg-valo.png',
        ];

        foreach ($backgrounds as $slug => $path) {
            $this->assertFileExists(public_path($path));

            $response = $this->get(route('jual-beli-akun.game', $slug));

            $response
                ->assertOk()
                ->assertSee('id="jbaGameLayoutBg"', false)
                ->assertSee('src="'.asset($path).'"', false);

            preg_match('/<img class="jba-game-hero-bg"[^>]*src="([^"]*)"/', $response->getContent(), $heroMatch);
            preg_match('/<img id="jbaGameLayoutBg"[^>]*src="([^"]*)"/', $response->getContent(), $layoutMatch);

            $this->assertSame('', $heroMatch[1] ?? null);
            $this->assertSame(asset($path), $layoutMatch[1] ?? null);
        }

        $page = $this->get(route('jual-beli-akun.game', 'mlbb'));
        $page->assertSee('position: fixed;', false)
            ->assertSee('height: 100vh;', false)
            ->assertDontSee('.jba-game-layout-background::after', false);
    }
}
