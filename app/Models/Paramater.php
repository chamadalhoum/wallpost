<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paramater extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable = [

        'name',
        'value',
    ];}
