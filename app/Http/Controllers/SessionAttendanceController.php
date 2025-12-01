<?php

namespace App\Http\Controllers;

use App\Models\SessionAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $records = SessionAttendance::query()
            ->with(['slot.day', 'slot.speakers'])
            ->where('user_id', $user->id)
            ->orderByDesc('event_day')
            ->orderBy('checked_in_at')
            ->get()
            ->map(function (SessionAttendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'agenda_slot_id' => $attendance->agenda_slot_id,
                    'event_day' => $attendance->event_day?->toDateString(),
                    'checked_in_at' => optional($attendance->checked_in_at)->toIso8601String(),
                    'checked_out_at' => optional($attendance->checked_out_at)->toIso8601String(),
                    'source' => $attendance->source,
                    'device_id' => $attendance->device_id,
                    'valid_for_points' => $attendance->valid_for_points,
                    'slot' => $attendance->slot ? [
                        'title' => $attendance->slot->title,
                        'start_time' => optional($attendance->slot->start_time)->format('H:i:s'),
                        'end_time' => optional($attendance->slot->end_time)->format('H:i:s'),
                        'location' => $attendance->slot->location,
                        'day' => $attendance->slot->day ? [
                            'day_number' => $attendance->slot->day->day_number,
                            'date' => $attendance->slot->day->date,
                        ] : null,
                        'speakers' => $attendance->slot->speakers->map(function ($speaker) {
                            return [
                                'name' => $speaker->name,
                                'title' => $speaker->title,
                                'company' => $speaker->company,
                            ];
                        })->all(),
                    ] : null,
                ];
            });

        return response()->json(['attendance' => $records]);
    }
}
