<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\ProjectController;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    //
    public function index(Organization $organization)
    {
        $currentOrg = User::with('currentOrganization')->where('id', '=', Auth::user()->id)->get()->first();
        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();
        return Team::with('projects')->where('organization_id', $org->id)->get();
    }
    public function show($projectid)
    {
        $currentOrg = User::with('currentOrganization')
            ->where('id', Auth::id())
            ->first();

        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();

        $teams = Team::with('projects')
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

        $teams = Team::with([
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
        $projects = Project::with('team')->get();

        $team = Team::with(['users', 'projects'])->where('organization_id', '=', $currentOrg->currentOrganization->id)->get();
        Log::info($team);
        return response()->json([
            'org_id' => $currentOrg->id,
            'org' => $org,
            'user' => $org->users,
            'projects' => $projects,
            'teams' => $team,
        ]);
    }
    public function assignMembers(Request $request, Team $team)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $team->users()->sync($request->user_ids);

        return response()->json(['success' => true]);
    }
    public function removeProject(Team $team, Project $project)
    {
        if ($project->team_id !== $team->id) {
            return response()->json(['error' => 'Not assigned to this team'], 400);
        }

        $project->team_id = null;
        $project->save();

        return response()->json(['success' => true]);
    }
    public function removeMember(Team $team, User $user)
    {
        $team->users()->detach($user->id);

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $currentOrg = User::with('currentOrganization')->where('id', '=', Auth::user()->id)->get()->first();
        $org = Organization::whereId($currentOrg->currentOrganization->id)->get()->first();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        return Team::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'organization_id' => $org->id,
        ]);
    }

    public function assignProject(Request $request, Team $team)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::find($request->project_id);
        $project->team_id = $team->id;
        $project->save();

        return response()->json(['success' => true]);
    }
    public function assignTeam2Project($teamid, $projectid)
    {

        $project = Project::find($projectid);
        $project->team_id = $teamid;
        $project->save();

        return response()->json(['success' => true]);
    }

}
