<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminDashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'TotalBookings',
        'TotalRevenue',
        'BookingToday',
        'PeakTime',
    ];
}