<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BnnkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TatRegisterController;
use App\Http\Controllers\TatRegisterImportController;
use App\Http\Controllers\TatRegisterPhotoController;
use App\Http\Controllers\TatRegisterReportController;
use App\Http\Controllers\TatRegisterSearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
Route::middleware('guest')->group(function () { Route::get('login',[LoginController::class,'showLoginForm'])->name('login'); Route::post('login',[LoginController::class,'login']); });
Route::middleware('auth')->group(function () {
 Route::post('logout',[LoginController::class,'logout'])->name('logout');
 Route::get('/',fn()=>redirect()->route('dashboard'));
 Route::get('dashboard',DashboardController::class)->name('dashboard');
 Route::get('profile',[ProfileController::class,'edit'])->name('profile.edit');
 Route::put('profile',[ProfileController::class,'update'])->name('profile.update');
 Route::get('profile/password',[PasswordController::class,'edit'])->name('password.edit');
 Route::put('profile/password',[PasswordController::class,'update'])->name('password.update');
 Route::get('laporan/register-tat',[TatRegisterReportController::class,'index'])->name('reports.tat.index');
 Route::get('laporan/register-tat/export/excel',[TatRegisterReportController::class,'exportExcel'])->name('reports.tat.export.excel');
 Route::get('laporan/register-tat/export/pdf',[TatRegisterReportController::class,'exportPdf'])->name('reports.tat.export.pdf');
 Route::middleware('role:bnnp')->group(function () {
  Route::resource('users',UserController::class)->only(['index','create','store','edit','update']);
  Route::post('bnnks',[BnnkController::class,'store'])->name('bnnks.store');
  Route::patch('users/{user}/status',[UserController::class,'updateStatus'])->name('users.status');
  Route::get('audit-logs',[AuditLogController::class,'index'])->name('audit-logs.index');
  Route::get('audit-logs/{auditLog}',[AuditLogController::class,'show'])->name('audit-logs.show');
 });
 Route::get('register-tat/arsip',[TatRegisterController::class,'archive'])->name('tat-registers.archive');
 Route::post('register-tat/{tatRegisterId}/restore',[TatRegisterController::class,'restore'])->name('tat-registers.restore');
 Route::get('register-tat/draft',[TatRegisterController::class,'drafts'])->name('tat-registers.drafts');
 Route::get('register-tat/search',TatRegisterSearchController::class)->name('tat-registers.search');
 Route::middleware('role:bnnk')->group(function () {
  Route::get('register-tat/import',[TatRegisterImportController::class,'index'])->name('tat-registers.import.index');
  Route::get('register-tat/import/template',[TatRegisterImportController::class,'downloadTemplate'])->name('tat-registers.import.template');
  Route::post('register-tat/import/validate',[TatRegisterImportController::class,'validateFile'])->name('tat-registers.import.validate');
  Route::get('register-tat/import/preview/{token}',[TatRegisterImportController::class,'preview'])->name('tat-registers.import.preview');
  Route::post('register-tat/import/confirm/{token}',[TatRegisterImportController::class,'confirm'])->name('tat-registers.import.confirm');
  Route::post('register-tat/import/cancel/{token}',[TatRegisterImportController::class,'cancel'])->name('tat-registers.import.cancel');
 });
 Route::get('register-tat/{tatRegister}/photo',TatRegisterPhotoController::class)->name('tat-registers.photo');
 Route::resource('register-tat',TatRegisterController::class)->parameters(['register-tat'=>'tatRegister'])->names('tat-registers');
});

