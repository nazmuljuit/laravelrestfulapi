<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AptExecutiveTarget extends Model
{
    protected $table =  'APT_ExecutiveTarget';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowId';
    



}