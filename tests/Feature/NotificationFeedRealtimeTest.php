<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeedRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_notification_center_with_realtime_feeds(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewHas('exchangeRates');
        $response->assertViewHas('weatherBulletins');
        $response->assertViewHas('tradeNews');
        
        $response->assertSee('Global Intelligence', false);
        $response->assertSee('Live Forex &amp; Grain Valuations', false);
        $response->assertSee('Worldwide Port Weather Bulletins', false);
    }

    public function test_notification_center_json_feed_returns_realtime_data(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'exchange_rates' => [
                'base',
                'rates' => ['IDR', 'SGD', 'BRL', 'CNY', 'AUD'],
                'currencies',
                'commodity_benchmarks',
            ],
            'weather_bulletins',
            'trade_news',
        ]);
    }
}
