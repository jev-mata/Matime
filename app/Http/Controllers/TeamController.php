<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\ProjectController;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Teams;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    //
    public function index(Organization $organization)
    {
        $currentOrg = User::with('currentOrganization')->where('id', '=', Auth::user()->id)->get()->first();
        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();
        return Teams::with('projects')->where('organization_id', $org->id)->get();
    }
    public function show($projectid)
    {
        $currentOrg = User::with('currentOrganization')
            ->where('id', Auth::id())
            ->first();

        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();

        $teams = Teams::with('projects')
            ->where('organization_id', $org->id)
            ->whereDoesntHave('projects', function ($query) use ($projectid) {
                $query->where('id', $projectid);
            })
            ->get();


        return response()->json([
            'team' => $teams,
        ]);
    }

    public function showowned($projectid)
    {
        $currentOrg = User::with('currentOrganization')
            ->where('id', Auth::id())
            ->first();

        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();

        $teams = Teams::with([
            'projects' => function ($query) use ($projectid) {
                $query->where('id', '=', $projectid);
            }
        ])
            ->where('organization_id', $org->id)
            ->whereHas('projects', function ($query) use ($projectid) {
                $query->where('id', '=', $projectid);
            })
            ->get();

        return response()->json([
            'team' => $teams,
        ]);
    }


    public function getOrg()
    {
        $currentOrg = User::with('currentOrganization')->where('id', '=', Auth::user()->id)->get()->first();
        $org = Organization::with('users')->whereId($currentOrg->currentOrganization->id)->get()->first();
        $projects = Project::with('groups')->get();



        $team = Teams::with(['users', 'projects'])->where('organization_id', '=', $currentOrg->currentOrganization->id)->get();


        $teamsWithManagers = Teams::with([
            'users' => function ($query) use ($currentOrg) {
                $query->whereHas('organizationMember', function ($q) use ($currentOrg) {
                    $q->where('organization_id', $currentOrg->currentOrganization->id)
                        ->where('role', 'manager');
                });
            }
        ])
            ->where('organization_id', $currentOrg->currentOrganization->id)
            ->get();


        Log::info($teamsWithManagers);
        return response()->json([
            'org_id' => $currentOrg->id,
            'org' => $org,
            'user' => $org->users,
            'projects' => $projects,
            'teams' => $team,
            'managers' => $teamsWithManagers,
        ]);
    }
    public function assignMembers(Request $request, Teams $team)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $team->users()->sync($request->user_ids);

        return response()->json(['success' => true]);
    }
    public function removeProject(Teams $team, Project $project)
    {

        $team->projects()->detach([$project->id]); // many-to-many attach

        return response()->json(['success' => true]);
    }
    public function removeMember(Teams $team, User $user)
    {

        $team->users()->detach($user->id);

        return response()->json(['success' => true]);
    }

    public function removeGroup(Teams $team)
    {
        $team->delete();
        session()->flash('message', 'Team successfully removed!');

        return response()->json(['success' => true]);
    }
    public function updateGroup(Teams $team, $name)
    {

        $team->name = $name;
        $team->save();
        session()->flash('message', 'Team successfully removed!');

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $currentOrg = User::with('currentOrganization')->where('id', '=', Auth::user()->id)->get()->first();
        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        return Teams::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'organization_id' => $org->id,
        ]);
    }

    public function assignProject(Request $request, Teams $team)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $team = Teams::findOrFail($team->id);
        $team->projects()->syncWithoutDetaching([$request->project_id]); // many-to-many attach 
        return response()->json(['success' => true]);
    }
    public function assignTeam2Project($teamId, $projectId)
    {
        $team = Teams::findOrFail($teamId);
        $team->projects()->syncWithoutDetaching([$projectId]); // many-to-many attach

        return response()->json(['success' => true]);
    }

}
