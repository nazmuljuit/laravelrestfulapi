<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmpLeave extends Model
{
    protected $table =  'TA_EmpLeave';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowId';

    Protected $gurded = [];



}