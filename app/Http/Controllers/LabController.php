<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabResource;
use App\Http\Resources\UploadLabResource;
use App\Models\Course;
use App\Models\Lab;
use App\Models\PointSubmit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class LabController extends Controller
{
    public function download($file)
    {
        return Storage::disk('s3')->response('labs/' . $file);
    }

    public function download_all($slug_course)
    {
        // $zip = new ZipArchive;
        // $course = Course::with(['point_submits' => function ($query) {
        //     $query->where('pointsubmitable_type','labs');
        // }])->where('slug',$slug_course)->firstOrFail();

        // $fileName = 'Lab-' . $course->class_code . '.zip';

        // foreach ($course->point_submits as $point_submits) {
        //     $labs = json_decode($point_submits->content,true);
        //     if(isset($labs[count($labs)-1])) {
        //         var_dump($labs[count($labs)-1]);
        //     }
        // }
        // return ($course);

        // $fileName = '';
        // return Storage::disk('s3')->response('labs/' . $file);
    }
    
    public function delete($slug)
    {
        $data = Lab::where('slug', $slug)->first()->delete();
        if ($data) {
            return BaseResponse::ResWithStatus('Xóa thành công!');
        }
        return BaseResponse::ResWithStatus('Không tìm thấy để xóa!', 404);
    }

    public function getOne($slug)
    {
        $data = Lab::where('slug', $slug)->first();
        return new LabResource($data);
    }

    public function upsert(Request $request)
    {
        $id = $request->input('id',null);
        $slugCourse = $request->input('slugCourse');
        $name = $request->input('nameLab');
        $level = $request->input('level');
        $range = $request->input('rangeLab');
        $description = $request->input('description');

        try {
            $course = Course::with('labs', 'subject.labs')->where('slug', $slugCourse)->first();

            $dataUpsert = [
                'name' => $name,
                'level' => $level,
                'description' => ($description != 'null') ? $description : null,
                'slug' => Str::slug($name . Str::random(8)),
            ];

            if ($range == 'subjects') {
                $data_course = $course->subject->labs()->updateOrCreate([
                    'id' => $id
                ], $dataUpsert);
            } else {
                $data_course = $course->labs()->updateOrCreate([
                    'id' => $id
                ], $dataUpsert);
            }
            return BaseResponse::ResWithStatus(!$data_course->wasRecentlyCreated && $data_course->wasChanged() ? "Sửa thành công!" : 'Tạo mới Lab thành công! Cần cấu hình để có thể làm bài', 200);
        } catch (\Exception $err) {
            return BaseResponse::ResWithStatus($id ? "Có lỗi khi sửa!" : 'Có lỗi xảy ra khi tạo mới!', 500);
        }
    }

    public function joinLab(Request $request)
    {
        $password = $request->input('password') ?? null;
        $slug_lab = $request->input('slug_lab');
        $slug_course = $request->input('slug_course');

        $lab = Lab::with(['deadlines', 'labable', 'point_submit' => function ($query) {
            $query->where([
                'user_id' => Auth::id(),
                'status' => 1
            ]);
        }])->where('slug', $slug_lab)->first();

        if (!isset($lab->deadlines)) {
            return BaseResponse::ResWithStatus("Bài tập này chưa được Giảng viên cấu hình!", 403);
        }

        if ($lab->deadlines->password != null && $password != $lab->deadlines->password) {
            return BaseResponse::ResWithStatus("Mật khẩu sai không thể nộp bài!", 403);
        }

        # kiểm tra xem còn trong thời gian deadline hay không
        $now = Carbon::now();
        if ($lab->deadlines->time_end < $now) {
            return BaseResponse::ResWithStatus("Hết thời gian nộp bài!", 403);
        }

        if (isset($lab->point_submit->content)) {
            $count = count(json_decode($lab->point_submit->content, true));
        } else {
            $count = 0;
        }

        if ($count >= $lab->deadlines->max_working) {
            return BaseResponse::ResWithStatus("Số File quá giới hạn quy định!", 403);
        }

        return new UploadLabResource([
            "data" => [
                'info_lab' => $lab,
            ]
        ]);
    }

    public function submit_lab(Request $request)
    {
        $id_point = $request->input('id_point');
        $slug_course = $request->input('slug_course');
        $slug_lab = $request->input('slug_lab');


        $course = Course::where('slug', $slug_course)->first();
        $lab = Lab::with('deadlines', 'labable', 'point_submit')->where('slug', $slug_lab)->first();

        $data_point = PointSubmit::with('pointsubmitable.deadlines')->find($id_point);
        if (!$data_point) {
            $new_point = $lab->point_submit()->create([
                'user_id' => Auth::id(),
                'course_id' => $course->id ?? 0,
                'content' => '[]',
                'point' => null,
                'status' => 1, # đã làm
            ]);
            $data_point = PointSubmit::with('pointsubmitable.deadlines')->find($new_point->id);
        }

        # kiểm tra xem số lượng file nộp lên nhiều hơn không
        $check_file = json_decode($data_point->content, true);

        if ((count($check_file) + count($request->file('listFile'))) > $data_point->pointsubmitable->deadlines->max_working) {
            return BaseResponse::ResWithStatus("Số lượng bài cũ và mới vượt quá số File được phép nộp!", 403);
        }

        $files = [];
        if ($request->hasfile('listFile')) {

            $content = json_decode($data_point->content, true);
            foreach ($request->file('listFile') as $file) {
                $name = Auth::id() . '-' . time() . rand(1, 100) . '.' . $file->extension();
                $file->storeAs('labs/', $name, 's3');
                $elmFile = [
                    "name" => $file->getClientOriginalName(),
                    "link" => $name
                ];
                $files[] = $elmFile;
                $content = [...$content, $elmFile];
            }

            $data_point->content = json_encode($content);
            $data_point->save();
            return BaseResponse::ResWithStatus("Nộp bài thành công!", 200);
        }
    }
}
