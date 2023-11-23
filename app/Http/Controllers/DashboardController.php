<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Line;
use App\Models\Pivot;
use App\Models\Shift;
use App\Models\Theme;
use App\Models\Employee;
use App\Models\Henkaten;
use App\Models\PicActive;
use App\Models\HenkatenMan;
use Illuminate\Support\Str;
use App\Models\Troubleshoot;
use Illuminate\Http\Request;
use App\Models\EmployeeActive;
use App\Models\HenkatenMethod;
use App\Models\HenkatenMachine;
use App\Models\HenkatenMaterial;
use App\Models\HenkatenManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function index()
    {
        // get current pivot
        $current_date = Carbon::now()->toDateString();
        $pivot = Pivot::with('theme')
            ->with('firstPic')
            ->with('secondPic')
            ->where('active_date', $current_date)
            ->first();

        // get henkaten where status still active
        $activeProblem = Henkaten::where('is_done', '0')->get();

        // in this page we will get all line status
        return view('pages.website.dashboard', [
            'pivot' => $pivot,
            'themes' => Theme::all(),
            'employees' => Employee::select('id', 'name')
                ->whereIn('role', ['Leader', 'JP'])
                ->get(),
            'lines' => Line::all(),
            'histories' => $activeProblem
        ]);
    }
    
    public function indexLine()
    {
        // in this page we will get all line status
        return view('pages.website.lineDashboard', [
            'lines' => Line::all(),
        ]);
    }

    public function dashboardLine(Line $lineId)
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        // get all history
        $histories = Henkaten::with('troubleshoot')->where('line_id', $lineId->id)->get();
        
        // get man power at spesific line and range of time
        $activeEmployees = EmployeeActive::with('shift')
            ->with('employee')
            ->where('active_from', '<=', $currentDate)
            ->where('expired_at', '>=', $currentDate)
            ->where('line_id', $lineId->id)
            ->whereHas('shift', function ($query) use ($currentTime) {
                $query->where('time_start', '<=', $currentTime)
                    ->where('time_end', '>=', $currentTime);
            })
            ->get();

        // get active pic
        $activePic = PicActive::with('shift')
            ->with('employee')
            ->where('active_from', '<=', $currentDate)
            ->where('expired_at', '>=', $currentDate)
            ->where('line_id', $lineId->id)
            ->whereHas('shift', function ($query) use ($currentTime) {
                $query->where('time_start', '<=', $currentTime)
                    ->where('time_end', '>=', $currentTime);
            })
            ->first();

        // get man henkaten
        $manHenkaten = Troubleshoot::with(['henkaten.shift', 'manAfter', 'manBefore'])
            ->whereHas('henkaten', function ($query) use ($lineId, $currentTime) {
                $query->where('line_id', $lineId->id)
                    ->whereHas('shift', function ($subQuery) use ($currentTime) {
                        $subQuery->where('time_start', '<=', $currentTime)
                            ->where('time_end', '>=', $currentTime);
                    });
            })
            ->get();     
        
        return view('pages.website.line', [
            'line' => Line::findOrFail($lineId->id),
            'employees' => Employee::all(),
            'activeEmployees' => $activeEmployees,
            'activePic' => $activePic,
            'histories' => $histories,
            'manHenkaten' => $manHenkaten,
            'henkatenManagements' => HenkatenManagement::all()
        ]);
    }

    public function selectTheme(Request $request)
    {
        // get theme name
        $parts = explode("/", $request->path());
        $customTheme = explode("-", $parts[2]);

        $theme_name = Theme::select('name')->where('id', $parts[2])->first();

        // get current pivot
        $current_date = Carbon::now()->toDateString();
        $pivot = Pivot::where('active_date', $current_date)->first();
        if (!$pivot) {
            if (($theme_name == null)) {
                try {
                    DB::beginTransaction();
                    // insert new data if pivot table is empty
                    Pivot::create([
                        'custom_theme' => urldecode($customTheme[0]),
                        'active_date' => $current_date
                    ]);

                    DB::commit();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $th
                    ], 500);
                }
            } else {
                try {
                    DB::beginTransaction();

                    // insert new data if pivot table is empty
                    Pivot::create([
                        'theme_id' => $parts[2],
                        'active_date' => $current_date
                    ]);

                    DB::commit();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $th
                    ], 500);
                }
            }
        } else {
            if (($theme_name == null)) {
                try {
                    DB::beginTransaction();

                    // insert new data if pivot table is empty
                    $pivot->update([
                        'custom_theme' => urldecode($customTheme[0]),
                        'theme_id' => null
                    ]);

                    DB::commit();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $th
                    ], 500);
                }
            } else {
                try {
                    DB::beginTransaction();

                    // insert new data if pivot table is empty
                    $pivot->update([
                        'custom_theme' => null,
                        'theme_id' => $parts[2]
                    ]);

                    DB::commit();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $th
                    ], 500);
                }
            }
        }

        $custom_theme = Pivot::select('custom_theme')->where('custom_theme', urldecode($customTheme[0]))->first();

        $theme_name_final = '';
        if (isset($custom_theme)) {
            $decoded_custom_theme = $custom_theme->custom_theme;
            $theme_name_final = $decoded_custom_theme;
        } elseif (isset($theme_name)) {
            $theme_name_final = $theme_name->name;
        }

        // Mengembalikan respons JSON tanpa if-else
        return response()->json([
            'status' => 'success',
            'message' => 'Tema berhasil ditambahkan!',
            'theme_id' => $parts[2],
            'theme_name' => $theme_name_final,
        ], 200);
    }

    public function selectFirstPic($id)
    {
        $pic = $id;
        if ($pic == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pilih karyawan yang tersedia!'
            ]);
        }

        $existingPics = Pivot::where(function ($query) use ($pic) {
            $query->where('first_pic_id', $pic)
                ->orWhere('second_pic_id', $pic);
        })
            ->whereDate('active_date', '=', now()->toDateString()) // Menggunakan toDateString() untuk mendapatkan tanggal saja
            ->first();

        if ($existingPics) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan ini sudah menjadi PIC 1 atau PIC 2.'
            ]);
        }

        // get current pivot
        $current_date = Carbon::now()->toDateString();
        $pivot = Pivot::where('active_date', $current_date)->first();

        $employee = Employee::where('id', $pic)->first();
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'karyawan tidak terdaftar!'
            ], 404);
        }

        if (!$pivot) {
            try {
                DB::beginTransaction();

                // insert new data if pivot table is empty
                Pivot::create([
                    'first_pic_id' => $pic,
                    'active_date' => $current_date
                ]);

                DB::commit();

                // insert the value into session
                session(['selected_pic1' => $pic]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $th
                ], 500);
            }
        } else {
            try {
                DB::beginTransaction();

                $pivot->update([
                    'first_pic_id' => $pic
                ]);

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $th
                ], 500);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'PIC berhasil ditambahkan!',
            'name' => $employee->name,
            'photo' => $employee->photo,
            'role' => $employee->role,
            'npk' => $employee->npk
        ], 200);
    }

    public function selectSecondPic($id)
    {
        $pic = $id;
        if ($pic == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pilih karyawan yang tersedia!'
            ]);
        }

        $existingPics = Pivot::where(function ($query) use ($pic) {
            $query->where('first_pic_id', $pic)
                ->orWhere('second_pic_id', $pic);
        })
            ->whereDate('active_date', '=', now()->toDateString()) // Menggunakan toDateString() untuk mendapatkan tanggal saja
            ->first();

        if ($existingPics) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan ini sudah menjadi PIC 1 atau PIC 2.'
            ]);
        }

        // get current pivot
        $current_date = Carbon::now()->toDateString();
        $pivot = Pivot::where('active_date', $current_date)->first();

        $employee = Employee::where('id', $pic)->first();
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'karyawan tidak terdaftar!'
            ], 404);
        }

        if (!$pivot) {
            try {
                DB::beginTransaction();

                // insert new data if pivot table is empty
                Pivot::create([
                    'second_pic_id' => $pic,
                    'active_date' => $current_date
                ]);

                DB::commit();

                // insert the value into session
                session(['selected_pic2' => $pic]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $th
                ], 500);
            }
        } else {
            try {
                DB::beginTransaction();

                $pivot->update([
                    'second_pic_id' => $pic
                ]);

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $th
                ], 500);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'PIC berhasil ditambahkan!',
            'name' => $employee->name,
            'photo' => $employee->photo,
            'role' => $employee->role,
            'npk' => $employee->npk
        ], 200);
    }
}
