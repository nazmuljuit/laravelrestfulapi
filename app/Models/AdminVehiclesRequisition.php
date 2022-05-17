<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminVehiclesRequisition extends Model
{
    protected $table =  'Admin_Vehicles_Requisition';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowId';

    Protected $gurded = [];



}