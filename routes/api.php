<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CourseController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/checkDB', [PostController::class, 'checkDB']);
Route::get('/posts', [PostController::class, 'index']);
Route::post('/posts', [PostController::class, 'store']);


Route::prefix('courses')->group(function () {
    Route::post('/addCourse', [CourseController::class, 'addCourse']);         // Store a new course
    Route::get('/getCourseListapi', [CourseController::class, 'getCourseListapi']);          // Get all courses
    Route::put('{id}', [CourseController::class, 'updateCourse']);      // Update a specific course
    Route::get('getCourseById', [CourseController::class, 'getCourseById']);        // Show a specific course by ID
    Route::delete('/deleteCourse', [CourseController::class, 'deleteCourse']);  // Delete a specific course
});