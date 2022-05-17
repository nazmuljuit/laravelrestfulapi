<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeMovementDetail extends Model
{
    protected $table =  'TA_EmployeeMovement_Detail';
    // const CREATED_AT = 'EntryDate';
    // const UPDATED_AT = 'UpdateDate';
    public $timestamps = false;
    protected $primaryKey = 'RefRowID';



}