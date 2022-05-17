<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AmgHRDetail extends Model
{
    protected $table =  'AMG_HR_Detail';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'EmployeeCode';


}