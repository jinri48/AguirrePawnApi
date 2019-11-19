<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// use Sofa\Eloquence\Eloquence; // base trait
// use Sofa\Eloquence\Mappable; // extension trait
// use Sofa\Eloquence\Mutable; // extension trait

class RecordRequest extends Model
{
    // use Eloquence, Mappable, Mutable;
    protected $table    = 'Requests';
    public $timestamps  = false;

    protected $fillable = [
        'status'
    ];
   
    // protected $maps = [  
    //     '_id'             => 'REQUESTID',
    //     'date'            => 'NUMBER',
    //     'time'            => 'TIME',
    //     'account_id'      => 'ACCOUNTID',
    //     'account_name'    => 'ACCOUNTNAME',
    //     'username'        => 'USERNAME',
    //     'status'            => 'STATUS'
    // ];  

}
