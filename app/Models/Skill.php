<?php

// app/Models/Skill.php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Skill extends Eloquent
{
    // Optional: specify the MongoDB connection if it's different from the default
    protected $connection = 'mongodb';  // You can skip this if you're using the default MongoDB connection

    // Specify the fillable fields for mass assignment
    protected $fillable = ['skillName'];

    // Define the relationship to Capability (many-to-many)
    public function capabilities()
    {
        // MongoDB many-to-many relationship
        return $this->belongsToMany(Capability::class);
    }
}
