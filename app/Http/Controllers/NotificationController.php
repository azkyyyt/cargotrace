<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\ExternalApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected ExternalApiService $apiService;

    public function __construct(ExternalApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(Request $request)
    {
        $forceRefresh = $request->has('refresh');
        $notifications = Auth::user()->inAppNotifications()->latest()->paginate(15);
        $exchangeRates = $this->apiService->getRealtimeExchangeRates($forceRefresh);
        $weatherBulletins = $this->apiService->getGlobalMaritimeWeatherBulletins();
        $tradeNews = $this->apiService->getGlobalTradeNews();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'exchange_rates' => $exchangeRates,
                'weather_bulletins' => $weatherBulletins,
                'trade_news' => $tradeNews,
            ]);
        }

        return view('notifications.index', compact(
            'notifications',
            'exchangeRates',
            'weatherBulletins',
            'tradeNews'
        ));
    }

    public function markAsRead(int $id)
    {
        $notification = Auth::user()->inAppNotifications()->findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        Auth::user()->inAppNotifications()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
