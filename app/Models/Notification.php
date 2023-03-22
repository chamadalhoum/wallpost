<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable = [
        'diffMask',
        'newobject',
        'oldobject',
        'state',
        'fiche_id',
        'user_id',
       
    ];
}
