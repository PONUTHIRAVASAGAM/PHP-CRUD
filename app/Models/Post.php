<?php

// app/Models/Post.php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Post extends Eloquent
{
    protected $connection = 'mongodb';  // Optional, but good to define if you have multiple DB connections
    protected $fillable = ['title', 'content'];
}
