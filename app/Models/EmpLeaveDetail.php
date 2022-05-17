<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmpLeaveDetail extends Model
{
    protected $table =  'TA_EmpLeave_Detail';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'EmployeeCode';



}