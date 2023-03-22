<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;
class Photohistorie extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
    'category',
    'name',
    'views',
    'file',
    'thumbnail',
    'format',
    'width',
    'height',
    'profileName',
    'profilePhotoUrl',
    'profileUrl',
    'takedownUrl',
    'fiche_id',
    'user_id',
    'created_at',
    'updated_at',
    'photo_id'
    ];
    use  LogsActivity;
    protected static $logName = 'Photohistory';
    protected static $logAttributes = ['id', 'category',
    'name',
    'views',
    'file',
    'thumbnail',
    'format',
    'width',
    'height',
    'profileName',
    'profilePhotoUrl',
    'profileUrl',
    'takedownUrl',
    'fiche_id',
    'user_id',
    'state',
    'created_at',
    'updated_at',
    'photo_id'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Photohistory";
    }
    public function photo(){
        return $this->belongsTo(Photo::class);
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
