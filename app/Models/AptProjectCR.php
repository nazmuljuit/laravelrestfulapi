<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AptProjectCR extends Model
{
    protected $table =  'Apt_Project_CR';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';

    

}