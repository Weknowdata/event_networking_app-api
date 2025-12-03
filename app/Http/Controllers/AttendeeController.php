<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendeeResource;
use App\Models\User;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    public function index(Request $request)
    {
        // Start with all attendee profiles and eager load their profile details to avoid N+1 queries.
        $query = User::query()->with('profile');

        if ($request->user()) {
            // Do not return the currently authenticated user in the listing.
            $query->whereKeyNot($request->user()->getKey());
        }

        if ($search = trim((string) $request->query('q', ''))) {
            // Basic multi-field search across user and profile attributes.
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('job_title', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    });
            });
        }

        $filter = $request->query('filter');

        if ($filter === 'first_timers') {
            // Narrow to attendees marked as first timers when requested.
            $query->whereHas('profile', fn ($profileQuery) => $profileQuery->where('is_first_timer', true));
        }

        // Return attendees in a consistent alphabetical order.
        $query->orderBy('name');

        // Clamp per-page to a reasonable range to avoid excessive payloads.
        $perPage = (int) $request->integer('per_page', 100);
        $perPage = max(1, min($perPage, 100));

        $attendees = $query->paginate($perPage)->appends($request->query());

        return AttendeeResource::collection($attendees);
    }
}
