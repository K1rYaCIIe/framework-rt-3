<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AstroController extends Controller
{
    public function events(Request $r)
    {
        $lat  = (float) $r->query('lat', 55.7558);
        $lon  = (float) $r->query('lon', 37.6176);
        $days = max(1, min(30, (int) $r->query('days', 7)));

        $fakeEvents = [
            [
                'name' => 'Солнце',
                'type' => 'Восход',
                'when' => now()->addHours(6)->toISOString(),
                'extra' => 'Азимут: 90°'
            ],
            [
                'name' => 'Луна',
                'type' => 'Полнолуние',
                'when' => now()->addDays(3)->toISOString(),
                'extra' => 'Фаза: 100%'
            ],
            [
                'name' => 'Марс',
                'type' => 'Противостояние',
                'when' => now()->addDays(10)->toISOString(),
                'extra' => 'Видимость: отличная'
            ],
            [
                'name' => 'МКС',
                'type' => 'Пролет',
                'when' => now()->addMinutes(90)->toISOString(),
                'extra' => 'Яркость: -1.5m'
            ],
            [
                'name' => 'Венера',
                'type' => 'Вечерняя звезда',
                'when' => now()->addHours(2)->toISOString(),
                'extra' => 'Высота: 25°'
            ]
        ];

        return response()->json([
            'ok' => true,
            'data' => [
                'events' => $fakeEvents,
                'location' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'days' => $days
                ],
                'timestamp' => now()->toISOString(),
                'note' => 'Это тестовые данные. Реальные данные Astronomy API временно недоступны.'
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
