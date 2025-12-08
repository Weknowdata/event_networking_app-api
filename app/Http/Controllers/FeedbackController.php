<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $user = $request->user();

        $feedback = Feedback::updateOrCreate(
            ['user_id' => $user->id],
            [
                'rating' => $request->validated()['rating'],
                'comment' => $request->validated()['comment'],
            ]
        );

        return response()->json([
            'message' => $feedback->wasRecentlyCreated ? 'Feedback created.' : 'Feedback updated.',
            'feedback' => new FeedbackResource($feedback),
        ], $feedback->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id !== $user->id) {
            abort(403, 'You cannot view feedback for another user.');
        }

        $feedback = Feedback::where('user_id', $user->id)->first();

        if (! $feedback) {
            return response()->json([
                'message' => 'Feedback not found.',
            ], 404);
        }

        return response()->json([
            'feedback' => new FeedbackResource($feedback),
        ]);
    }

    public function update(UpdateFeedbackRequest $request, Feedback $feedback): JsonResponse
    {
        if ($request->user()->id !== $feedback->user_id) {
            abort(403, 'You cannot update feedback for another user.');
        }

        $feedback->fill($request->validated());
        $feedback->save();

        return response()->json([
            'message' => 'Feedback updated.',
            'feedback' => new FeedbackResource($feedback),
        ]);
    }
}
