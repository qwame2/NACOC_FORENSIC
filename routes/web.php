<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// AUTHENTICATION LOGIN & REGISTRATION ROUTES
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/Forensic_Head', function () {
    return view('Forensic_Head.head');
});

Route::get('/Crime_Scene_Support', function () {
    return view('Crime_Scene_Support.head.head');
});

Route::get('/Crime_Scene_Support/head', function () {
    return view('Crime_Scene_Support.head.head');
});

Route::get('/Crime_Scene_Support/staff', function () {
    return view('Crime_Scene_Support.staff.staff');
});

// GENERAL DOCKET & EVENT ROUTES (ACCESSIBLE BY ANY UNIT)
Route::get('/arrest-person', function () {
    return view('Crime_Scene_Support.staff.events.arrest_person');
})->name('arrest_person');

Route::get('/cargo-parcel-interception', function () {
    return view('Crime_Scene_Support.staff.events.cargo_parcel_interception');
});

Route::get('/raid', function () {
    return view('Crime_Scene_Support.staff.events.raid');
});

Route::get('/search-warrant', function () {
    return view('Crime_Scene_Support.staff.events.search_warrant');
});

Route::get('/intel-led-ops', function () {
    return view('Crime_Scene_Support.staff.events.intel_led_ops');
});

Route::get('/transferred-case', function () {
    return view('Crime_Scene_Support.staff.events.transferred_case');
});

// CRIME SCENE SUPPORT DEDICATED EVENT PAGES (LEGACY ALIASES)
Route::get('/Crime_Scene_Support/staff/cargo-parcel-interception', function () {
    return view('Crime_Scene_Support.staff.events.cargo_parcel_interception');
});

Route::get('/Crime_Scene_Support/staff/raid', function () {
    return view('Crime_Scene_Support.staff.events.raid');
});

Route::get('/Crime_Scene_Support/staff/search-warrant', function () {
    return view('Crime_Scene_Support.staff.events.search_warrant');
});

Route::get('/Crime_Scene_Support/staff/intel-led-ops', function () {
    return view('Crime_Scene_Support.staff.events.intel_led_ops');
});

Route::get('/Crime_Scene_Support/staff/transferred-case', function () {
    return view('Crime_Scene_Support.staff.events.transferred_case');
});

Route::get('/Crime_Scene_Support/staff/arrest-person', function () {
    return view('Crime_Scene_Support.staff.events.arrest_person');
});

Route::get('/crime-scene-support', function () {
    return view('Crime_Scene_Support.head.head');
});

// MASTER CASE DIRECTORY LANDING PAGE
Route::get('/cases', function () {
    return view('cases.landing');
});

Route::get('/cases/landing', function () {
    return view('cases.landing');
});

Route::get('/Crime_Scene_Support/cases', function () {
    return view('cases.landing');
});