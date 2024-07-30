<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    UserController,
    ServiceController,
    AvailabilityController,
    BookingController,
    ReviewController,
    CommunicationController,
    AdminDashboardController,
    AdminBookingController,
    AddOnController,
    ChatController,
    SmsController,
};

// Public routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Define the payment cancel route
Route::get('/payment/cancel/{id}', [BookingController::class, 'handlePaymentCancel'])->name('payment.cancel');
// Define the payment success route
Route::get('/payment/success', function () {
    return view('payment.success');
})->name('payment.success');
// Define the payment callback route
Route::get('/payment/callback/{id}', [BookingController::class, 'handlePaymentCallback'])->name('payment.callback');

// Public routes for Add-Ons
Route::get('/add_ons', [AddOnController::class, 'index']);
Route::get('/add_ons/{id}', [AddOnController::class, 'show']);

// Booking routes
Route::get('/bookings', [BookingController::class, 'index']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);
Route::post('/book', [BookingController::class, 'store']);
Route::put('/bookings/{id}', [BookingController::class, 'update']);
Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
Route::put('/bookings/confirm/{id}', [BookingController::class, 'confirm']);
Route::post('/bookings/{id}/initiate-payment', [BookingController::class, 'initiatePayment']);

// Remove the duplicate payment success and cancel routes
// Route::get('/payment/success', function () {
//     return view('payment.success');
// })->name('payment.success');

// Route::get('/payment/cancel', function () {
//     return view('payment.cancel');
// })->name('payment.cancel');

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Logout route
    Route::post('/logout', [UserController::class, 'logout']);

    // Service routes
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store'])->middleware('role:Admin');
    Route::put('/services/{id}', [ServiceController::class, 'update'])->middleware('role:Admin');
    Route::delete('/services/{id}', [ServiceController::class, 'destroy'])->middleware('role:Admin');

    // Availability routes
    Route::get('/availabilities', [AvailabilityController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/availabilities', [AvailabilityController::class, 'store'])->middleware('role:Admin');
    Route::delete('/availabilities/{date}', [AvailabilityController::class, 'destroy'])->middleware('role:Admin');

    // Review routes
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Communication routes
    Route::get('/communications', [CommunicationController::class, 'index']);
    Route::get('/communications/{id}', [CommunicationController::class, 'show']);
    Route::post('/communications', [CommunicationController::class, 'store']);
    Route::put('/communications/{id}', [CommunicationController::class, 'update']);
    Route::delete('/communications/{id}', [CommunicationController::class, 'destroy']);

    // Sms routes
    Route::post('/send-sms', [SmsController::class, 'sendSms']);

    // Message routes
    Route::get('/current-user', [UserController::class, 'currentUser']);
    Route::get('/messages', [ChatController::class, 'fetchMessages']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);

    // Admin-specific routes
    Route::middleware(['role:Admin'])->group(function () {
        // AdminDashboard routes
        Route::get('/admin-dashboards', [AdminDashboardController::class, 'index']);
        Route::get('/admin-dashboards/{id}', [AdminDashboardController::class, 'show']);
        Route::post('/admin-dashboards', [AdminDashboardController::class, 'store']);
        Route::put('/admin-dashboards/{id}', [AdminDashboardController::class, 'update']);
        Route::delete('/admin-dashboards/{id}', [AdminDashboardController::class, 'destroy']);

        // Admin Booking routes
        Route::get('/admin/bookings', [AdminBookingController::class, 'index']);
        Route::put('/admin/bookings/{id}', [AdminBookingController::class, 'update']);
        Route::delete('/admin/bookings/{id}', [AdminBookingController::class, 'destroy']);

        // Admin registration route
        Route::get('/register-admin', [UserController::class, 'registerAdmin']);

        // Protected Add-On routes
        Route::post('/add_ons', [AddOnController::class, 'store']);
        Route::put('/add_ons/{id}', [AddOnController::class, 'update']);
        Route::delete('/add_ons/{id}', [AddOnController::class, 'destroy']);
    });

    // Add the broadcasting routes here
    Broadcast::routes(['middleware' => ['auth:sanctum']]);
    Broadcast::channel('chat', function ($user) {
        return Auth::check();
    });
});
