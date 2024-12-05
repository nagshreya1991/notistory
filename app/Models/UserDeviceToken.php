<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeviceToken extends Model
{
    protected $fillable = ['user_id', 'device_token', 'device_type'];

    public static function updateOrCreateToken($userId, $newToken, $deviceType)
    {
        return self::updateOrCreate(
            ['user_id' => $userId], // Condition to check by user_id
            ['device_token' => $newToken, 'device_type' => $deviceType] // Values to update
        );
    }
}
