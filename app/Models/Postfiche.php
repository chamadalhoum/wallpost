<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postfiche extends Model
{
  use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
        'genre',
        'post_id',
        'fiche_id',
        'name',
        'localPostActions',
        'localPostViewsSearch'
        
        
    ];
}
