<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeMovement extends Model
{
    protected $table =  'TA_Employeemovement';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    // protected $primaryKey = 'EmployeeCode';
    protected $primaryKey = 'RowID';
    protected $keyType = 'string';

}
