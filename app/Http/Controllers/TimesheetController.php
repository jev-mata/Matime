<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Teams;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function permission()
    {
        $org = $this->currentOrganization();
        $mem = $this->member($org);
        $isManager = $mem->role !== 'employee';
        Log::info($mem);
        Log::info($isManager);
        $teamIds = Auth::user()->groups()->pluck('teams.id');

        $timesheets = TimeEntry::with('user.groups')
            ->where('approval', '=', 'submitted')
            ->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds))
            ->get();

        $grouped = $timesheets
            ->groupBy(function ($t) {
                $d = Carbon::parse($t->start);
                $half = $d->day <= 15 ? 1 : 2;
                return $d->format('Y-m') . '-' . $half;
            })
            ->map(function ($entriesByPeriod) {
                return $entriesByPeriod
                    ->groupBy('user_id')
                    ->map(function ($entriesByUser) {
                        $user = $entriesByUser->first()->user;
                        $totalMinutes = $entriesByUser->sum(function ($entry) {
                            return Carbon::parse($entry->start)->diffInMinutes(Carbon::parse($entry->end));
                        });
                        return [
                            'user' => $user->only(['id', 'name']) + ['groups' => $user->groups],
                            'totalHours' => floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm',
                        ];
                    })->values();
            });

        return response()->json(
            [
                "isManager" => $isManager,
                "remain" => count($grouped),
            ]
        );
    }

    public function getAllTimeEntries()
    {

        $curOrg = $this->currentOrganization();
        $entries = TimeEntry::where('user_id', '=', Auth::user()->id)->where('organization_id', '=', $curOrg->id)->get();
        Log::info($entries);
        return response()->json($entries);
    }
    public function destroy(Request $request)
    {

    }

    public function Submit(Request $request)
    {
        $ids = $request->input('ids'); // or $request->get('ids');

        // Optional validation
        if (!is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        TimeEntry::whereIn('id', $ids)->update([
            'approval' => 'submitted',
        ]);

    }

    public function Unsubmit(Request $request)
    {
        $ids = $request->input('ids'); // or $request->get('ids');

        // Optional validation
        if (!is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        TimeEntry::whereIn('id', $ids)->update([
            'approval' => 'unsubmitted',
            'approved_by' => null,
        ]);

    }


    public function reject($id)
    {
    }


    /**
     * Store a newly created resource in storage.
     */

    /**
     * Show the specified resource.
     */

    public function approval(Request $request)
    {
        $teamIds = Auth::user()->groups()->pluck('teams.id');
        $timesheets = TimeEntry::with(['user.groups', 'member'])          // eager‑load member
            ->where('approval', 'submitted')
            ->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds))
            ->get();

        $grouped = $timesheets
            ->groupBy(function ($t) {
                $d = Carbon::parse($t->start);
                $half = $d->day <= 15 ? 1 : 2;           // 1‑15 ⇒ 1, 16‑EOM ⇒ 2
                return $d->format('Y-m') . '-' . $half;  // e.g. 2025‑07‑2
            })
            ->map(function ($entriesByPeriod) {
                return $entriesByPeriod
                    ->groupBy('user_id')
                    ->map(function ($entriesByUser) {
                        $first = $entriesByUser->first();  // any row in this bucket
                        $user = $first->user;
                        $member = $first->member;           // ⬅︎ comes from ->with('member')
        
                        $totalMinutes = $entriesByUser->sum(
                            fn($e) =>
                            Carbon::parse($e->start)->diffInMinutes(Carbon::parse($e->end))
                        );

                        return [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'groups' => $user->groups,
                                'member' => $member ? ['id' => $member->id] : null, // ⬅︎ new
                            ],
                            'totalHours' => floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm',
                        ];
                    })
                    ->values();
            });


        return Inertia::render('Timesheet/Index', [
            'timesheets' => $grouped,
        ]);
    }

    public function ApprovalOverview(Request $request)
    {
        $user = Member::where('id', '=', $request->input('user_id'))->first();
        if ($user) {

            return Inertia::render('Timesheet/TimeReportOverview', [
                'userid' => $request->input('user_id'),
                'period' => ['start' => '', 'end' => ''],
            ]);
        } else {
            return redirect()->route('dashboard');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('timesheet::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
}
