<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Course extends Eloquent
{
    // Optional: specify the MongoDB connection if it's different from the default
    protected $connection = 'mongodb';  // You can skip this if you're using the default MongoDB connection

    protected $fillable = [
        'courseName', 'startDate', 'endDate', 'courseImage','capability'
    ];

    // In Course.php model
    public function capabilities()
    {
        return $this->belongsToMany(Capability::class);
    }
}

