<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserApp extends Model
{
    protected $table =  'U_App_Users';
    // const CREATED_AT = 'EntryDate';
    // const UPDATED_AT = 'UpdateDate';
    // protected $primaryKey = 'RowID';
    protected $primaryKey = 'EmployeeCode';

    

}