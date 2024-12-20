<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Capability;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CourseController extends Controller
{
    public function addCourse(Request $request)
    {
        // Define the validation rules
        $rules = [
            'courseName' => 'required|string|max:255',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'courseImage' => 'required|string', // Assuming image is base64 encoded or path
            'capability' => 'required|array',
            'capability.*.capabilityName' => 'required|string|max:255',
            'capability.*.skill' => 'required|array',
            'capability.*.skill.*.skillName' => 'required|string|max:255',
        ];

        // Create the validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check if the validation fails
        if ($validator->fails()) {
            // Return the validation errors as a response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Proceed with course creation if validation passes
        try {
            // Create the course
            $course = Course::create([
                'courseName' => $request->input('courseName'),
                'startDate' => $request->input('startDate'),
                'endDate' => $request->input('endDate'),
                'courseImage' => $request->input('courseImage'),
            ]);

            // Log course creation attempt
            \Log::info("Course created successfully", ['courseId' => $course->id]);

            // Prepare the capability IDs array (collect IDs, not models)
            $capabilityIds = [];
            // Loop through each capability provided in the request

            // foreach ($request->input('capability') as $capabilityData) {
            // $capability = Capability::firstOrCreate([
            // 'capabilityName' => $capabilityData['capabilityName'],
            // ]);
            // \Log::info("Processing capability", ['capabilityName' => $capability->capabilityName]);
            // $skillIds = [];
            // foreach ($capabilityData['skill'] as $skillData) {
            // $skill = Skill::firstOrCreate([
            // 'skillName' => $skillData['skillName'],
            // ]);
            // \Log::info("Processing skill", ['skillName' => $skill->skillName]);
            // 
            // $skillIds[] = $skill->id;
            // }
            // 
            // $capability->skills()->sync($skillIds);
            // $capabilityIds[] = $capability->id;
            // }

            // Use array_map for processing capabilities and skills
            $capabilityIds = array_map(function ($capabilityData) use ($course) {
                // Check if the capability already exists, otherwise create it
                $capability = Capability::firstOrCreate([
                    'capabilityName' => $capabilityData['capabilityName'],
                ]);

                // Log capability creation or found
                \Log::info("Processing capability", ['capabilityName' => $capability->capabilityName]);

                // Use array_map to process the skills for each capability
                $skillIds = array_map(function ($skillData) {
                    // Check if the skill exists, otherwise create it
                    $skill = Skill::firstOrCreate([
                        'skillName' => $skillData['skillName'],
                    ]);

                    // Log skill creation or found
                    \Log::info("Processing skill", ['skillName' => $skill->skillName]);

                    // Return the skill ID to be collected
                    return $skill->id;
                }, $capabilityData['skill']);

                // Attach skills to the capability (sync skills)
                $capability->skills()->sync($skillIds);

                // Return the capability ID to be associated with the course
                return $capability->id;
            }, $request->input('capability'));

            // Sync capabilities to the course (sync capability IDs)
            $course->capabilities()->sync($capabilityIds);


            // Now attach the capabilities to the course (sync with the capability IDs)
            $course->capabilities()->sync($capabilityIds);

            // Log successful course-capability linking
            \Log::info("Capabilties linked to course", ['courseId' => $course->id, 'capabilityIds' => $capabilityIds]);

            // Return success response
            return response()->json([
                'message' => 'Course created successfully.',
                'data' => $course
            ], 201);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error("Error creating course: " . $e->getMessage(), ['exception' => $e]);

            // Return error response with the exception message
            return response()->json(['message' => 'An error occurred while creating the course.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCourseListapi(Request $request)
    {
        // dd($request);
        // Get page and limit from request
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        // Paginate the courses, including the related capabilities and skills
        $courses = Course::with(['capabilities.skills']) // eager load capabilities and skills
            ->paginate($limit);

        // Format the response
        $data = $courses->map(function ($course) {
            return [
                'courseName' => $course->courseName,
                'startDate' => $course->startDate,
                'endDate' => $course->endDate,
                'capability' => $course->capabilities->map(function ($capability) {
                    // dd($capability);
                    return [
                        'capabilityId' => $capability->_id,
                        'capabilityName' => $capability->capabilityName,
                        'courseId' => $capability->course_ids[0],
                        'skill' => $capability->skills->map(function ($skill) use ($capability) {
                            return [
                                'skillId' => $skill->_id,
                                'skillName' => $skill->skillName,
                                'capabilityId' => $capability->_id,
                                'courseId' => $capability->course_ids[0]
                            ];
                        })
                    ];
                })
            ];
        });
        // dd($data);
        // Return the paginated response
        return response()->json([
            'statusCode' => 200,
            'data' => $data,
            'pageable' => [
                'total' => $courses->total(),
                'limit' => $courses->perPage(),
                'page' => $courses->currentPage(),
            ]
        ]);
    }

    public function updateCourse(Request $request, $courseId)
    {
        // Define validation rules
        $rules = [
            'courseName' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'courseImage' => 'nullable|string', // Assuming image is base64 encoded or path
            'capability' => 'nullable|array',
            'capability.*.capabilityId' => 'nullable|exists:capabilities,_id', // Validate capability ID
            'capability.*.capabilityName' => 'nullable|string|max:255',
            'capability.*.skill' => 'nullable|array',
            'capability.*.skill.*.skillId' => 'nullable|exists:skills,_id', // Validate skill ID
            'capability.*.skill.*.skillName' => 'nullable|string|max:255',
        ];

        // Create the validator instance
        $validator = Validator::make($request->all(), $rules);
        // Check if the validation fails
        if ($validator->fails()) {
            // Return the validation errors as a response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the course by ID
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course not found.'], 404);
        }

        try {
            // Update the course details if provided in the request
            if ($request->has('courseName')) {
                $course->courseName = $request->input('courseName');
            }
            if ($request->has('startDate')) {
                $course->startDate = $request->input('startDate');
            }
            if ($request->has('endDate')) {
                $course->endDate = $request->input('endDate');
            }
            if ($request->has('courseImage')) {
                $course->courseImage = $request->input('courseImage');
            }

            // Save the updated course
            $course->save();
            \Log::info("Course updated successfully", ['courseId' => $courseId]);

            // If there are capabilities to update
            if ($request->has('capability')) {
                foreach ($request->input('capability') as $capabilityData) {
                    // Find the capability by ID
                    $capability = Capability::find($capabilityData['capabilityId']);
                    // dd($capability);
                    if ($capability) {
                        // Check if the capability is associated with this course
                        if (!$course->capabilities->contains($capability->id)) {
                            return response()->json(['message' => 'This capability is not associated with this course.'], 400);
                        }

                        // Update the capability name if provided
                        if (isset($capabilityData['capabilityName'])) {
                            $capability->capabilityName = $capabilityData['capabilityName'];
                            $capability->save();
                            \Log::info("Capability updated", ['capabilityId' => $capability->id]);
                        }

                        // If there are skills to update in this capability
                        if (isset($capabilityData['skill'])) {
                            foreach ($capabilityData['skill'] as $skillData) {
                                // Find the skill by ID
                                $skill = Skill::find($skillData['skillId']);

                                if ($skill) {
                                    // Check if the skill is associated with the capability
                                    if (!$capability->skills->contains($skill->id)) {
                                        return response()->json(['message' => 'This skill is not associated with this capability.'], 400);
                                    }

                                    // Update the skill name if provided
                                    if (isset($skillData['skillName'])) {
                                        $skill->skillName = $skillData['skillName'];
                                        $skill->save();
                                        \Log::info("Skill updated", ['skillId' => $skill->id]);
                                    }
                                } else {
                                    return response()->json(['message' => 'Skill not found.'], 404);
                                }
                            }
                        }
                    } else {
                        return response()->json(['message' => 'Capability not found.'], 404);
                    }
                }
            }

            // Return success message
            return response()->json([
                'data' => 'Updated Successfully'
            ], 200);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error("Error updating course: " . $e->getMessage(), ['courseId' => $courseId, 'exception' => $e]);

            // Return error response with the exception message
            return response()->json(['message' => 'An error occurred while updating the course.', 'error' => $e->getMessage()], 500);
        }
    }


    public function deleteCourse(Request $request)
    {
        // dd($request);
        // Validate incoming request for courseId
        // $validated = $request->validate([
        // 'courseId' => 'required|exists:courses,_id',  // Ensures the courseId exists in the courses collection
        // ]);

        // Get the courseId from validated data
        // $courseId = $validated['courseId'];

        // Validate incoming request for courseId
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|bail|regex:/^[a-fA-F0-9]{24}$/',  // Ensure courseId is a valid ObjectId format
        ]);

        // Check if the validation fails
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid courseId format.'], 400);
        }

        // Validate if the courseId exists in the `courses` collection
        $courseId = $request->input('courseId');
        // dd($courseId);
        // Find the course by ID
        $course = Course::find($courseId);
        // dd($course);
        if (!$course) {
            // Return 404 if course not found
            return response()->json(['message' => 'Course not found.'], 404);
        }

        try {
            // Log the course deletion attempt
            \Log::info("Deleting course and related data", ['courseId' => $courseId]);

            // Delete related capabilities by courseId
            $capabilities = Capability::where('course_ids', $courseId)->get();
            \Log::info("Found capabilities for courseId", ['capabilities' => $capabilities]);

            // If capabilities are found, delete related skills and capabilities
            foreach ($capabilities as $capability) {
                \Log::info("Deleting related skills for capability", ['capabilityId' => $capability->_id]);

                // Delete related skills (assuming there's a `skills()` relationship method)
                if ($capability->skills()->count() > 0) {
                    $capability->skills()->delete();
                    \Log::info("Deleted related skills for capability", ['capabilityId' => $capability->_id]);
                }

                // Now, delete the capability itself
                $capability->delete();
                \Log::info("Deleted capability", ['capabilityId' => $capability->_id]);
            }

            // Finally, delete the course
            $course->delete();
            \Log::info("Deleted course", ['courseId' => $courseId]);

            // Return success message if everything went fine
            return response()->json(['message' => 'Course and its related data deleted successfully.'], 200);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error("Error deleting course: " . $e->getMessage(), ['courseId' => $courseId, 'exception' => $e]);

            // Return error response with the exception message
            return response()->json(['message' => 'An error occurred while deleting the data.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCourseById(Request $request)
    {
        // Validate incoming request for courseId
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|bail|regex:/^[a-fA-F0-9]{24}$/',  // Ensure courseId is a valid ObjectId format
        ]);

        // Check if the validation fails
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid courseId format.'], 400);
        }

        // Validate if the courseId exists in the `courses` collection
        $courseId = $request->input('courseId');
        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['message' => 'Course not found.'], 404);
        }

        // Log the course retrieval attempt
        \Log::info("Course retrieved successfully", ['courseId' => $courseId]);

        // Return the course data as JSON
        return response()->json([
            'message' => 'Course found.',
            'data' => $course
        ], 200);
    }
}
