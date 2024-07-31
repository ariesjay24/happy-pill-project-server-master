<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail; // Add this line
// use App\Mail\BookingNotification; // Add this line
use App\Services\SemaphoreService;
use App\Services\PayPalService;

class BookingController extends Controller
{
    protected $semaphoreService;
    protected $payPalService;

    public function __construct(SemaphoreService $semaphoreService, PayPalService $payPalService)
    {
        $this->semaphoreService = $semaphoreService;
        $this->payPalService = $payPalService;
    }

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
        }

        return response()->json([
            "bookings" => $bookings,
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'serviceType' => 'required|string',
                'bookingDate' => 'required|date',
                'bookingTime' => 'required|date_format:H:i',
                'location' => 'required|string',
                'addOns' => 'nullable|array',
                'userID' => 'required|integer',
                'total' => 'required|numeric',
            ]);

            \Log::info('Validated data:', $validatedData);

            $totalPrice = $validatedData['total'];

            $service = Service::where('Name', $validatedData['serviceType'])->firstOrFail();
            $serviceID = $service->ServiceID;

            $booking = Booking::create([
                'UserID' => $validatedData['userID'],
                'ServiceID' => $serviceID,
                'ServiceName' => $validatedData['serviceType'],
                'BookingDate' => $validatedData['bookingDate'],
                'BookingTime' => $validatedData['bookingTime'],
                'Location' => $validatedData['location'],
                'AddOns' => $validatedData['addOns'] ? json_encode($validatedData['addOns']) : null,
                'Price' => $totalPrice,
                'Status' => 'Pending',
                'payment_status' => 'Unpaid',
                'PaymentID' => null,
            ]);

            $user = User::find($booking->UserID);
            $message = "Thank you for your booking, {$user->FirstName}. Your booking for {$booking->ServiceName} on {$booking->BookingDate} at {$booking->BookingTime} is confirmed.";
            $this->semaphoreService->sendSms($user->PhoneNumber, $message);

            // Send booking notification email
            // Mail::to($user->Email)->send(new BookingNotification($user, $booking));

            return response()->json(['booking' => $booking], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating booking: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to create booking.'], 500);
        }
    }
    
    


    
    

    public function update(Request $request, $id)
    {
        try {
            // Find the booking by BookingID
            $booking = Booking::where('BookingID', $id)->firstOrFail();

            $fields = $request->validate([
                "ServiceType" => "string",
                "AddOns" => "nullable|array",
                "BookingDate" => "date",
                "BookingTime" => "nullable|date_format:H:i",
                "Location" => "string",
                "Status" => "string",
                "payment_status" => "string",
            ]);

            if ($request->has('ServiceType')) {
                $service = Service::where('Name', $request->ServiceType)->firstOrFail();
                $booking->ServiceID = $service->ServiceID;
                $booking->ServiceName = $service->Name;
            }

            if ($request->has('AddOns')) {
                $price = $service->Price;
                foreach ($request->AddOns as $addOnName) {
                    $addOn = Service::where('Name', $addOnName)->first();
                    if ($addOn) {
                        $price += $addOn->Price;
                    }
                }
                $booking->AddOns = json_encode($request->AddOns);
                $booking->Price = $price;
            }

            $booking->BookingDate = $request->BookingDate ?? $booking->BookingDate;
            $booking->BookingTime = $request->BookingTime ?? $booking->BookingTime;
            $booking->Location = $request->Location ?? $booking->Location;
            $booking->Status = $request->Status ?? $booking->Status;
            $booking->payment_status = $request->payment_status ?? $booking->payment_status;
            $booking->save();

            // Send SMS notification after booking is updated
            $user = User::find($booking->UserID);
            $message = "Dear {$user->FirstName}, your booking for {$booking->ServiceName} on {$booking->BookingDate} at {$booking->BookingTime} has been updated.";
            $this->semaphoreService->sendSms($user->PhoneNumber, $message);

            return response()->json([
                "booking" => $booking,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Booking not found: ' . $e->getMessage());
            return response()->json(['error' => 'Booking not found.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error updating booking: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to update booking.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Find the booking by BookingID
            $booking = Booking::where('BookingID', $id)->firstOrFail();
            $booking->delete();

            return response()->json([
                "message" => "Booking deleted",
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Booking not found: ' . $e->getMessage());
            return response()->json(['error' => 'Booking not found.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting booking: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete booking.'], 500);
        }
    }
    public function confirm(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $booking->Status = 'Confirmed';
            $booking->save();

            // Send SMS notification after booking is confirmed
            $user = User::find($booking->UserID);
            $message = "Dear {$user->FirstName}, your booking for {$booking->ServiceName} on {$booking->BookingDate} at {$booking->BookingTime} has been confirmed.";
            $this->semaphoreService->sendSms($user->PhoneNumber, $message);

            return response()->json(['message' => 'Booking confirmed'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to confirm booking'], 500);
        }
    }

    public function initiatePayment($id)
    {
        try {
            $booking = Booking::where('BookingID', $id)->firstOrFail();
            $amount = $booking->Price;

            $currency = 'PHP'; // Change as needed

            // Ensure the PayPal service is properly configured
            if (!$this->payPalService) {
                Log::error('PayPal service is not configured.');
                return response()->json(['error' => 'Payment service is unavailable'], 500);
            }

            $response = $this->payPalService->createOrder(
                $amount,
                $currency,
                route('payment.callback', ['id' => $booking->BookingID]),
                route('payment.cancel', ['id' => $booking->BookingID])
            );

            // Log the entire response for debugging purposes
            Log::info('PayPal createOrder response:', ['response' => $response]);

            // Check if response is valid
            if ($response && isset($response->links) && is_array($response->links)) {
                foreach ($response->links as $link) {
                    if ($link->rel == 'approve') {
                        Log::info("PayPal payment approval URL: {$link->href}");
                        return response()->json(['paymentUrl' => $link->href]);
                    }
                }
                Log::warning('No approval URL found in PayPal response', ['response' => $response]);
                return response()->json(['error' => 'No approval URL found in PayPal response'], 500);
            } else {
                Log::error('Failed to create PayPal order or response is invalid', ['response' => $response]);
                return response()->json(['error' => 'Failed to create PayPal order'], 500);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Booking not found:', ['BookingID' => $id, 'message' => $e->getMessage()]);
            return response()->json(['error' => 'Booking not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error initiating PayPal payment:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to initiate PayPal payment', 'details' => $e->getMessage()], 500);
        }
    }

    public function handlePaymentCallback(Request $request, $id)
    {
        Log::info('Payment callback called', ['method' => $request->getMethod(), 'id' => $id, 'query' => $request->query()]);
    
        try {
            $orderId = $request->query('token'); // Ensure you're fetching the correct query parameter
    
            // Log the orderId to verify it's being fetched correctly
            Log::info('Order ID (token) fetched from query', ['orderId' => $orderId]);
    
            if (!$orderId) {
                Log::error('Order ID (token) missing in request query', ['query' => $request->query()]);
                return response()->json(['error' => 'Order ID missing'], 400);
            }
    
            $result = $this->payPalService->captureOrder($orderId);
    
            if ($result) {
                $booking = Booking::findOrFail($id);
                $booking->payment_status = 'Paid';
                $booking->save();
    
                Log::info('Payment status updated to Paid', ['BookingID' => $id, 'orderId' => $orderId]);
                return response()->json(['message' => 'Payment successful'], 200);
            } else {
                Log::error('Failed to capture PayPal order', ['orderId' => $orderId]);
                return response()->json(['error' => 'Payment failed'], 500);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Booking not found', ['BookingID' => $id, 'message' => $e->getMessage()]);
            return response()->json(['error' => 'Booking not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error handling PayPal payment callback', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to update payment status', 'details' => $e->getMessage()], 500);
        }
    }
    
    
    public function handlePaymentCancel(Request $request, $id)
    {
        Log::info("Payment cancellation handled for booking ID: {$id}");
        \Log::info('Handling PayPal payment cancellation', ['booking_id' => $id]);
        return redirect()->route('payment.cancel');
    }
}    