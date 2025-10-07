<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\TimeEntryReminder;
use App\Mail\TimeEntrySubmittionNotification;
use App\Models\Client;
use App\Models\Member;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Teams;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

        if ($memberRole->role === 'employee') {
            return redirect()->route('dashboard');
        }

        $teamIds = Auth::user()->groups()->pluck('teams.id');

        // Base query
        $query = TimeEntry::query()
            ->selectRaw("
            user_id,
            TO_CHAR(start, 'YYYY-MM') as year_month,
            CASE WHEN EXTRACT(DAY FROM start) <= 15 THEN 1 ELSE 2 END as half,
            SUM(EXTRACT(EPOCH FROM (\"end\" - start)) / 60) as totalMinutes
        ")
            ->where('approval', 'submitted')
            ->groupBy('user_id', 'year_month', 'half')
            ->with('user:id,name')
            ->with('member:id,user_id,role');

        // Role filters
        if ($memberRole->role === 'owner') {
            $query->whereHas('member', fn ($q) => $q->whereIn('role', ['manager', 'admin', 'employee'])
            );
        } elseif ($memberRole->role === 'admin') {
            $query->whereHas('member', fn ($q) => $q->whereIn('role', ['manager', 'employee'])
            )->whereHas('user.groups', fn ($q) => $q->whereIn('teams.id', $teamIds)
            );
        } elseif ($memberRole->role === 'manager') {
            $query->whereHas('member', fn ($q) => $q->whereIn('role', ['employee', 'intern'])
            )->whereHas('user.groups', fn ($q) => $q->whereIn('teams.id', $teamIds)
            );
        }

        $timesheets = $query->get();

        // Transform
        $grouped = $timesheets->groupBy(fn ($row) => $row->year_month.'-'.$row->half)
            ->map(function ($entries) {
                return $entries->map(function ($row) {
                    return [
                        'user' => [
                            'id' => $row->user->id,
                            'name' => $row->user->name,
                            'groups' => $row->user->groups ?? [],
                            'member' => $row->member ? ['id' => $row->member->id] : null,
                        ],
                        'totalHours' => floor($row->totalMinutes / 60).'h '.($row->totalMinutes % 60).'m',
                    ];
                })->values();
            });

        return response()->json([
            'isManager' => $isManager,
            'remain' => $timesheets->pluck('user_id')->unique()->count(),
            'data' => $grouped,
        ]);
    }

    public function getAllTimeEntries()
    {

        $curOrg = $this->currentOrganization();
        $entries = TimeEntry::where('user_id', '=', Auth::user()->id)->where('organization_id', '=', $curOrg->id)->get();
        Log::info($entries);

        return response()->json($entries);
    }

    public function destroy(Request $request): void {}

    public function Submit(Request $request)
    {
        $ids = $request->input('ids'); // or $request->get('ids');
        $period = $request->input('period'); // or $request->get('ids');

        $curOrg = $this->currentOrganization();
        $memb = $this->member($curOrg)->whereIn('role', [Role::Manager, Role::Admin])->pluck('user_id');
        // Optional validation
        if (! is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        $entries = TimeEntry::with('user')->whereIn('id', $ids);

        $teamIds = Auth::user()->groups()->pluck('teams.id');
        $users = User::with('organizations')
            ->where(function ($query) use ($teamIds): void {
                // 1) Users in same groups AND Manager/Admin
                $query->whereHas('groups', function ($q) use ($teamIds): void {
                    $q->whereIn('teams.id', $teamIds);
                })->whereHas('organizations', function ($q): void {
                    $q->whereIn('role', [Role::Manager, Role::Admin]);
                });

                // 2) OR Users with role Owner (anywhere)
                $query->orWhereHas('organizations', function ($q): void {
                    $q->where('role', Role::Owner);
                });
            })
            ->get();

        $names = $entries->get()
            ->pluck('user.name')     // get user names
            ->filter()               // remove nulls
            ->unique()               // remove duplicates
            ->values()               // reindex
            ->implode(', ');         // convert to a comma-separated string

        $entries->update([
            'approval' => 'submitted',
        ]);
        $emails = $users->pluck('email');                 // reindex (optional)
        foreach ($emails as $email) {
            Mail::to($email)->send(
                new TimeEntrySubmittionNotification(
                    route('approval.index'),
                    $period,
                    Auth::user()->name,
                    $names,
                    'Submit'
                )
            );
        }
    }

    public function Unsubmit(Request $request)
    {
        $ids = $request->input('ids'); // or $request->get('ids');

        $period = $request->input('period', 'All'); // or $request->get('ids');

        // Optional validation
        if (! is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        $entries = TimeEntry::with('user')->whereIn('id', $ids);
        $names = $entries->get()
            ->pluck('user.name')     // get user names
            ->filter()               // remove nulls
            ->unique()               // remove duplicates
            ->values()               // reindex
            ->implode(', ');         // convert to a comma-separated string

        $teamIds = Auth::user()->groups()->pluck('teams.id');
        $users = User::with('organizations')
            ->where(function ($query) use ($teamIds): void {
                // 1) Users in same groups AND Manager/Admin
                $query->whereHas('groups', function ($q) use ($teamIds): void {
                    $q->whereIn('teams.id', $teamIds);
                })->whereHas('organizations', function ($q): void {
                    $q->whereIn('role', [Role::Manager, Role::Admin]);
                });

                // 2) OR Users with role Owner (anywhere)
                $query->orWhereHas('organizations', function ($q): void {
                    $q->where('role', Role::Owner);
                });
            })
            ->get();

        $entries->update([
            'approval' => 'unsubmitted',
            'approved_by' => null,
        ]);

        $emails = $users->pluck('email');                 // reindex (optional)
        foreach ($emails as $email) {
            Mail::to($email)->send(
                new TimeEntrySubmittionNotification(
                    route('approval.index'),
                    $period,
                    Auth::user()->name,
                    $names,
                    'Unsubmit'
                )
            );
        }

        return response()->json(['message' => 'Entries unsubmitted']);
    }

    public function withdraw(Request $request)
    {
        $ids = $request->input('timeEntries'); // or $request->get('ids');

        $period = $request->input('period', 'All'); // or $request->get('ids');
        $curOrg = $this->currentOrganization();
        $memb = $this->member($curOrg)->whereIn('role', [Role::Manager, Role::Admin])->pluck('user_id');
        $user = User::whereIn('id', $memb)->get();
        // Optional validation
        if (! is_array($ids)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        $entries = TimeEntry::with('user')->whereIn('id', $ids);
        $users = $entries->get()
            ->pluck('user')       // get user emails
            ->filter()                  // remove nulls
            ->unique('id')                  // remove duplicates
            ->values();                 // reindex (optional)

        if ($users->isEmpty()) {
            return response()->json(['error' => 'No valid user emails found'], 404);
        }              // reindex (optional)
        $entries->update([
            'approval' => 'submitted',
            'approved_by' => null,
        ]);               // reindex (optional)
        foreach ($users as $user) {
            Mail::to($user->email)->send(
                new TimeEntrySubmittionNotification(
                    route('approval.approve'),
                    $period,
                    Auth::user()->name,
                    $user->name,
                    'Withdraw'
                )
            );
        }

        return response()->json(['message' => 'Entries widrawn'.$user->count().' user(s)']);
    }

    public function remind(Request $request)
    {
        $ids = $request->input('timeEntries');

        if (! is_array($ids)) {
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

        return response()->json(['message' => 'Reminder emails sent to '.$emails->count().' user(s)']);
    }

    public function approve(Request $request)
    {

        $ids = $request->input('timeEntries'); // expect array of UUIDs
        $period = $request->input('period', 'All'); // or $request->get('ids');
        $timeEntry = TimeEntry::with('user')->whereIn('id', $ids);
        // Extract user emails, filter nulls, and remove duplicates

        $users = $timeEntry->get()
            ->pluck('user')        // get the User models
            ->filter()             // remove nulls (in case some time entries don't have users)
            ->unique('id')         // remove duplicates based on user ID
            ->values();            // reindex the collection (optional)
        if ($users->isEmpty()) {
            return response()->json(['error' => 'No valid user emails found'], 404);
        }
        // reindex (optional)
        $timeEntry->update(['approval' => 'approved']);
        foreach ($users as $user) {
            Mail::to($user->email)->send(
                new TimeEntrySubmittionNotification(
                    route('time'),
                    $period,
                    Auth::user()->name,
                    $user->name,
                    'Approved'
                )
            );
        }

        return response()->json([
            'updated' => count($ids),
            'status' => 'ok',
        ]);
    }

    public function reject(Request $request)
    {

        $ids = $request->input('timeEntries'); // expect array of UUIDs
        $period = $request->input('period', 'All'); // or $request->get('ids');
        $timeEntry = TimeEntry::with('user')->whereIn('id', $ids);

        $users = $timeEntry->get()
            ->pluck('user')        // get the User models
            ->filter()             // remove nulls (in case some time entries don't have users)
            ->unique('id')         // remove duplicates based on user ID
            ->values();            // reindex the collection (optional)

        if ($users->isEmpty()) {
            return response()->json(['error' => 'No valid user emails found'], 404);
        }
        // reindex (optional)

        $timeEntry->update(['approval' => 'rejected']);
        foreach ($users as $user) {
            Mail::to($user->email)->send(
                new TimeEntrySubmittionNotification(
                    route('time'),
                    $period,
                    Auth::user()->name,
                    $user->name,
                    'Rejected'
                )
            );
        }

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

        $query =null;

        $teamIds = Auth::user()->groups()->pluck('teams.id')->toArray();

        if ($memberRole->role === 'owner') {
            $query =TimeEntry::with(['user.groups:id,name', 'member:id,role,organization_id']); 
            // Optional: team filter if admins should only see their own teams
            // $query->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds));
        } elseif ($memberRole->role === 'admin') {
            $query =TimeEntry::with(['user.groups:id,name', 'member:id,role,organization_id'])->whereHas('member', fn ($q) => $q->whereIn('role', ['manager',  'employee'])
            )->whereHas('user.groups', fn ($q) => $q->whereIn('teams.id', $teamIds)
        );
            // Optional: team filter if admins should only see their own teams
            // $query->whereHas('user.groups', fn($q) => $q->whereIn('teams.id', $teamIds));
        } elseif ($memberRole->role === 'manager') {
            $query = TimeEntry::with(['user.groups:id,name', 'member:id,role,organization_id'])->whereHas('member', fn ($q) => $q->whereIn('role', ['employee', 'intern'])
            )->whereHas('user.groups', fn ($q) => $q->whereIn('teams.id', $teamIds)
            );
        }  
        $timesheets = $query
            ->whereIn('approval', ['submitted', 'unsubmitted', 'approved'])
            ->get()
            ->groupBy('approval');

        $submitted = $timesheets->get('submitted', collect());
        $unsubmitted = $timesheets->get('unsubmitted', collect());
        $approved = $timesheets->get('approved', collect());

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

                return $d->format('Y-m').'-'.$half;
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
                            'totalHours' => floor($totalMinutes / 60).'h '.($totalMinutes % 60).'m',
                        ];
                    })->values();
            });
    }

    public function ApprovalOverview(Request $request)
    {
        $user = Member::with('user')->find($request->input('user_id'));

        if (! $user) {
            return redirect()->route('dashboard');
        }
        $start = Carbon::parse($request->input('date_start'))->startOfDay();
        $end = Carbon::parse($request->input('date_end'))->endOfDay();
        $curOrg = $this->currentOrganization();

        $projects = Project::where('organization_id', $curOrg->id)->get();
        $tasks = Task::where('organization_id', $curOrg->id)->get();
        $tags = Tag::where('organization_id', $curOrg->id)->get();
        $clients = Client::where('organization_id', $curOrg->id)->get();

        $timeEntriesQuery = TimeEntry::where('organization_id', $curOrg->id)
            ->where('member_id', $user->id)
            ->whereBetween('start', [$start, $end])
            ->select('*')
            ->selectRaw('EXTRACT(EPOCH FROM ("time_entries"."end" - "time_entries"."start"))::int as duration')
            ->orderBy('start');
        if (! $timeEntriesQuery->exists()) {
            return redirect()->route('approval.index');
        }

        return Inertia::render('Timesheet/TimeReportOverview', [
            'userid' => $user->id,
            'period' => ['start' => $start, 'end' => $end],
            'name' => $user->user->name,
            'projects' => $projects,
            'timeEntries' => $timeEntriesQuery->get(),
            'tasks' => $tasks, // you might want to actually load real tasks if needed
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
    public function update(Request $request, $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
}
