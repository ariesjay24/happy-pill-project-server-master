<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->input('UserID');
    
        $bookings = Booking::with(['user'])
            ->when($userId, function ($query, $userId) {
                return $query->where('UserID', $userId);
            })
            ->get(['BookingID', 'UserID', 'ServiceName', 'BookingDate', 'BookingTime', 'Location', 'AddOns', 'Price', 'Status', 'payment_status', 'PaymentID']);
    
        foreach ($bookings as $booking) {
            $booking->FullName = $booking->user ? $booking->user->FullName : 'Unknown User';
            $booking->AddOns = json_decode($booking->AddOns, true) ?? [];
            $booking->Price = $booking->Price ?? 0; // Ensure Price is not null
            Log::info('Booking Data:', $booking->toArray());
        }
    
        return response()->json([
            "bookings" => $bookings,
        ], 200);
    }
    
    public function update(Request $request, $id)
    {
        Log::info('Update request received for Booking ID:', ['id' => $id]);
        
        $booking = Booking::where('BookingID', $id)->firstOrFail(); // Ensure this is correct
        $booking->update($request->all());
        
        return response()->json(['message' => 'Booking updated successfully']);
    }

    public function destroy($id)
    {
        $booking = Booking::where('BookingID', $id)->firstOrFail(); // Ensure this is correct
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
