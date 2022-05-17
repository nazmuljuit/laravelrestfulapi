<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\EmpLeave;
class ManApproval extends Model
{
    protected $table =  'MAN_Approval';
    const CREATED_AT = 'EntryDate';
    const UPDATED_AT = 'UpdateDate';
    protected $primaryKey = 'RowID';

    public function user_info()
    {
        return $this->belongsTo(User::class, 'EmployeeCode', 'RequestingID');
    }

    public function emp_leave()
    {
        return $this->belongsTo(EmpLeave::class, 'rIdentityValue', 'RowID');
    }

}
