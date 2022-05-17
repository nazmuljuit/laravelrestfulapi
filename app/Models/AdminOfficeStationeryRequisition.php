<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\AdminOfficeStationeryRequisitionDetails;
class AdminOfficeStationeryRequisition extends Model
{
    protected $table =  'Admin_OfficeStationery_Requisition';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';

    public function stationery_details()
    {
        return $this->belongsTo(AdminOfficeStationeryRequisitionDetails::class, 'RefReqID', 'RowID');
    }



}