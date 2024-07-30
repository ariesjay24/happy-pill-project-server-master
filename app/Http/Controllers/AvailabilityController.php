<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    public function index()
    {
        $availabilities = Availability::all();
        return response()->json($availabilities, 200);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Availability store request received', ['request' => $request->all()]);

            $request->validate([
                'date' => 'required|date',
                'available' => 'required|boolean',
            ]);

            $date = Carbon::parse($request->date)->startOfDay()->toDateString();

            Log::info('Parsed date', ['date' => $date]);

            $availability = Availability::updateOrCreate(
                ['date' => $date],
                ['available' => $request->available]
            );

            Log::info('Availability stored successfully', ['availability' => $availability]);

            return response()->json($availability, 201);
        } catch (\Exception $e) {
            Log::error('Error marking date as unavailable:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to mark date as unavailable'], 500);
        }
    }

    public function destroy($date)
    {
        try {
            Log::info('Availability destroy request received', ['date' => $date]);

            $parsedDate = Carbon::parse($date)->startOfDay()->toDateString();

            Log::info('Parsed date for deletion', ['date' => $parsedDate]);

            $availability = Availability::where('date', $parsedDate)->firstOrFail();
            $availability->delete();

            Log::info('Availability deleted successfully', ['date' => $parsedDate]);

            return response()->json(['message' => 'Date marked as available again'], 200);
        } catch (\Exception $e) {
            Log::error('Error unmarking date as unavailable:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to mark date as available again'], 500);
        }
    }
}
