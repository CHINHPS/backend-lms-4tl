<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseJoined;
use App\Models\Subject;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursesController extends Controller
{

    public function getQuizLabCourse($slug, $role = 'LECTURER')
    {
        # lấy Quiz, Lab, sinh viên tham gia khóa học
        $course = Course::with(
            [
                'quizs.deadlines',
                'quizs.point_submit' => function ($query) use ($role) {
                    $role == 'STUDENT' ? $query->where('user_id', Auth::id()) : null;
                },
                'labs.deadlines',
                'labs.point_submit' => function ($query) use ($role) {
                    $role == 'STUDENT' ? $query->where('user_id', Auth::id()) : null;
                },
                'course_joined'
            ]
        )->where('slug', $slug)->first();

        # lấy thêm bài Quiz + Lab nếu là mặc định phải có
        $subject = Subject::with(
            [
                'quizs.deadlines',
                'quizs.point_submit' => function ($query) use ($role) {
                    $role == 'STUDENT' ? $query->where('user_id', Auth::id()) : null;
                },
                'labs.deadlines',
                'labs.point_submit' => function ($query) use ($role) {
                    $role == 'STUDENT' ? $query->where('user_id', Auth::id()) : null;
                },
            ]
        )->find($course->subject_id);

        $list_document = [...$course->documents, ...$subject->documents];
        // usort($list_document, function ($a, $b) {
        //     return $a['level'] <=> $b['level'];
        // });

        $list_quiz = [...$course->quizs, ...$subject->quizs];
        usort($list_quiz, function ($a, $b) {
            return $a['level'] <=> $b['level'];
        });

        $list_lab = [...$course->labs, ...$subject->labs];
        usort($list_lab, function ($a, $b) {
            return $a['level'] <=> $b['level'];
        });

        return [$course, $subject, $list_quiz, $list_lab, $list_document];
    }


    public function showDocQuizLab($slug)
    {

        $role_code = Auth::user()->role->role_code;
        # lấy Quiz, Lab, sinh viên
        list($data, $subject, $list_quiz, $list_lab, $list_document) = $this->getQuizLabCourse($slug, $role_code);

        # lấy những thông tin cơ bản của khóa học
        $courseResource = [
            "data" => [
                'courses' => $data,
                'student_joined' => $data->course_joined,
                'documents' => $list_document,
                'labs' => $list_lab,
                'quizs' => $list_quiz,
            ]
        ];

        # giảng viên lấy thêm thông tin sinh viên đã làm bài
        $count_course_joined = $data->course_joined()->count();
        if ($role_code == 'LECTURER') {

            $data_lecturer_lab = [];
            $data_lecturer_quiz = [];

            foreach ($courseResource['data']['labs'] as $lab) {
                $data_lecturer_lab[] = [
                    "type" => "lab",
                    "student_worked" => $lab->point_submit()->orderBy('user_id')->count(),
                    "count_student" => $count_course_joined,
                ];
            }

            foreach ($courseResource['data']['quizs'] as $quiz) {
                $data_lecturer_quiz[] = [
                    "type" => "quiz",
                    "student_worked" => $quiz->point_submit()->orderBy('user_id')->count(),
                    "count_student" => $count_course_joined,
                ];
            }

            # merge thông tin giảng viên và với thông tin khóa học
            $labs = [];
            foreach ($data_lecturer_lab as $key => $lab_value) {
                $labs[] = [...$courseResource['data']['labs'][$key]->toArray(), ...$lab_value];
            }
            $quizs = [];
            foreach ($data_lecturer_quiz as $key => $quiz_value) {
                $quizs[] = [...$courseResource['data']['quizs'][$key]->toArray(), ...$quiz_value];
            }

            $courseResource['data']['labs'] = $labs;
            $courseResource['data']['quizs'] = $quizs;
        }
        # transform data trước khi trả về
        return CourseResource::collection($courseResource);
    }

    public function list()
    {
        $data = DB::table('courses')->join('subjects', 'courses.subject_id', '=', 'subjects.id')
            ->selectRaw('courses.name as course_name, subjects.name as subject_name, courses.id, courses.subject_id, courses.class_code, courses.status')->orderBy('id', 'desc')->paginate(10);
        return response()->json($data);
    }

    public function new(Request $request)
    {
        try {
            $data = DB::table('courses')->insert([
                'subject_id' => $request->subject_id,
                'class_code' => $request->class_code,
                'name' => $request->name,
                'status' => $request->status
            ]);
            return response()->json(["msg" => "Thêm thành công!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
    public function getTeacher()
    {
        $data = DB::table('users')->where('role_id', 2)->get();
        return response()->json($data);
    }
    public function getById(Request $request)
    {
        $data = DB::table('courses')->where('id', $request->id)->first();
        return response()->json($data);
    }
    public function put(Request $request)
    {
        try {
            DB::table('courses')->where('id', $request->id)->update([
                'subject_id' => $request->subject_id,
                'class_code' => $request->class_code,
                'name' => $request->name,
                'status' => $request->status,
            ]);
            return response()->json(["msg" => "Sửa thành công id $request->id!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
    public function delete(Request $request)
    {
        try {
            DB::table('courses')->where('id', $request->id)->delete();
            return response()->json(["msg" => "Xóa thành công id $request->id!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
}
