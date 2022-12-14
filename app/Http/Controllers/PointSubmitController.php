<?php

namespace App\Http\Controllers;

use App\Exports\QuizExport;
use App\Http\Resources\PointSubmitResource;
use App\Models\Course;
use App\Models\PointSubmit;
use App\Models\Quiz;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PointSubmitController extends Controller
{
    public function export($type, $slug)
    {
        $typeFm = $type == 'quiz' ? 'quizs' : 'labs';
        $quizs = Course::with('course_joined.course')->where('slug', $slug)->first();
        list($data, $subject, $list_quiz, $list_lab) = (new CoursesController)->getQuizLabCourse($slug);

        $list = $type == 'quiz' ? $list_quiz : ($type == 'lab' ? $list_lab : [...$list_quiz, ...$list_lab]);

        if ($type == 'all') {
            $listUser = [
                'quizs' => $this->getPointFullSubject($quizs, $list_quiz, 'quizs'),
                'labs' => $this->getPointFullSubject($quizs, $list_lab, 'labs')
            ];
        } else {
            $listUser = $this->getPointFullSubject($quizs, $list, $typeFm);
        }

        $res = [
            'class' => ($type == 'quiz' ? 'QUIZ' : ($type == 'quiz' ? 'LAB' : "TẤT CẢ QUIZ & LAB")) . ' ' . $data->class_code,
            'students' => $listUser,
            'list' =>  $list,
        ];
        return Excel::download(new QuizExport($res), 'users.xlsx');
    }

    private function getPointFullSubject($quizs, $list, $typeFm)
    {
        $listUser = [];

        foreach ($quizs->course_joined as $student) {
            $dataPointStudent = collect($list)->map(function ($quiz) use ($student, $typeFm) {
                return $quiz->point_submit()->where([
                    "user_id" => $student->user_id,
                    "pointsubmitable_type" => $typeFm
                ])->get();
            });
            $listUser[] = [
                "user" => $student->user,
                "points" => $dataPointStudent
            ];
        }
        return $listUser;
    }

    public function getOneFormat($id)
    {
        $data = PointSubmit::with('user')->find($id);
        return new PointSubmitResource($data);
    }

    public function mark(Request $request)
    {
        $id = $request->input('id');
        $point = $request->input('point');
        $description = $request->input('description');
        try {
            $data = PointSubmit::where(["id" => $id, "pointsubmitable_type" => "labs"])->first();
            $data->point = $point;
            $data->note = $description;
            $data->status = 1;
            $data->save();
            return BaseResponse::ResWithStatus("Chấm điểm thành công!");
        } catch (\Exception $e) {
            return BaseResponse::ResWithStatus("Có lỗi đã xảy ra!", 500);
        }
    }

    public function getListSlug($type, $slug)
    {
        $data = Course::with([
            'point_submits' => function ($query) use ($type) {
                $query->where('pointsubmitable_type', $type)->orderBy('id', 'desc')
                    ->groupBy('user_id', 'pointsubmitable_id')->select('*', DB::raw('count(*) as total'))->get();
            }, 'point_submits.user', 'point_submits.pointsubmitable'
        ])->where('slug', $slug)->first();
        return PointSubmitResource::collection($data->point_submits);
    }

    public function list()
    {
        $data = DB::table('point_submit')->join('users', 'point_submit.user_id', '=', 'users.id')->join('courses', 'point_submit.course_id', '=', 'courses.id')
            ->orderBy('id', 'desc')->selectRaw('point_submit.id, users.name as user_name, courses.class_code, courses.name as course_name, point_submit.content, point_submit.point, point_submit.status, point_submit.pointSubmitable_type')->paginate(10);
        return response()->json($data);
    }

    public function delete(Request $request)
    {
        try {
            $data = PointSubmit::findOrFail($request->id);
            $data->delete();
            return BaseResponse::ResWithStatus("Xóa thành công id $request->id!");
        } catch (Exception $e) {
            return BaseResponse::ResWithStatus("Không tìm thấy bài để xóa!", 404);
        }
    }
    public function new(Request $request)
    {
        try {
            $data = DB::table('point_submit')->insert([
                'name' => $request->name

            ]);
            return response()->json(["msg" => "Thêm thành công!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function getOne(Request $request)
    {
        $data = DB::table('point_submit')->where('id', $request->id)->first();
        return response()->json($data);
    }

    public function put(Request $request)
    {
        try {
            DB::table('point_submit')->where('id', $request->id)->update([
                'name' => $request->name
            ]);
            return response()->json(["msg" => "Sửa thành công id $request->id!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
    public function listFull()
    {
        $data = DB::table('point_submit')->get();
        return response()->json($data);
    }
}
