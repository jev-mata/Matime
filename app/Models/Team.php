<?php

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    //

    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
    ];
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    } 
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

}
