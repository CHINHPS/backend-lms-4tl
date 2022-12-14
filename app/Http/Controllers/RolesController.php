<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    public function list()
    {
        $data = DB::table('role')->orderBy('id', 'desc')->paginate(10);
        return response()->json($data);
    }

    public function delete(Request $request)
    {
        try {
            DB::table('role')->where('id', $request->id)->delete();
            return response()->json(["msg" => "Xóa thành công id $request->id!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
    public function new(Request $request)
    {
        try {
            $data = DB::table('role')->insert([
                'role_code' => $request->role_code,
                'role_name' => $request->role_name,
            ]);
            return response()->json(["msg" => "Thêm thành công!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function getOne(Request $request)
    {
        $data = DB::table('role')->where('id', $request->id)->first();
        return response()->json($data);
    }

    public function put(Request $request)
    {
        try {
            DB::table('role')->where('id', $request->id)->update([
                'role_code' => $request->role_code,
                'role_name' => $request->role_name,
            ]);
            return response()->json(["msg" => "Sửa thành công id $request->id!"]);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
    public function listFull()
    {
        $data = DB::table('role')->get();
        return response()->json($data);
    }
}
