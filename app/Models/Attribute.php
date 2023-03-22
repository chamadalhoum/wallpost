<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Attribute extends Model
{
    use HasFactory;
    protected $fillable = [
'displayName',
        'attributeId',
        'values',
        'valueType',
        'repeatedEnumValue',
        'urlValues',
        'groupDisplayName',
        'user_id',
        'fiche_id',
        "state"

    ];
    use  LogsActivity;
    protected static $logName = 'Attribute';
    protected static $logAttributes = ['id',
        'displayName',
        'attributeId',
        'values',
        'valueType',
        'repeatedEnumValue',
        'urlValues',
        'groupDisplayName',
        'user_id',
        'fiche_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Attribute";
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
