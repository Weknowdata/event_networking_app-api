<?php

namespace App\Http\Controllers;

use App\Models\AgendaSlot;
use App\Models\BoothVisit;
use App\Models\SessionAttendance;
use App\Services\ChallengeEvaluator;
use App\Services\QrTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScanController extends Controller
{
    public function session(Request $request, ChallengeEvaluator $evaluator, QrTokenService $qrTokens): JsonResponse
    {
        $validated = $request->validate([
            'agenda_slot_id' => ['required', 'integer', 'exists:agenda_slots,id'],
            'qr_token' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $slot = AgendaSlot::with('day')->findOrFail($validated['agenda_slot_id']);
        $eventDay = CarbonImmutable::parse($slot->day?->date ?? now());
        $eventDayNumber = $slot->day?->day_number;

        if (config('features.workshop_only_checkins', false) && $slot->type !== 'workshop') {
            throw ValidationException::withMessages([
                'agenda_slot_id' => ['Only workshop sessions allow check-in/out.'],
            ]);
        }

        $qrTokens->validateForAgendaSlot($validated['qr_token'], $slot->id);

        SessionAttendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'agenda_slot_id' => $slot->id,
                'event_day' => $eventDay->toDateString(),
            ],
            [
                'checked_in_at' => now(),
                'checked_out_at' => null,
                'source' => $validated['source'] ?? 'scan',
                'device_id' => $validated['device_id'] ?? null,
                'valid_for_points' => true,
            ],
        );

        $evaluator->evaluateForDay($user, $eventDay, $eventDayNumber);

        return response()->json([
            'message' => 'Session attendance recorded.',
        ], 201);
    }

    public function checkout(Request $request, QrTokenService $qrTokens): JsonResponse
    {
        $validated = $request->validate([
            'agenda_slot_id' => ['required', 'integer', 'exists:agenda_slots,id'],
            'qr_token' => ['nullable', 'string'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $slot = AgendaSlot::with('day')->findOrFail($validated['agenda_slot_id']);
        $eventDay = CarbonImmutable::parse($slot->day?->date ?? now());

        if (config('features.workshop_only_checkins', false) && $slot->type !== 'workshop') {
            throw ValidationException::withMessages([
                'agenda_slot_id' => ['Only workshop sessions allow check-in/out.'],
            ]);
        }

        $attendance = SessionAttendance::query()
            ->where('user_id', $user->id)
            ->where('agenda_slot_id', $slot->id)
            ->whereDate('event_day', '=', $eventDay)
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'agenda_slot_id' => ['No active check-in found for this session.'],
            ]);
        }

        if (isset($validated['qr_token'])) {
            $qrTokens->validateForAgendaSlot($validated['qr_token'], $slot->id);
        }

        $attendance->update([
            'checked_out_at' => now(),
            'device_id' => $validated['device_id'] ?? $attendance->device_id,
        ]);

        return response()->json([
            'message' => 'Session checkout recorded.',
        ]);
    }

    public function booth(Request $request, ChallengeEvaluator $evaluator): JsonResponse
    {
        $validated = $request->validate([
            'booth_code' => ['required', 'string', 'max:100'],
            'event_day' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:50'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $eventDay = CarbonImmutable::parse($validated['event_day'] ?? now());

        BoothVisit::updateOrCreate(
            [
                'user_id' => $user->id,
                'booth_code' => $validated['booth_code'],
                'event_day' => $eventDay->toDateString(),
            ],
            [
                'scanned_at' => now(),
                'source' => $validated['source'] ?? 'scan',
                'device_id' => $validated['device_id'] ?? null,
            ],
        );

        $evaluator->evaluateForDay($user, $eventDay);

        return response()->json([
            'message' => 'Booth visit recorded.',
        ], 201);
    }
}
