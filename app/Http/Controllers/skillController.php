<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\Skill;
use App\Models\MinimumSkill;
use Illuminate\Http\Request;
use App\Models\EmployeeSkill;
use Illuminate\Support\Facades\DB;

class skillController extends Controller
{
    public function index()
    {

        $masterSkills = Skill::select('name', DB::raw('GROUP_CONCAT(level) as levels'))
            ->groupBy('name')
            ->get();

        return view('pages.website.registSkill', [
            'masterSkill' => $masterSkills
        ]);
    }

    public function minimumIndex()
    {
        return view('pages.website.minimumSkill', [
            'minimumSkills' => MinimumSkill::all(),
            'skills' => Skill::selectRaw('MAX(id) as id, name')->groupBy('name')->get(),
            'lines' => Line::all()
        ]);
    }

    public function regist(Request $request)
    {
        $skills = $request->input('repeater-group');

        try {
            DB::beginTransaction();

            foreach ($skills as $skill) {
                for ($i = 1; $i <= 5; $i++) {
                    Skill::create([
                        'name' => $skill['name'],
                        'level' => $i + 1
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Skill berhasil ditambah!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Skill gagal ditambah!');
        }
    }

    public function minimumRegist(Request $request)
    {
        $skills = [];
        $validatedData = $request->validate([
            'skill' => 'required',
            'pos' => 'required',
            'line' => 'required',
            'level' => 'required',
        ]);

        // get skill id
        for ($i = 0; $i < count($validatedData['skill']); $i++) {
            $skillId = Skill::select('id')
                ->where('name', $validatedData['skill'][$i])
                ->where('level', $validatedData['level'][$i])
                ->first();
            array_push($skills, $skillId->id);
        }

        try {
            DB::beginTransaction();

            for ($i = 0; $i < count($validatedData['skill']); $i++) {
                MinimumSkill::create([
                    'skill_id' => $skills[$i],
                    'pos' => $request->pos[$i],
                    'line_id' => $request->line[$i],
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Minimum skill berhasil ditambah!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function checkSkill(Request $request)
    {
        $line = $request->line;
        $pos = $request->pos;
        $skill = $request->skill;

        // check skill if skill is already registerd in minimum skill
        // get skill name in spesific line and pos
        $skills = MinimumSkill::with('skill')->where('line_id', $line)->where('pos', $pos)->get();
        if (!$skills) {
            return response()->json([
                'status' => 'success',
                'message' => 'skill bisa didaftarkan!'
            ], 200);
        }

        foreach ($skills as $item) {
            if ($item->skill->name == $skill) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'skill sudah terdaftar!'
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'skill bisa didaftarkan!'
                ], 200);
            }
        }
    }

    public function minimumRegistEdit($id)
    {
        $minimumSkill = MinimumSkill::find($id);
        $lines = Line::all();
        $skills = Skill::selectRaw('MAX(id) as id, name')->groupBy('name')->get();
        $skillName = Skill::all();

        return view('pages.website.editMinimumSkill', compact('minimumSkill', 'lines', 'skills', 'skillName'));
    }

    public function minimumRegistUpdate(Request $request, $id)
    {
        $skills = [];

        for ($i = 0; $i < count($request->skill); $i++) {
            $skillId = Skill::select('id')
                ->where('name', $request->skill[$i])
                ->where('level', $request->level[$i])
                ->first();
            array_push($skills, $skillId->id);
        }

        try {
            DB::beginTransaction();
            for ($i = 0; $i < count($request->skill); $i++) {
                MinimumSkill::where('id', $id)->update([
                    'skill_id' => $skills[$i],
                    'pos' => $request->pos[$i],
                    'line_id' => $request->line[$i],
                ]);
            }

            DB::commit();

            return redirect('skill/minimum')->with('success', 'Minimum skill berhasil diubah!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function destroy($id)
    {
        if (request()->isMethod('delete')) {
            $employee = MinimumSkill::findOrFail($id);
            $employee->delete();

            return redirect('/employee')->with('success', 'Minimum Skill deleted successfully!');
        } else {
            // Handle unsupported methods
            return response()->json(['error' => 'Method not allowed'], 405);
        }
    }

    public function destroySkill($name)
    {
        if (request()->isMethod('delete')) {
            $employee = Skill::where('name', $name);
            // dd($employee);
            $employee->delete();

            return redirect('/employee')->with('success', 'Skill deleted successfully!');
        } else {
            // Handle unsupported methods
            return response()->json(['error' => 'Method not allowed'], 405);
        }
    }
}
