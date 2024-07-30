<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $primaryKey = 'BookingID'; // Use BookingID as the primary key
    public $incrementing = true; // Indicate that the primary key is auto-incrementing
    protected $keyType = 'int'; // Indicate that the primary key is a string

    protected $fillable = [
        'BookingID',
        'UserID',
        'ServiceID',
        'ServiceName',
        'BookingDate',
        'BookingTime',
        'Location',
        'AddOns',
        'Price',
        'Status',
        'payment_status',
        'PaymentID',
    ];

    protected $casts = [
        'AddOns' => 'json', // Ensure AddOns are cast to JSON
        'BookingDate' => 'date',
        'BookingTime' => 'datetime:H:i',
        'Price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'ServiceID', 'ServiceID');
    }
}
