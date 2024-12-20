<?php

// app/Models/Capability.php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Capability extends Eloquent
{
    // Optional: specify the MongoDB connection if it's different from the default
    protected $connection = 'mongodb';  // You can skip this if you're using the default MongoDB connection

    // Specify the fillable fields for mass assignment
    protected $fillable = ['capabilityName'];

    // Define the relationship to Skill (many-to-many)
    public function skills()
    {
        // MongoDB many-to-many relationship
        return $this->belongsToMany(Skill::class);
    }
}
