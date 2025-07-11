<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProjectGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',  // replace with 'user_id' if you decide to tie to users
    ];

    /* --------------------------------------------------------------------
     | Relationships
     |-------------------------------------------------------------------*/

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Convenience accessor: returns a *collection* of the member’s projects
     * (pulled through the related team) – handy for read‑only use.
     */
    public function projects()
    {
        return $this->relationLoaded('team')
            ? optional($this->getRelation('team'))->projects ?? collect()
            : ($this->team ? $this->team->projects : collect());
    }

    /**
     * Query scope for eager‑loading the team **and** its projects in
     * a single round‑trip:
     *
     *     ProjectGroupMember::withProjects()->get();
     */
    public function scopeWithProjects(Builder $query): Builder
    {
        return $query->with('team.projects');
    }
}
