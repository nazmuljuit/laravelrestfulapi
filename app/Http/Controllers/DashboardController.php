<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AptExecutiveTarget;
use App\Models\AptProjectCR;
use App\Models\AptProjectDetail;
use App\Models\EmpLeave;
use App\Models\ManApproval;
use Illuminate\Support\Facades\Storage;
use URL;
use App\User;
use DB;
use Validator;
class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        ini_set('max_execution_time', 300000);
    }

    public function SalseTarget($EmployeeCode)
    {
        if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        {
            $checkData = employee_verify($EmployeeCode);
            
            if($checkData)
            {
                $data = [];
                $user_permission = app_permission($EmployeeCode);
                $data['app_permission'] = $user_permission->AppPermission;
                $fromdate = "2018-03-01";
                $currentdate = "2018-04-30";
                $lastdate = "2018-03-31";
                $year = "2018";
                // $year1 = "2019"; 
                
                // $fromdate =date('Y-m-01');
                // $currentdate = date('Y-m-d');
                // $lastdate = date('Y-m-t');
                // $year = date('Y');
                // return $year = date('Y');
                

                // total salse target 
                $total_sales_target = AptExecutiveTarget::where('year',$year)
                            ->where('deptalias',"Sales")
                            ->where('FromDate','<=',$currentdate)
                            ->get();

                $data['TarSales_Total'] = $total_sales_target->sum('Amount');

                // monthly salse target 
                $data['TarSales_This'] = $total_sales_target
                        ->where('FromDate','>=',$fromdate)
                        ->where('ToDate','<=',$currentdate)->sum('Amount');

                // total encash target         
                $total_encash_target = AptExecutiveTarget::where('year', $year)
                        ->where('deptalias',"CR")
                        ->where('FromDate','<=',$currentdate)
                        ->get();

                $data['TarEnc_Total'] =  $total_encash_target->sum('CR');
                // monthly encash target 
                $data['TarEnc_This'] = $total_encash_target
                        ->where('FromDate','>=',$fromdate)
                        ->where('ToDate','<=',$lastdate)
                        ->sum('CR');

                // total enc  
                $total_encash = AptProjectCR::whereYear('FinalHDate', $year)
                        ->where('ModeOfPay','<>','Adjustment')
                        ->where('ChqStatus','H')
                        ->where('FinalHDate','<=',$currentdate)
                        ->where("PayCode","Like","%1")
                        // ->select('PayCode')
                        ->get();
                    //  return $total_encash->count();   

                $data['Enc_Total'] =  $total_encash->sum('Amount');
                // monthly enc  
                $data['Enc_This'] =   $total_encash
                            ->where('FinalHDate','<=',$fromdate)
                            ->where('FinalHDate','>=',$lastdate)
                            ->sum('Amount');

            $project_code = AptProjectDetail::leftJoin('Apt_Project_CR', 
                function ($join) {
                    $join->on('Apt_Project_Detail.Project_Code', '=', 'Apt_Project_CR.Project_Code')
                        ->on('Apt_Project_Detail.FlatID', '=', 'Apt_Project_CR.FlatNo')
                        ->on('Apt_Project_Detail.FileNo', '=', 'Apt_Project_CR.FileNo');
                    }
                )
                
                    ->where('Apt_Project_CR.ModeOfPay','<>', 'Adjustment')
                    ->where('Apt_Project_CR.ChqStatus','=','H')
                    ->where('Apt_Project_CR.PayCode',"Like","%1")
                    ->where('Apt_Project_Detail.CompanyID',101)
                    ->where('Apt_Project_Detail.Status','Sold')
                    ->whereYear('Apt_Project_Detail.BookingDate',$year)
                    ->where('Apt_Project_Detail.BookingDate','<=',"2019-03-31")
                    ->select('Apt_Project_Detail.NetPrice','Apt_Project_Detail.BookingDate','Apt_Project_Detail.Project_Code','Apt_Project_Detail.FlatID')
                    ->distinct('Apt_Project_Detail.Project_Code')
                    ->get();
                  
               $data['Sales_Total'] = $project_code->sum('NetPrice');

               $data['Sales_This'] = $project_code
                            ->where('BookingDate','>=',$fromdate)
                            ->where('BookingDate','<=',$lastdate)
                            ->sum('NetPrice');

                    // attendance 
                $attendance_data = "WITH InOUt AS (SELECT C_Unique, C_Date, MIN(C_Time) AS InTime, MAX(C_Time) AS OutTime
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
                        $result  = DB::Select($attendance_data);
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
               
                    $data['attendance'] = $attendance_status;

                    $approval_lists = ManApproval::select('TypeKey', DB::raw('count(*) as total'))
                    ->where('ApprovalStatus',"Pending")
                    ->Where(function($query)  use ($EmployeeCode){
                        $query->where('RequestingID',$EmployeeCode);
                        $query->orwhere('PermissionDelegatedTo',$EmployeeCode); 
                    })->groupBy('TypeKey')
                    ->orderBy('TypeKey','ASC')
                    ->get(); 
                    $pending_data = 0;
                    if(!empty($approval_lists)){
                        foreach($approval_lists as $value){
                            if( TRIM($value->TypeKey) == "HR-Leave" || TRIM($value->TypeKey) == "HR-Movement"  ||  TRIM($value->TypeKey) == "White_Voucher" ) 
                            {
                                $pending_data += $value->total;
                            }
                        }
    
                        $data['pending_data'] = $pending_data;

                    }
                    
            
                return response()->json(['code' => 200,'data' => $data],200);
            }
            else
            {
                auth()->logout();
                return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
            }

        }
        else
        {
            auth()->logout();
            return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
        }
    }

    public function employee_attendance($EmployeeCode)
    {
       

        if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        { 
            $checkData = employee_verify($EmployeeCode);
            if($checkData)
            {
               
                $current_date = date('Ymd');
                $data = "select COUNT(EmployeeCode) AS TotalEmployee, 
                        SUM(CASE WHEN FinalStatus IN ('InTimePresent', 
                        'LatePresent') THEN 1 ELSE 0 END) AS TotalPresent, 
                        SUM(CASE WHEN FinalStatus = 'InTimePresent' THEN 1 ELSE 0 END) AS InTimePresent,
                        SUM(CASE WHEN FinalStatus = 'LatePresent' THEN 1 ELSE 0 END) AS LatePresent,
                        SUM(CASE WHEN FinalStatus = 'Leave' THEN 1 ELSE 0 END) AS Leave,
                        SUM(CASE WHEN FinalStatus = 'Absent' THEN 1 ELSE 0 END) AS Absent
                        from dbo.Employees_Attendance_Processing_V2($current_date) WHERE HOEmployee='Yes' AND LEFT(HRCode,4) = '2000' ";
                       
                $result  = DB::select($data);        
                return response()->json(['code' => 200,'data' => $result],200);
                
            }
            else
            {
                auth()->logout();
                return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
            }

        }
        else
        {
            auth()->logout();
            return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
        }
    }

    public function FileUpload(Request $request)
    {
        
        $validator = Validator::make($request->all(),[
            'content_file' => 'required|mimes:mpeg,ogg,mp4,webm,3gp,mov,flv,avi,wmv,ts,jpg,jpge,png,doc,docx,pptx,ppt,pdf,xls,xlsx,csv,txt',
            'chat_id' => 'required'
            ]);

        if($validator->fails()) {

            return response()->json(['error'=>$validator->errors()], 401);
        }

        if ($request->hasFile('content_file'))
        {

            $dest_path = "contents";
            $ext = $request->content_file->getClientOriginalExtension();
            $path = Storage::putFileAs(
                $dest_path,
                $request->file('content_file'),
                'file_'. $request->chat_id.'_'.time().'.'.$ext
            );

            $base_url = URL::to('/');
            $storage_path = "/storage/app/";
            $file_url = $base_url . $storage_path . $path;

           return response()->json(['code' => 200,'file_path' => $file_url],200);

        }

       
    }
}
