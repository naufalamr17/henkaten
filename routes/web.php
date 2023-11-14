<?php

use App\Models\Skill;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\skillController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['guest'])->group(function (){
    Route::get('/', function () {
        return view('pages.auth.login');
    });

    Route::get('/login', [LoginController::class, 'index'])->name('login.index');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');

    Route::get('/register', [RegisterController::class, 'index'])->name('register.index');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

});

Route::middleware(['auth'])->group(function () {
    // default routes
    Route::get('/', [DashboardController::class, 'index']);
    
    // dashboard
    Route::get('/lineDashboard', [DashboardController::class, 'indexLine'])->name('dashboard.indexLine');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::prefix('dashboard')->group(function(){
        Route::get('{lineId}', [DashboardController::class, 'dashboardLine'])->name('dashboard.line');
        Route::get('selectTheme/{theme}', [DashboardController::class, 'selectTheme'])->name('dashboard.theme');
        Route::get('selectFirstPic/{id}', [DashboardController::class, 'selectFirstPic'])->name('dashboard.firstPic');
        Route::get('selectSecondPic/{id}', [DashboardController::class, 'selectSecondPic'])->name('dashboard.SecondPic');

        Route::get('storeHenkaten/{table}/{status}/{lineId}/{pic?}/{problem?}/{description?}', [DashboardController::class, 'storeHenkaten'])->name('dashboard.storeHenkaten');
        Route::post('troubleshootHenkaten', [DashboardController::class, 'troubleshootHenkaten'])->name('dashboard.troubleshootHenkaten');
    });

    // employees 
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::prefix('/employee')->group(function(){
        // regist employees
        Route::get('/register', [EmployeeController::class, 'employeeRegister'])->name('employeeRegister.index');  
        Route::post('/store', [EmployeeController::class, 'employeeStore'])->name('employee.store');  

        // planning employees
        Route::get('/planning', [EmployeeController::class, 'employeePlanning'])->name('employeePlanning.index');  
        Route::post('/planning/store', [EmployeeController::class, 'employeePlanningStore'])->name('employeePlanning.store');

        // get employee skill from employee
        Route::get('/getSkillEmp', [EmployeeController::class, 'getSkillEmp'])->name('employee.getSkillEmp');  
        Route::get('/getSkillPos', [EmployeeController::class, 'getSkillPos'])->name('employee.getSkillPos');  
        
        // edit employees
        Route::get('/{id}/edit', [EmployeeController::class, 'employeeEdit'])->name('editEmployee');
        Route::put('/{id}/update', [EmployeeController::class, 'employeeUpdate'])->name('employee.update');
    });

    // regist employee skill
    Route::get('/skill', [skillController::class, 'index'])->name('skill.index');
    Route::prefix('/skill')->group(function () {
        Route::post('/regist', [skillController::class, 'regist'])->name('skill.regist');
        Route::get('/minimum', [skillController::class, 'minimumIndex'])->name('skill.minimum.index');
        Route::post('/minimumRegist', [skillController::class, 'minimumRegist'])->name('skill.minimum.regist');
    });

    Route::get('/mappingAllLine', function () {
        return view('welcome');
    }); 
    
    // logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout.auth');
});

