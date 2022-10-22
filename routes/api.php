<?php

use App\Http\Controllers\CoursesController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessagesController;
use Illuminate\Support\Facades\Route;


Route::get('/labs', [LabController::class, 'list']);
Route::get('/get-user-message', [MessagesController::class, 'GetMessage']);

# khóa học
Route::prefix('/course')->group(function () {
    # tham gia khóa học
    Route::get('/join/{id}', [CourseStudentController::class, 'addStudent_to_Course']);
    # thông tin khóa học
    Route::prefix('/{id}')->group(function () {
        Route::get('/', [CoursesController::class, 'getCourseById']);
        Route::get('/quiz', [QuizController::class, 'getQuizSubject']);
        Route::post('/quiz', [QuizController::class, 'saveQuizSubject']);
    });
});

# người dùng
Route::prefix('/users')->group(function () {
    Route::get('/get-me', [UserController::class, 'getMe']);
});

# cây thư mục
Route::prefix('/branch')->group(function () {
    Route::get('/', [BranchController::class, 'list_parent']);
    Route::get("{path}", [BranchController::class, 'list_child'])->where('path', '.+');
});



// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
