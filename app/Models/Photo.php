<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Photo extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $primaryKey = 'id';
    protected $fillable = [
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
        'avertir',
        'messageAvertir',
        'dateAvertir',
        'userAvertir',
        'signials',
        'signial_date',
        'fiche_id',
        'user_id',
        'created_at',
        'updated_at',
    ];
    protected static $logName = 'Photo';
    protected static $logAttributes = ['id',
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
                'avertir',
        'messageAvertir',
        'dateAvertir',
        'userAvertir',
        'signials',
        'signial_date',
    'fiche_id',
    'user_id',
         'created_at',
        'updated_at', ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Photo";
    }

    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photohistories()
    {
        return $this->hasMany(Photohistorie::class);
    }
}
