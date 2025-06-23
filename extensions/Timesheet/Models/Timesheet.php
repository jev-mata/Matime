<?php

namespace Extensions\Timesheet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Extensions\Timesheet\Database\Factories\TimesheetFactory;

class Timesheet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date_start',
        'date_end',
        'hours',
        'description',
        'status',
        'approved_by',
        'approved_at',
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    // protected static function newFactory(): TimesheetFactory
    // {
    //     // return TimesheetFactory::new();
    // }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
 

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
