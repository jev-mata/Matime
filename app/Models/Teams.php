<?php

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    //

    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
    ];
    protected $table = "teams";
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id');
    }


    public function projects()
    {
        return $this->belongsToMany(
            Project::class,      // related
            'team_project',      // pivot table
            'team_id',           // FK on pivot pointing to this model
            'projects_id'         // FK on pivot pointing to related model
        )->withTimestamps();
    }

}
