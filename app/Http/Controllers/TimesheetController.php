<?php

namespace App\Http\Controllers;

use App\Mail\TimeEntryReminder;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
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
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function permission()
    {
        $curOrg = $this->currentOrganization();
        $memberRole = $this->member($curOrg);
        $isManager = $memberRole->role !== 'employee';

        // Redirect employees away from approval route
        if ($memberRole->role === 'employee') {
            return redirect()->route('dashboard');
        }

        $teamIds = Auth::user()->groups()->pluck('teams.id');

        // Start base query with eager-loaded user.groups and member
        $query = TimeEntry::with(['user.groups', 'member'])
            ->where('approval', 'submitted');

        // Filter based on manager’s teams
        if ($memberRole->role === 'manager') {
            $query = $query->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds));
        }

        $timesheets = $query->get();

        // Group and map entries
        $grouped = $timesheets
            ->groupBy(function ($entry) {
                $date = Carbon::parse($entry->start);
                $half = $date->day <= 15 ? 1 : 2;
                return $date->format('Y-m') . '-' . $half;  // e.g. 2025-07-1 or 2025-07-2
            })
            ->map(function ($entriesByPeriod) {
                return $entriesByPeriod
                    ->groupBy('user_id')
                    ->map(function ($entriesByUser) {
                        $first = $entriesByUser->first();
                        $user = $first->user;
                        $member = $first->member;

                        $totalMinutes = $entriesByUser->sum(function ($e) {
                            return Carbon::parse($e->start)->diffInMinutes(Carbon::parse($e->end));
                        });

                        return [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'groups' => $user->groups,
                                'member' => $member ? ['id' => $member->id] : null,
                            ],
                            'totalHours' => floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm',
                        ];
                    })
                    ->values(); // Remove user_id keys
            });

        // Optional: Count total distinct users across all periods
        $totalUsers = $timesheets->pluck('user_id')->unique()->count();

        return response()->json([
            'isManager' => $isManager,
            'remain' => $totalUsers,     // Number of bi-monthly periods  
        ]);
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

    public function withdraw(Request $request)
    {
        $ids = $request->input('timeEntries'); // or $request->get('ids'); 

        // Optional validation
        if (!is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        TimeEntry::whereIn('id', $ids)->update([
            'approval' => 'submitted',
            'approved_by' => null,
        ]);

    }
 

    public function remind(Request $request)
    {
        $ids = $request->input('timeEntries');

        if (!is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        // Fetch all related users for the given time entry IDs
        $entries = TimeEntry::with('user')
            ->whereIn('id', $ids)
            ->get();

        // Extract user emails, filter nulls, and remove duplicates
        $emails = $entries
            ->pluck('user.email')       // get user emails
            ->filter()                  // remove nulls
            ->unique()                  // remove duplicates
            ->values();                 // reindex (optional)

        if ($emails->isEmpty()) {
            return response()->json(['error' => 'No valid user emails found'], 404);
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(new TimeEntryReminder(route('time')));
        }

        return response()->json(['message' => 'Reminder emails sent to ' . $emails->count() . ' user(s)']);
    }


    public function approve(Request $request)
    {

        $ids = $request->input('timeEntries'); // expect array of UUIDs
        $timeEntry = TimeEntry::whereIn('id', $ids)->update(['approval' => 'approved']);

        return response()->json([
            'updated' => count($ids),
            'status' => 'ok',
        ]);
    }
    public function reject(Request $request)
    {

        $ids = $request->input('timeEntries'); // expect array of UUIDs
        $timeEntry = TimeEntry::whereIn('id', $ids)->update(['approval' => 'rejected']);

        return response()->json([
            'updated' => count($ids),
            'status' => 'ok',
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */

    /**
     * Show the specified resource.
     */
    public function approval(Request $request)
    {
        $curOrg = $this->currentOrganization();
        $memberRole = $this->member($curOrg);

        if ($memberRole->role === 'employee') {
            return redirect()->route('dashboard');
        }

        $query = TimeEntry::with(['user.groups', 'member']);

        if ($memberRole->role === 'manager') {
            $teamIds = Auth::user()->groups()->pluck('teams.id');
            $query->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds));
        }

        $submitted = (clone $query)->where('approval', 'submitted')->get();
        $unsubmitted = (clone $query)->where('approval', 'unsubmitted')->get();
        $approved = (clone $query)->where('approval', 'approved')->get();

        return Inertia::render('Timesheet/Index', [
            'timesheets' => $submitted,
            'grouped' => $this->groupTimesheets($submitted),
            'unsubmitted_timesheets' => $unsubmitted,
            'unsubmitted_grouped' => $this->groupTimesheets($unsubmitted),
            'archive_timesheets' => $approved,
            'archive_grouped' => $this->groupTimesheets($approved),
        ]);
    }

    private function groupTimesheets($timesheets)
    {
        return $timesheets
            ->groupBy(function ($t) {
                $d = Carbon::parse($t->start);
                $half = $d->day <= 15 ? 1 : 16;
                return $d->format('Y-m') . '-' . $half;
            })
            ->map(function ($entriesByPeriod) {
                return $entriesByPeriod
                    ->groupBy('user_id')
                    ->map(function ($entriesByUser) {
                        $first = $entriesByUser->first();
                        $user = $first->user;
                        $member = $first->member;

                        $totalMinutes = $entriesByUser->sum(function ($e) {
                            return Carbon::parse($e->start)->diffInMinutes(Carbon::parse($e->end));
                        });

                        return [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'groups' => $user->groups,
                                'member' => $member ? ['id' => $member->id] : null,
                            ],
                            'totalHours' => floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm',
                        ];
                    })->values();
            });
    }

    public function ApprovalOverview(Request $request)
    {
        $user = Member::with('user')->find($request->input('user_id'));

        if (!$user) {
            return redirect()->route('dashboard');
        }
        $start = Carbon::parse($request->input('date_start'))->startOfDay();
        $end = Carbon::parse($request->input('date_end'))->endOfDay();
        $curOrg = $this->currentOrganization();

        $projects = Project::where('organization_id', $curOrg->id)->get();
        $tags = Tag::where('organization_id', $curOrg->id)->get();
        $clients = Client::where('organization_id', $curOrg->id)->get();

        $timeEntriesQuery = TimeEntry::where('organization_id', $curOrg->id)
            ->where('member_id', $user->id)
            ->whereBetween('start', [$start, $end])
            ->orderBy('start');
        if (!$timeEntriesQuery->exists()) {
            return redirect()->route('approval.index');
        }

        return Inertia::render('Timesheet/TimeReportOverview', [
            'userid' => $user->id,
            'period' => ['start' => $start, 'end' => $end],
            'name' => $user->user->name,
            'projects' => $projects,
            'timeEntries' => $timeEntriesQuery->get(),
            'tasks' => $projects, // you might want to actually load real tasks if needed
            'tags' => $tags,
            'clients' => $clients,
        ]);
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
