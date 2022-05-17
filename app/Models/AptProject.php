<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AptProject extends Model
{
    protected $table =  'Apt_Projects';
    // const CREATED_AT = 'EntryDate';
    // const UPDATED_AT = 'UpdateDate';
    public $timestamps = false;
    protected $primaryKey = 'RowID';

    

}
