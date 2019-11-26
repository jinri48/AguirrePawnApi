<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecordResponse extends Model
{
    protected $table    = 'Responses';
    public $timestamps  = false;

    protected $primaryKey = 'RESPONSEID';


    
}
