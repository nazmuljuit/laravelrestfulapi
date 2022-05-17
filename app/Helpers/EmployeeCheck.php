<?php
use App\User;
use App\Models\UserApp;

 function employee_verify($EmployeeCode)
 {
    $checkData = User::where('EmployeeCode',$EmployeeCode)->where('status','Active')->count();
    return $checkData;
 }

 function app_permission($EmployeeCode)
 {
   $userpermission = UserApp::where('EmployeeCode',$EmployeeCode)->select('AppPermission')->first();
   return $userpermission;
 }
