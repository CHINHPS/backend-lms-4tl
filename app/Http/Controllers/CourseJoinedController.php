<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseJoinedResource;
use App\Models\Course;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseJoinedController extends Controller
{

    public function getListByCourse($slug)
    {
        $data = Course::with('course_joined.user.role')->where('slug', $slug)->first();
        // return $data;
        return CourseJoinedResource::collection($data->course_joined);
    }

    public function getMyCourse(Request $request)
    {
        $limit = $request->limit ?? 3;
        $data = DB::table('course_joined')
            ->selectRaw('*')
            ->join('courses', 'course_joined.course_id', '=', 'courses.id')
            ->where('user_id', Auth::id())
            // ->orderBy('id','asc')
            ->take($limit)->get();
        return response()->json($data);
    }

    public function joinCourse(Request $request)
    {

        $data = DB::table('courses')->where('id', $request->idCourse)->first();
        try {
            DB::table('course_joined')->insert([
                'course_id' => $request->idCourse,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'status' => 200,
                'slug' => $data->slug,
                'msg' => 'Tham gia thành công!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 201,
                'slug' => $data->slug,
                'msg' => 'Đã tham gia khóa học!'
            ]);
        }
    }
}
