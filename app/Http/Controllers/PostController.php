<?php

// app/Http/Controllers/PostController.php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PostController extends Controller
{

    public function checkDB()
    {
        try {
            // Attempt to get a raw MongoDB connection (PDO object).
            $connection = DB::connection('mongodb');
            $connected = $connection->getMongoClient()->listDatabases();  // This will confirm the connection.

            // If the connection is established, this should return the list of databases.
            if ($connected) {
                // Retrieve all posts from MongoDB (assuming a MongoDB collection named "posts").
                $posts = Post::all();  // You can replace 'Post' with your actual MongoDB model
                return response()->json([
                    'status' => 'success',
                    'message' => 'MongoDB connection is successful.',
                    'data' => $posts
                ]);
            }
        } catch (\Exception $e) {
            // Log the exception for debugging.
            \Log::error("MongoDB connection error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not connect to MongoDB: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Retrieve all posts from MongoDB
        $posts = Post::all();
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        // Insert a new post into MongoDB
        $post = Post::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);

        return response()->json($post, 201);
    }
}
