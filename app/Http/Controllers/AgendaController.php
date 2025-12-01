<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAgendaRequest;
use App\Http\Resources\AgendaDayResource;
use App\Models\AgendaDay;
use App\Models\SessionAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $days = AgendaDay::with(['slots.speakers', 'slots.day'])
            ->orderBy('day_number')
            ->get();

        $user = $request->user();

        if ($user) {
            $slots = $days->flatMap(fn ($day) => $day->slots);
            $slotIds = $slots->pluck('id')->all();

            if ($slotIds !== []) {
                $attendances = SessionAttendance::query()
                    ->where('user_id', $user->id)
                    ->whereIn('agenda_slot_id', $slotIds)
                    ->get()
                    ->keyBy('agenda_slot_id');

                foreach ($slots as $slot) {
                    $slot->setAttribute('checked_in', $attendances->has($slot->id));
                }
            }
        }

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
                        'type' => 'session',
                    ];
                }

                $day->slots()->createMany($slots);
            }
        });

        $days = AgendaDay::with(['slots.speakers', 'slots.day'])
            ->orderBy('day_number')
            ->get();

        return response()->json([
            'message' => 'Agenda generated.',
            'agenda' => AgendaDayResource::collection($days),
        ], 201);
    }
}
