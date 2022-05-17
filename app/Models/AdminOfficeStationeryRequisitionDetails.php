<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminOfficeStationeryRequisitionDetails extends Model
{
    protected $table =  'Admin_OfficeStationery_Requisition_Details';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';




}