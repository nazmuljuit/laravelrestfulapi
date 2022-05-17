<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\User;
use App\Models\AmgHRDetail;
use DB;
use GuzzleHttp\Client;
class UserController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile($EmployeeCode)
    { 
        if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        {                                                                                             
            $checkData = employee_verify($EmployeeCode);
           
            if($checkData){  
                try { 

                   
                    $user = User::where('EmployeeCode', '=' ,$EmployeeCode)
                            ->Join('U_Companies','AMG_HR.SalaryUnit','=','U_Companies.CompanyID')
                            ->select('HRTitle as name',DB::raw('CAST(EmployeeCode AS CHAR) AS employeeCode'),'SupervisorID','Designation','Grade','Step','Category','PABX','officeMobile','mobilePhone','eMail','HRCode','joiningDate','homePhone','U_Companies.CompanyName as salaryUnit')
                            ->firstOrFail();

                    //attendance 

                    $data = "WITH InOUt AS (SELECT C_Unique, C_Date, MIN(C_Time) AS InTime, MAX(C_Time) AS OutTime
                            FROM [UNIS].[dbo].[tEnter]
                            Where C_Unique = $EmployeeCode AND C_Date = CAST(Year(GETDATE()) AS CHAR(4))+RIGHT('0'+RTRIM(CAST(Month(GETDATE()) AS CHAR(2))),2)+RIGHT('0'+RTRIM(CAST(Day(GETDATE()) AS CHAR(2))),2)
                            Group By C_Unique, C_Date),

                            EmpPunch AS (Select IO.C_Unique AS EmpCode, IO.C_Date, IO.InTime,
                                            CASE WHEN E1.L_MatchingType=0 THEN 'finger' WHEN E1.L_MatchingType = 3 THEN 'card' ELSE 'other pucnh method' END AS InPunch
                                    From InOUt AS IO
                                                LEFT OUTER JOIN UNIS.dbo.tEnter AS E1 ON IO.C_Unique = E1.C_Unique AND IO.C_Date = E1.C_Date AND IO.InTime = E1.C_Time)

                            SELECT     dbo.AMG_HR.EmployeeCode, dbo.AMG_HR.HRTitle,
                                CASE WHEN EmpCode IS NULL THEN 'Absent today' ELSE InTime+' '+InPunch END AS AttendanceToday
                            FROM dbo.AMG_HR LEFT OUTER JOIN

                            dbo.AMG_HR AS AMG_HR_1 ON dbo.AMG_HR.pHead = AMG_HR_1.HRCode
                                    LEFT OUTER JOIN dbo.U_Companies AS SU ON  dbo.AMG_HR.SalaryUnit  = SU.CompanyID
                                    LEFT OUTER JOIN EmpPunch AS EP ON dbo.AMG_HR.EmployeeCode = EP.EmpCode
                            WHERE (dbo.AMG_HR.EmployeeCode = $EmployeeCode)";
                        $result  = DB::Select($data);
                        $attendance = $result[0]->AttendanceToday;
                        
                        if($attendance == 'Absent today')
                        {
                            $attendance_status = array("present_status" =>$attendance, 'type' =>"" , "time" =>"");
                        }
                        else 
                        {
                            $value = explode(" ",$attendance);
                            $attendance_status = array("present_status" =>"Present Today", "type" =>$value[1] ,"time" => substr($value[0],0,2).'-'.substr($value[0],2,2).'-'.substr($value[0],4,2)); 
                        }

                        $user['attendance'] = $attendance_status;



                             
                    // attendace time
                    //     $current_date = Date('Y-m-d');
                    //     $attendance_info =   DB::connection('sqlsrv2')
                    //     ->table('tEnter')
                    //     ->where("C_Unique", TRIM($user->employeeCode))
                    //     ->groupBy('C_Date','C_Unique')
                    //     ->select('C_Date',\DB::raw("MIN(C_Time) AS C_Time"),'C_Unique')
                    //     ->orderby('C_Date','DESC')
                    //     ->limit(1)
                    //     ->get();
                       
                    //   if(!empty($attendance_info) && count($attendance_info) !=0) 
                    //    { 
                        
                    //         $attend = substr($attendance_info[0]->C_Date,0,4).'-'.substr($attendance_info[0]->C_Date,-4,2). '-' . substr($attendance_info[0]->C_Date,-2,2);
                            
                    //         if($attend == $current_date)
                    //         {
                                
                    //             $user['attendance'] = 'Present('.(substr($attendance_info[0]->C_Time,0,2).'-'.substr($attendance_info[0]->C_Time,2,2).'-'.substr($attendance_info[0]->C_Time,4,2)).')';
                    //         }
                    //         else 
                    //         {
                    //             $user['attendance'] =  'Absent Today';
                    //         }
                    //     } 
                            
                 
                   // For Department name
                        $dept1 = User::where('EmployeeCode',$EmployeeCode)
                            ->select('HRCode')->first();
                        if(!empty($dept1)){
                            $dept2 =User::where('HRCode',substr($dept1->HRCode, 0, 7))
                            ->select('HRTitle')->first();
                        }    
                       
                      
                    $user['image'] = 'http://205.188.5.54/images/uploads/members/'.TRIM($user->employeeCode).'.'.'jpg';
                    $user['sign']  = 'http://205.188.5.54/images/employeesigh/'.TRIM(ltrim($user->employeeCode, '0')).'.'.'jpg';
                    
                    $user_detail_info = AmgHRDetail::where('EmployeeCode',TRIM($user->employeeCode))->select('BloodGroup')->first();
                    if(!empty($user_detail_info)){
                        $user['BloodGroup']  = $user_detail_info->BloodGroup;
                    }else {
                        $user['BloodGroup']  = "";
                    }
                   
                    if(!empty($dept2)){
                        $user['Department']  = $dept2->HRTitle;
                    }else{
                        $user['Department']  = "";
                    }
                    
                    
                    $supervisor_info = User::where('EmployeeCode',TRIM($user->SupervisorID))->select('EmployeeCode','HRTitle')->first();
                    
                    if(!empty($supervisor_info))
                    {
                        // $supervisor_info = [];
                        $supervisor_detail['supervisor_emp_code'] = TRIM($supervisor_info->EmployeeCode);
                        $supervisor_detail['supervisor_name'] = TRIM($supervisor_info->HRTitle);
                        $supervisor_detail['supervisor_image'] = 'http://205.188.5.54/images/uploads/members/'.TRIM($supervisor_info->EmployeeCode).'.'.'jpg';;
                        $user['supervisor_info']  = $supervisor_detail;
                        // $user['supervisor_emp_code']  = TRIM($supervisor_info->EmployeeCode);
                        // $user['supervisor_name']  = TRIM($supervisor_info->HRTitle);
                        // $user['supervisor_image']  = 'http://205.188.5.54/images/uploads/members/'.TRIM($supervisor_info->EmployeeCode).'.'.'jpg';
                        
                    }
                    else
                    {
                        $user['supervisor_info'] = NULL;
                    }
                    
                    
                    return response()->json(['code' =>200, 'user' => $user],200);

                } catch (\Exception $e) {

                    return response()->json(['code' =>404,'message' => 'user not found!'],404);
                }
            }
            else
            {
                auth()->logout();
                return response()->json(['code' =>401,'message' => 'Unauthorized'],401);
            }
        }

        else 
        {
            auth()->logout();
            return response()->json(['code' =>401,'message' => 'Unauthorized'],401);
        }
        

        // return response()->json(['user' => Auth::user()], 200);
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {

        $users = User::select('RowID',DB::raw('CAST(EmployeeCode AS CHAR) AS employeeCode'), 'HRTitle','Designation','eMail','officeMobile')->where('status','Active')->get();
        $user_lists = array();
        foreach($users as $key=>$user)
        {

             //attendance 
             $employeeCode = TRIM($user->employeeCode);
             $data = "WITH InOUt AS (SELECT C_Unique, C_Date, MIN(C_Time) AS InTime, MAX(C_Time) AS OutTime
             FROM [UNIS].[dbo].[tEnter]
             Where C_Unique = $employeeCode AND C_Date = CAST(Year(GETDATE()) AS CHAR(4))+RIGHT('0'+RTRIM(CAST(Month(GETDATE()) AS CHAR(2))),2)+RIGHT('0'+RTRIM(CAST(Day(GETDATE()) AS CHAR(2))),2)
             Group By C_Unique, C_Date),

             EmpPunch AS (Select IO.C_Unique AS EmpCode, IO.C_Date, IO.InTime,
                             CASE WHEN E1.L_MatchingType=0 THEN 'finger' WHEN E1.L_MatchingType = 3 THEN 'card' ELSE 'other pucnh method' END AS InPunch
                     From InOUt AS IO
                                 LEFT OUTER JOIN UNIS.dbo.tEnter AS E1 ON IO.C_Unique = E1.C_Unique AND IO.C_Date = E1.C_Date AND IO.InTime = E1.C_Time)

             SELECT     dbo.AMG_HR.EmployeeCode, dbo.AMG_HR.HRTitle,
                 CASE WHEN EmpCode IS NULL THEN 'Absent today' ELSE InTime+' '+InPunch END AS AttendanceToday
             FROM dbo.AMG_HR LEFT OUTER JOIN

             dbo.AMG_HR AS AMG_HR_1 ON dbo.AMG_HR.pHead = AMG_HR_1.HRCode
                     LEFT OUTER JOIN dbo.U_Companies AS SU ON  dbo.AMG_HR.SalaryUnit  = SU.CompanyID
                     LEFT OUTER JOIN EmpPunch AS EP ON dbo.AMG_HR.EmployeeCode = EP.EmpCode
             WHERE (dbo.AMG_HR.EmployeeCode = $employeeCode)";
        $result  = DB::Select($data);
        $attendance = $result[0]->AttendanceToday;
        if($attendance == 'Absent today')
        {
            $attendance_status = array("present_status" =>$attendance, 'type' =>"" , "time" =>"");
        }
        else 
        {
            $value = explode(" ",$attendance);
            $attendance_status = array("present_status" =>"Present Today", "type" =>$value[1] ,"time" => substr($value[0],0,2).'-'.substr($value[0],2,2).'-'.substr($value[0],4,2)); 
        }  
         
        $user['attendance'] = $attendance_status;
         
            $user['image'] = 'http://172.16.200.11:91/images/uploads/members/'.TRIM($user->employeeCode).'.'.'jpg';
           $user_lists[] = $user;
        }
         return response()->json(['code' => 200,'users' =>  $user_lists],200);
        //  return response()->json(['users' =>  User::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id ,$EmployeeCode)
    {
        // $current_date = Date('Y-m-d');
        //                         $attendance_info =   DB::connection('sqlsrv2')
        //                         ->table('tEnter')
        //                         ->where("C_Unique", $EmployeeCode)
        //                         ->groupBy('C_Date','C_Unique')
        //                         ->select('C_Date',\DB::raw("MIN(C_Time) AS I_Time"), \DB::raw("MAX(C_Time) AS O_Time"),'C_Unique')
        //                         ->orderby('C_Date','DESC')
        //                         ->limit(1)
        //                         ->get();
    
   
        // if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        // {
            // Department info
            $dept1 =User::where('EmployeeCode',$EmployeeCode)
                    ->select('HRCode')->first();
            if(!empty($dept1)){
                $dept2 =User::where('HRCode',substr($dept1->HRCode, 0, 7))
                ->select('HRTitle')->first();
            }
            

            $checkData = employee_verify($EmployeeCode);

            if($checkData){
                try {

                      //attendance 

                    $data = "WITH InOUt AS (SELECT C_Unique, C_Date, MIN(C_Time) AS InTime, MAX(C_Time) AS OutTime
                        FROM [UNIS].[dbo].[tEnter]
                        Where C_Unique = $EmployeeCode AND C_Date = CAST(Year(GETDATE()) AS CHAR(4))+RIGHT('0'+RTRIM(CAST(Month(GETDATE()) AS CHAR(2))),2)+RIGHT('0'+RTRIM(CAST(Day(GETDATE()) AS CHAR(2))),2)
                        Group By C_Unique, C_Date),

                        EmpPunch AS (Select IO.C_Unique AS EmpCode, IO.C_Date, IO.InTime,
                                        CASE WHEN E1.L_MatchingType=0 THEN 'finger' WHEN E1.L_MatchingType = 3 THEN 'card' ELSE 'other pucnh method' END AS InPunch
                                From InOUt AS IO
                                            LEFT OUTER JOIN UNIS.dbo.tEnter AS E1 ON IO.C_Unique = E1.C_Unique AND IO.C_Date = E1.C_Date AND IO.InTime = E1.C_Time)

                        SELECT     dbo.AMG_HR.EmployeeCode, dbo.AMG_HR.HRTitle,
                            CASE WHEN EmpCode IS NULL THEN 'Absent today' ELSE InTime+' '+InPunch END AS AttendanceToday
                        FROM dbo.AMG_HR LEFT OUTER JOIN

                        dbo.AMG_HR AS AMG_HR_1 ON dbo.AMG_HR.pHead = AMG_HR_1.HRCode
                                LEFT OUTER JOIN dbo.U_Companies AS SU ON  dbo.AMG_HR.SalaryUnit  = SU.CompanyID
                                LEFT OUTER JOIN EmpPunch AS EP ON dbo.AMG_HR.EmployeeCode = EP.EmpCode
                        WHERE (dbo.AMG_HR.EmployeeCode = $EmployeeCode)";
                    $result  = DB::Select($data);
                    $attendance = $result[0]->AttendanceToday;
                    
                    if($attendance == 'Absent today')
                    {
                        $attendance_status = array("present_status" =>$attendance, 'type' =>"" , "time" =>"");
                    }
                    else 
                    {
                        $value = explode(" ",$attendance);
                        $attendance_status = array("present_status" =>"Present Today", "type" =>$value[1] ,"time" => substr($value[0],0,2).'-'.substr($value[0],2,2).'-'.substr($value[0],4,2)); 
                    }

                    


                    $user = User::where('RowId', '=' ,$id)->select('RowID','HRTitle',DB::raw('CAST(EmployeeCode AS CHAR) AS employeeCode'),'Designation','eMail','officeMobile','mobilePhone','homePhone','status')->firstOrFail();
                    $user['attendance'] = $attendance_status;
                    $user['image'] = 'http://205.188.5.54/images/uploads/members/'.TRIM($user->employeeCode).'.'.'jpg';
                    if(!empty($dept2->HRTitle)){
                        $user['Department'] = $dept2->HRTitle;
                    }else {
                        $user['Department'] = "";
                    }
                    
                    return response()->json(['code' => 200, 'user' => $user],200);

                } catch (\Exception $e) {

                    return response()->json(['code' => 404, 'message' => 'user not found!'],404 );
                }
            }
            else
            {
                auth()->logout();
                return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
            }

        // }
        // else
        // {
        //     auth()->logout();
        //     return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
        // }


    }

    public function update_user(Request $request)
    {
        $checkData = employee_verify($request->EmployeeCode);
        // $id = $request->EmployeeCode;
        if($checkData)
        {
            try {
                $user = User::where('EmployeeCode', '=' , $request->EmployeeCode)->firstOrFail();
                // $user =  User::where('RowID', $request->Rowid)->update(['HRTitle' => $request->HRTitle]);
                // $user1 = User::findOrFail($id);
                // return $user;
                $user->HRTitle = $request->HRTitle;
                // return $user;
                $user->save();

                return response()->json(['code' => 200, 'message'=>'Successfully Updated','user' => $user],200);

            } catch (\Exception $e) {

                return response()->json(['code' => 404, 'message' => 'user not found!'],404);
            }
        }
        else
        {
            auth()->logout();
            return response()->json(['code' => 401 ,'message' => 'Unauthorized'],401);
        }


    }

    public function get_approval_list_by_emp_code(Request $request)
    {
        $emp_code = $request->empcode;

        $data = DB::table('MAN_Approval')
           ->where('RequestingID',$emp_code)
           ->where('ApprovalStatus','Approved')
             ->get()->count();
        return $data;
    }

    public function Search_Users(Request $request)
    {
        if(TRIM(auth()->user()->EmployeeCode) == TRIM($request->EmployeeCode))
        {
    
            $checkData = employee_verify($request->EmployeeCode);
            if($checkData)
            {
                if($request->search)
                {
                                    
                    $user_lists = User::select('RowID','HRTitle as name',DB::raw('CAST(EmployeeCode AS CHAR) AS employeeCode'),'Designation','eMail','officeMobile','PABX')
                                ->where("status","Active")
                                ->where("HOEmployee","Yes")
                                ->Where(function($query)  use ($request){
                                    $query->orwhere("HRTitle","Like","%{$request->search}%");
                                    $query->orwhere("PABX","Like","%{$request->search}%");
                                    $query->orwhere("officeMobile","Like","%{$request->search}%");
                                    $query->orWhere("EmployeeCode","Like","%{$request->search}%");
                            })->get();
                        
                        $all_user = array();
                    if(!empty($user_lists))
                    {        
                        foreach($user_lists as $key=>$user)
                        {
                             $employeeCode =  TRIM($user->employeeCode);
                            $data = "WITH InOUt AS (SELECT C_Unique, C_Date, MIN(C_Time) AS InTime, MAX(C_Time) AS OutTime
                                FROM [UNIS].[dbo].[tEnter]
                                Where C_Unique = $employeeCode AND C_Date = CAST(Year(GETDATE()) AS CHAR(4))+RIGHT('0'+RTRIM(CAST(Month(GETDATE()) AS CHAR(2))),2)+RIGHT('0'+RTRIM(CAST(Day(GETDATE()) AS CHAR(2))),2)
                                Group By C_Unique, C_Date),

                                EmpPunch AS (Select IO.C_Unique AS EmpCode, IO.C_Date, IO.InTime,
                                                CASE WHEN E1.L_MatchingType=0 THEN 'finger' WHEN E1.L_MatchingType = 3 THEN 'card' ELSE 'other pucnh method' END AS InPunch
                                        From InOUt AS IO
                                                    LEFT OUTER JOIN UNIS.dbo.tEnter AS E1 ON IO.C_Unique = E1.C_Unique AND IO.C_Date = E1.C_Date AND IO.InTime = E1.C_Time)

                                SELECT     dbo.AMG_HR.EmployeeCode, dbo.AMG_HR.HRTitle,
                                    CASE WHEN EmpCode IS NULL THEN 'Absent today' ELSE InTime+' '+InPunch END AS AttendanceToday
                                FROM dbo.AMG_HR LEFT OUTER JOIN

                                dbo.AMG_HR AS AMG_HR_1 ON dbo.AMG_HR.pHead = AMG_HR_1.HRCode
                                        LEFT OUTER JOIN dbo.U_Companies AS SU ON  dbo.AMG_HR.SalaryUnit  = SU.CompanyID
                                        LEFT OUTER JOIN EmpPunch AS EP ON dbo.AMG_HR.EmployeeCode = EP.EmpCode
                                WHERE (dbo.AMG_HR.EmployeeCode = $employeeCode)";
                        $result  = DB::Select($data);
                        $attendance = $result[0]->AttendanceToday; 

                        if($attendance == 'Absent today')
                        {
                            $attendance_status = array("present_status" =>$attendance, 'type' =>"" , "time" =>"");
                        }
                        else 
                        {
                            $value = explode(" ",$attendance);
                            $attendance_status = array("present_status" =>"Present Today", "type" =>$value[1] ,"time" => substr($value[0],0,2).'-'.substr($value[0],2,2).'-'.substr($value[0],4,2)); 
                        }

                        $user['attendance'] = $attendance_status;


                            // attendace time
                    //     $current_date = Date('Y-m-d');
                    //     $attendance_info =   DB::connection('sqlsrv2')
                    //     ->table('tEnter')
                    //     ->where("C_Unique", TRIM($user->employeeCode))
                    //     ->groupBy('C_Date','C_Unique')
                    //     ->select('C_Date',\DB::raw("MIN(C_Time) AS C_Time"),'C_Unique')
                    //     ->orderby('C_Date','DESC')
                    //     ->limit(1)
                    //     ->get();
                          
                    //   if(!empty($attendance_info) && count($attendance_info) !=0) 
                    //    { 
                        
                    //         $attend = substr($attendance_info[0]->C_Date,0,4).'-'.substr($attendance_info[0]->C_Date,-4,2). '-' . substr($attendance_info[0]->C_Date,-2,2);
                            
                    //         if($attend == $current_date)
                    //         {
                    //             $user['attendance'] = 'Present('.(substr($attendance_info[0]->C_Time,0,2).'-'.substr($attendance_info[0]->C_Time,2,2).'-'.substr($attendance_info[0]->C_Time,4,2)).')';
                                
                    //         }
                    //         else 
                    //         {
                    //             $user['attendance'] =  'Absent Today';
                    //         }
                    //     } 




                            
                            $user['image'] = 'http://205.188.5.54/images/uploads/members/'.TRIM($user->employeeCode).'.'.'jpg';
                            
                            $H1 = User::where('EmployeeCode',TRIM($user->employeeCode))
                            ->select('HRCode')->first();
                            //    return $H1;
                            if(!empty($H1)){
                                $H2 = User::where('HRCode',substr($H1->HRCode, 0, 7))
                                ->select('HRTitle')->first();
                                if(!empty($H2)){
                                    $user['Department'] = $H2->HRTitle;
                                }else {
                                    $user['Department'] = "";
                                }
                            }
                            
                            $all_user[] = $user;
                        }
                    }
                    else{
                        return response()->json(['code' => 404,'message' => 'Data Not Found'],404);
                    }

                    // $user_lists[0] ['image'] =  "http://172.16.200.11:91/images/uploads/members/".$request->EmployeeCode.".jpg"; 

                    if($user_lists->count()){
                        return response()->json(['code' => 200,'user' => $all_user],200);
                    }
                    else{
                        return response()->json(['code' => 404,'message' => 'Data Not Found'],404);
                    }
                }
                else{
                    return response()->json(['code' => 404,'message' => 'Data Not Found'],404);
                }
            }
            else{
                auth()->logout();
                return response()->json(['code' => 401,'message' => 'Unauthorized'],401 );
            }

        }
        else{
            auth()->logout();
            return response()->json(['code' => 401,'message' => 'Unauthorized'],401 );
        }

    }

}
