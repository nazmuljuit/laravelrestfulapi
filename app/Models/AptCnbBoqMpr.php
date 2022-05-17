<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\AdminOfficeStationeryRequisitionDetails;
class AptCnbBoqMpr extends Model
{
    protected $table =  'APT_CNB_BOQ_MPR';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';




}