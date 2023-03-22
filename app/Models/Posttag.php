<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Posttag extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
        'post_id',
        'tag_id'
    ];
    use  LogsActivity;
    protected static $logName = 'Posttag';
    protected static $logAttributes = ['id',
        'post_id',
        'tag_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Posttag";
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function post(){
        return $this->belongsTo(Post::class);
    }
    public function tag(){
        return $this->belongsTo(Tag::class);
    }
}
