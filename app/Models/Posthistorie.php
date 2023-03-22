<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Posthistorie extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey='id';
    protected $fillable =[
        'modif_type',
        'old_content',
        'new_content',
        'state',
        'post_id',
        'user_id',
    ];
    protected static $logName = 'Posthistorique';
    protected static $logAttributes = ['id',
        'modif_type',
        'old_content',
        'new_content',
        'state',
        'post_id',
        'user_id'
        ,'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Posthistorique";
    }
    public function post(){
        return $this->belongsTo(Post::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

}
