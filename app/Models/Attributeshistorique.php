<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attributeshistorique extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [
    'attributeId',
    'displayName',
    'values',
    'valueType',
    'repeatedEnumValue',
    'urlValues',
    'repeatedEnumValue',
    'urlValues',
    'state',
    'fiche_id',
    'user_id',
    'attribute_id',
    'groupDisplayName',

    ];

    protected $table = 'attributeshistoriques';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Attributehistorique";
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
