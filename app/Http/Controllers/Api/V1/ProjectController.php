<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Project\ProjectIndexRequest;
use App\Http\Requests\V1\Project\ProjectStoreRequest;
use App\Http\Requests\V1\Project\ProjectUpdateRequest;
use App\Http\Resources\V1\Project\ProjectCollection;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillableRateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Project $project = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($project !== null && $project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project does not belong to organization');
        }
    }

    /**
     * Get projects visible to the current user
     *
     * @return ProjectCollection<ProjectResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getProjects
     */
    public function index(Organization $organization, ProjectIndexRequest $request): ProjectCollection
    {
        $this->checkPermission($organization, 'projects:view');
        $canViewAllProjects = $this->hasPermission($organization, 'projects:view:all');
        $user = $this->user();
        $team = User::with('groups')->where('id', '=', Auth::user()->id)->first();

        $projectsQuery = Project::with(['groups', 'client'])->orderBy('client_id')
            ->whereBelongsTo($organization, 'organization');

        $ownerId = optional($organization->owner()->first())->id;
        $userId = auth()->id();
        if ($this->member($organization)->role === Role::Admin->value) {
            Log::info("admin");
        } else if ($ownerId !== $userId) {
            $groupIds = $team->groups->pluck('id');
            if ($groupIds->isNotEmpty()) {
                $projectIDs = $projectsQuery->whereHas(
                    'groups',
                    fn($query) =>
                    $query->whereIn('id', $groupIds)
                )->with('tasks')->orderBy('name')->pluck('id');
                $projectsQuery = $projectsQuery->whereIn('id', $projectIDs); // ✅ correct
            } else {
                // User has no groups, return empty result
                $projectsQuery->whereRaw('1 = 0');
            }
        }
        $projectsQuery->orderByRaw("
    CASE 
        WHEN name IS NOT NULL AND name ~ '^[0-9]+' 
            THEN CAST((regexp_match(name, '^[0-9]+'))[1] AS INTEGER)
        ELSE NULL
    END
")->orderBy('name');


        // if (!$canViewAllProjects) {
        //     $projectsQuery->visibleByEmployee($user);
        // }
        $filterArchived = $request->getFilterArchived();
        if ($filterArchived === 'true') {
            $projectsQuery->whereNotNull('archived_at');
        } elseif ($filterArchived === 'false') {
            $projectsQuery->whereNull('archived_at');
        }

        $projects = $projectsQuery->paginate(config('app.pagination_per_page_default'));
        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;

        return new ProjectCollection($projects, $showBillableRate);
    }

    /**
     * Get project
     *
     * @throws AuthorizationException
     *
     * @operationId getProject
     */
    public function show(Organization $organization, Project $project): JsonResource
    {
        $this->checkPermission($organization, 'projects:view', $project);

        // Note: There is currently no need to check if a user is a member of the project,
        // since this is only relevant for users with the role "employee" and they can not access this endpoint.
        $projects = $project->load('organization');

        // Group by client name and apply natural sort on names


        return new ProjectResource($projects, true);
    }

    /**
     * Create project
     *
     * @throws AuthorizationException
     *
     * @operationId createProject
     */
    public function store(Organization $organization, ProjectStoreRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'projects:create');
        $project = new Project;
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        $project->billable_rate = $request->getBillableRate();
        $project->client_id = $request->input('client_id');
        $project->is_public = $request->getIsPublic();
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $project->estimated_time = $request->getEstimatedTime();
        }
        $project->organization()->associate($organization);
        $project->save();

        return new ProjectResource($project, true);
    }

    /**
     * Update project
     *
     * @throws AuthorizationException
     *
     * @operationId updateProject
     */
    public function update(Organization $organization, Project $project, ProjectUpdateRequest $request, BillableRateService $billableRateService): JsonResource
    {
        $this->checkPermission($organization, 'projects:update', $project);
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        if ($request->has('is_archived')) {
            $project->archived_at = $request->getIsArchived() ? Carbon::now() : null;
        }
        if ($request->has('is_public')) {
            $project->is_public = $request->boolean('is_public');
        }
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $project->estimated_time = $request->getEstimatedTime();
        }
        $oldBillableRate = $project->billable_rate;
        $clientIdChanged = false;
        $project->billable_rate = $request->getBillableRate();
        if ($project->client_id !== $request->input('client_id')) {
            $project->client_id = $request->input('client_id');
            $clientIdChanged = true;
        }
        $project->save();

        if ($oldBillableRate !== $request->getBillableRate()) {
            $billableRateService->updateTimeEntriesBillableRateForProject($project);
        }
        if ($clientIdChanged) {
            TimeEntry::query()
                ->whereBelongsTo($organization, 'organization')
                ->whereBelongsTo($project, 'project')
                ->update(['client_id' => $project->client_id]);
        }

        return new ProjectResource($project, true);
    }

    /**
     * Delete project
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteProject
     */
    public function destroy(Organization $organization, Project $project): JsonResponse
    {
        $this->checkPermission($organization, 'projects:delete', $project);

        if ($project->tasks()->exists()) {
            throw new EntityStillInUseApiException('project', 'task');
        }
        if ($project->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('project', 'time_entry');
        }

        DB::transaction(function () use (&$project): void {
            $project->members->each(function (ProjectMember $member): void {
                $member->delete();
            });

            $project->delete();
        });

        return response()
            ->json(null, 204);
    }
}
