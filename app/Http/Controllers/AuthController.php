<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLogin;
use App\Http\Requests\UserRegister;
use App\Models\User;
use App\Repositories\User\UserInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public UserInterface $userRepository;

    public function __construct(UserInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function Login(UserLogin $request)
    {
        $validated = $request->validated();
        if (Auth::attempt($validated)) {
            session()->regenerate();
            return $request->user();
        }
        return response()->json(['msg' => 'Dang nhap that bai'], 400);
    }

    // Dang ky
    public function Register(UserRegister $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create([
            ...$validated,
            'status' => 1,
            'role_id' => 1,
            'class_id' => 1
        ]);
        return response()->json(['user' => $user, 'msg' => 'Tao tai khoan thanh cong!']);
    }

    public function getMe()
    {
        $data_user = Auth::user()->load(['role']);
        return BaseResponse::ResWithStatus($data_user);
    }

    public function change_password(Request $request)
    {
        $password_old = $request->input('password_old');
        $password = $request->input('password');
        // $password = $request->input('password_confirmation');

        $userData = User::find(Auth::id());
        if ($userData->password == null || Hash::check($password_old, $userData->password)) {
            $userData->password = Hash::make($password);
            $userData->save();
            return BaseResponse::ResWithStatus("Đổi mật khẩu thành công!");
        } else {
            return BaseResponse::ResWithStatus("Mật khẩu cũ bạn nhập chưa đúng!",403);
        }
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['msg' => 'Dang xuat thành công!']);
    }

    public function ListUsers()
    {
        $data = $this->userRepository->getList();
        return response()->json([
            "data" => $data
        ]);
    }
}
