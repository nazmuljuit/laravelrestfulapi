<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\EmpLeave;
class AdminVehiclesAssignmentLog extends Model
{
    protected $table =  'Admin_Vehicles_Assignment_Log';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';



}