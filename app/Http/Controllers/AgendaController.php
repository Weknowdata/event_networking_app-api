<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAgendaRequest;
use App\Http\Resources\AgendaDayResource;
use App\Models\AgendaDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function index(): JsonResponse
    {
        $days = AgendaDay::with(['slots.speakers'])
            ->orderBy('day_number')
            ->get();

        return response()->json([
            'agenda' => AgendaDayResource::collection($days),
        ]);
    }

    public function generate(GenerateAgendaRequest $request): JsonResponse
    {
        $daysCount = $request->daysCount();
        $startDate = $request->startDate();

        DB::transaction(function () use ($daysCount, $startDate) {
            // Regenerate the entire agenda; cascades remove existing slots.
            AgendaDay::query()->delete();

            for ($i = 0; $i < $daysCount; $i++) {
                $day = AgendaDay::create([
                    'day_number' => $i + 1,
                    'date' => $startDate->addDays($i)->toDateString(),
                ]);

                $slots = [];
                for ($hour = 9; $hour < 17; $hour++) {
                    $start = CarbonImmutable::createFromTime($hour, 0);
                    $slots[] = [
                        'start_time' => $start->format('H:i:s'),
                        'end_time' => $start->addHour()->format('H:i:s'),
                        'title' => 'TBD',
                        'description' => null,
                        'location' => null,
                    ];
                }

                $day->slots()->createMany($slots);
            }
        });

        $days = AgendaDay::with(['slots.speakers'])
            ->orderBy('day_number')
            ->get();

        return response()->json([
            'message' => 'Agenda generated.',
            'agenda' => AgendaDayResource::collection($days),
        ], 201);
    }
}
