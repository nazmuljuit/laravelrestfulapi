<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UCompanies extends Model
{
    protected $table =  'U_Companies';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';

    

}
