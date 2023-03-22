<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorieshistorique extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable=[
        'categorieId',
        'displayName',
        'type',
        'user_id',
        'fiche_id',
        'state',
        'categorie_id'
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Categoriehistorique";
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function catgorie()
    {
        return $this->belongsTo(Categorie::class);
    }
}
