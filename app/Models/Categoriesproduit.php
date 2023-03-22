<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoriesproduit extends Model
{
    use HasFactory;
     protected $primaryKey = 'id';
    protected $fillable = [
        'displayName',
        'fiche_id',
        'post_id',
       
    ];
        public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Categorieproduit";
    }
}
