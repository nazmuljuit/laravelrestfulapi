<?php

namespace App\Http\Controllers;
use App\Models\ManApproval;
use App\Models\EmpLeave;
use App\Models\EmpLeaveDetail;
use App\Models\EmployeeMovement;
use App\Models\EmployeeMovementDetail;
use App\Models\WhiteVoucherListApp;
use App\Models\UCompanies;
use App\Models\AptProject;
use App\Models\WhiteVoucherApt;
use App\Models\AdminVehiclesRequisition;
use App\Models\JoinModel;
use App\Models\CrudModel;
use App\Models\AdminVehiclesAssignmentLog;
use App\Models\AdminOfficeStationeryRequisition;
use App\Models\AptCnbBoqMpr;
use App\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
class ManApprovalController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function AllApprovalList($EmployeeCode,$ApprovalStatus='Pending')
    {
        if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        {

            $checkData = employee_verify($EmployeeCode);
            if($checkData)
            {
                $approval_lists = ManApproval::select('TypeKey', DB::raw('count(*) as total'))
                    ->where('ApprovalStatus',$ApprovalStatus)
                    ->whereIn('TypeKey',['HR-Leave','HR-Movement','White_Voucher','Vehicle','VehicleAdmin','StationeryRequisition','MPR_BOQ','MPR_BOQ_Over','MPR_BOQ_ROD','MRR_BOQ','MPO_BOQ','BOQ-S'])
                    ->Where(function($query)  use ($EmployeeCode){
                        $query->where('RequestingID','like', '%'.$EmployeeCode.'%');
                        $query->orwhere('PermissionDelegatedTo',$EmployeeCode); 
                    })->groupBy('TypeKey')
                    ->orderBy('TypeKey','ASC')
                    ->get();
                    
                return response()->json(['code' => 200,'approval_lists' => $approval_lists,'approval_status'=>$ApprovalStatus],200);
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

    public function Approve_list_by_keytype($keytype,$ApprovalStatus,$EmployeeCode,$RowID)
    {
        $limit_len = 10;
        $step = $RowID*$limit_len;

        if(TRIM(auth()->user()->EmployeeCode) == TRIM($EmployeeCode))
        {

            $checkData = employee_verify($EmployeeCode);

            if($checkData)
            { 
                $sql = "SELECT $RowID"; 
                $approval_lists = ManApproval::where('TypeKey',$keytype)
                    ->where('ApprovalStatus',$ApprovalStatus)
                    ->where('RequestingID','like', '%'.$EmployeeCode.'%')
                    
                    ->select('RowID','TypeKey','rTopic','RequestDate','rIdentityValue','ApprovalStatus','MessageDetail','rIdentityColumn')
                    ->selectSub($sql, 'RowNum')
                    ->offset($step)
                    ->limit($limit_len)
                    ->get();
                    if(!empty($approval_lists))
                    {
                        $approval_lists->map(function($approval_list) use ($keytype){
                            if($keytype == "HR-Leave")
                            {
                                $emp_leave_info = EmpLeave::where('RowID',$approval_list->rIdentityValue)->select('RowID','EmployeeCode','DayCount','LeaveDate as Date','Leave_Type as Type','LeaveDetail as Detail')->first();
                                                                                             
                            }

                            elseif($keytype == "HR-Movement")
                            {
                                $emp_leave_info = EmployeeMovement::where('RowID',$approval_list->rIdentityValue)->select('RowID','EmployeeCode','DayCount','NoteDate as Date','AttendanceStatus as Type','NoteDetail as Detail')->first();
                                // $emp_leave_info['type'] ="";
                            }
                                  

                            elseif($keytype == "White_Voucher")
                            {
                                $emp_leave_info = WhiteVoucherListApp::where('RowID',TRIM($approval_list->rIdentityValue))->select('RowID','IssueFor as EmployeeCode','VrNo','VrDate as Date','_Type as Type','VrAmount')->first();
                               
                            }
                            elseif($keytype == "Vehicle")
                            {
                                $emp_leave_info = AdminVehiclesRequisition::where('RowID',TRIM($approval_list->rIdentityValue))->select('RowID','EmployeeCode','MobileNo','VisitType','StartFrom','AppStatus')->first();
                               
                            }
                            elseif($keytype == "VehicleAdmin")
                            {
                                $emp_leave_info = AdminVehiclesRequisition::where('RowID',TRIM($approval_list->rIdentityValue))->select('RowID','EmployeeCode','MobileNo','VisitType','StartFrom','AppStatus')->first();
                               
                            }
                            elseif($keytype == "StationeryRequisition")
                            {
                                $emp_leave_info = AdminOfficeStationeryRequisition::where('RefNo',TRIM($approval_list->rIdentityValue))->select('RowID','RequisitorID as EmployeeCode','Remark','ApprovalRequest','ApprovalStatus','RequisitionDate','RefNo')->first();
                               
                            }
                            elseif($keytype == "MPR_BOQ" || $keytype == "MPR_BOQ_ROD" || $keytype == "BOQ-S")
                            {
                                $emp_leave_info = AptCnbBoqMpr::where('RowID',TRIM($approval_list->rIdentityValue))->select('RowID','Project_Code','MPRNo','MPRDate','JobCode','MPRStatus')->first();

                               
                            }

                            
                            if(!empty($emp_leave_info))
                            {
                                $dept1 = User::where('EmployeeCode',TRIM($emp_leave_info->EmployeeCode))
                                ->select('HRCode')->first();

                                if(!empty($dept1))
                                {
                                    $dept2 =User::where('HRCode',substr($dept1->HRCode, 0, 7))
                                    ->select('HRTitle')->first();
                                    if(!empty($dept2)){
                                        $deptname = $dept2->HRTitle;
                                    }else {
                                        $deptname = "";
                                    }
                                   
                                }
                                else 
                                {
                                    $deptname = ""; 
                                }
                                
                                $user_info = User::where('EmployeeCode',$emp_leave_info->EmployeeCode)->select('RowID','EmployeeCode','HRTitle as name','Designation')->first();
                               
                               
                                
                                if(!empty($user_info))
                                {
                                    $user_info['Department'] = $deptname;
                                    $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($emp_leave_info->EmployeeCode).'.'.'jpg';
                                }
                                
                                $approval_list->setRelation('emp_leave_info', $emp_leave_info);
                                $approval_list->setRelation('user_info', $user_info);
                            }
                            else 
                            {
                                $user_info = null;
                                $approval_list->setRelation('emp_leave_info', $emp_leave_info);
                                $approval_list->setRelation('user_info', $user_info);  
                            }   
                            return $approval_list;
                        });
                    }    
                
                    
                    

                return response()->json(['code' => 200,'approval_lists_details' => $approval_lists],200);
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

    public function update_approval_byId(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'ApprovalStatus' => 'required',
            'EmployeeCode' => 'required',
            'RowID'        => 'required',
            'TypeKey'      => 'required'
           
        ]);

        if(TRIM(auth()->user()->EmployeeCode) == TRIM($request->EmployeeCode))
        {

            $checkData = employee_verify($request->EmployeeCode);

            if($checkData)
            {

                try 
                {

                    // HR-Leave 

                    $man_approval = ManApproval::where('RowId', '=' ,$request->RowID)->firstOrFail();
                    
                if($request->TypeKey == "HR-Leave")

                    {
                    $emp_leave = EmpLeave::where('RowID',$man_approval->rIdentityValue)->firstOrFail();

                        if(TRIM($emp_leave->ApprovedByJT) == TRIM($man_approval->RequestingID) && TRIM($emp_leave->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {
                                $emp_leave->ApprovedStatusJT = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                                $RequestingID =  TRIM($emp_leave->ApprovedBySv);
                                $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn="ApprovedBySv",$rAppovalVColumn="ApprovedStatusSv",$rApprovalStatus="ApprovalStatusSv",$ApprovalStatus="Pending",$ApprovalValue="No");
                            }
                            else 
                            {
                                $emp_leave->ApprovedStatusJT = "No";
                                $man_approval->ApprovalValue    = "No";
                            }
                            
                            
                        
                            EmpLeave::where('RowID', $emp_leave->RowID)->update([
                                'ApprovedStatusJT'=>$emp_leave->ApprovedStatusJT,
                                'ApprovalStatusJT'=>$request->ApprovalStatus
                        ]);
                        ManApproval::where('RowId', '=' ,$request->RowID)->update([
                            'ApprovalStatus' => $request->ApprovalStatus,
                            'ApprovalValue' => $man_approval->ApprovalValue,
                            'ApprovalDate' => Date("Y-m-d"),
                            'Remark' => $request->Remark,
                            'RemarkBy' => $request->EmployeeCode,
                            'RemarkDate' => Date("Y-m-d")
                        ]);
                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");                        
                            // $man_approval->save();                     
                    
                        }

                        elseif(TRIM($emp_leave->ApprovedBySv) == TRIM($man_approval->RequestingID) && TRIM($emp_leave->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                if(empty(TRIM($emp_leave->ApprovedByHOD)))
                                {
                                    $RequestingID =  TRIM($emp_leave->ApprovedBy);

                                    $rAppVColumn="ApprovedBy";
                                    $rAppovalVColumn="ApprovedStatus";
                                    $rApprovalStatus="ApprovalStatus";
                                }
                                else 
                                {
                                    $RequestingID =  TRIM($emp_leave->ApprovedByHOD);
                                    $rAppVColumn="ApprovedByHOD";
                                    $rAppovalVColumn="ApprovedStatusHOD";
                                    $rApprovalStatus="ApprovalStatusHOD";
                                }

                        
                                $emp_leave->ApprovedStatusSv = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                                $ApprovalStatus="Pending";
                                $ApprovalValue="No";
                                $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn,$rAppovalVColumn,$rApprovalStatus,$ApprovalStatus,$ApprovalValue);
                            }
                            else 
                            {
                                $emp_leave->ApprovedStatusSv = "No";
                                $man_approval->ApprovalValue    = "No";
                            }

                        
                            EmpLeave::where('RowID', $emp_leave->RowID)->update([
                                'ApprovedStatusSv'=>$emp_leave->ApprovedStatusSv,
                                'ApprovalStatusSv'=>$request->ApprovalStatus
                        ]);
                        ManApproval::where('RowId', '=' ,$request->RowID)->update([
                            'ApprovalStatus' => $request->ApprovalStatus,
                            'ApprovalValue' => $man_approval->ApprovalValue,
                            'ApprovalDate' => Date("Y-m-d"),
                            'Remark' => $request->Remark,
                            'RemarkBy' => $request->EmployeeCode,
                            'RemarkDate' => Date("Y-m-d")
                        ]);

                        
                            // $emp_leave->ApprovalStatusSv = $request->ApprovalStatus;
                            // $emp_leave->save();

                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");
                            // $man_approval->save();
                            
                        }

                        elseif(TRIM($emp_leave->ApprovedByHOD) == TRIM($man_approval->RequestingID) && TRIM($emp_leave->RowID) == TRIM($man_approval->rIdentityValue))

                        {
                            if($request->ApprovalStatus == "Approved")
                            {
                                $emp_leave->ApprovedStatusHOD = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                                $RequestingID =  TRIM($emp_leave->ApprovedBy);
                                $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn="ApprovedBy",$rAppovalVColumn="ApprovedStatus",$rApprovalStatus="ApprovalStatus",$ApprovalStatus="Pending",$ApprovalValue="No");
                            }
                            else 
                            {
                                $emp_leave->ApprovedStatusHOD = "No";
                                $man_approval->ApprovalValue    = "No";
                            }

                            EmpLeave::where('RowID', $emp_leave->RowID)->update([
                                'ApprovedStatusHOD'=>$emp_leave->ApprovedStatusHOD,
                                'ApprovalStatusHOD'=>$request->ApprovalStatus
                        ]);
                        ManApproval::where('RowId', '=' ,$request->RowID)->update([
                            'ApprovalStatus' => $request->ApprovalStatus,
                            'ApprovalValue' => $man_approval->ApprovalValue,
                            'ApprovalDate' => Date("Y-m-d"),
                            'Remark' => $request->Remark,
                            'RemarkBy' => $request->EmployeeCode,
                            'RemarkDate' => Date("Y-m-d")
                        ]);

                            
                            // $emp_leave->ApprovalStatusHOD = $request->ApprovalStatus;
                            
                            // $emp_leave->save();

                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");
                            // $man_approval->save();

                        

                        }

                        elseif(TRIM($emp_leave->ApprovedBy) == TRIM($man_approval->RequestingID) && TRIM($emp_leave->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {
                                $emp_leave->ApprovedStatus      = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                            
                            }
                            else 
                            {
                                $emp_leave->ApprovedStatus      = "No";
                                $man_approval->ApprovalValue    = "No";
                            }

                            EmpLeave::where('RowID', $emp_leave->RowID)->update([
                                'ApprovedStatus'=>$emp_leave->ApprovedStatus,
                                'ApprovalStatus'=>$request->ApprovalStatus
                        ]);

                            EmpLeaveDetail::where('RefRowID',$emp_leave->RowID)->update([
                            'Status' =>"Approved"
                            ]);

                            ManApproval::where('RowId', '=' ,$request->RowID)->update([
                            'ApprovalStatus' => $request->ApprovalStatus,
                            'ApprovalValue' => $man_approval->ApprovalValue,
                            'ApprovalDate' => Date("Y-m-d"),
                            'Remark' => $request->Remark,
                            'RemarkBy' => $request->EmployeeCode,
                            'RemarkDate' => Date("Y-m-d")
                        ]);
                        
                        // emp leave
                            // $emp_leave->ApprovalStatus = $request->ApprovalStatus;
                            // $emp_leave->save();

                            // emp leave detail

                            
                            // $emp_leave_detail = EmpLeaveDetail::where('RefRowID',$emp_leave->RowID)->firstOrFail();
                            // $emp_leave_detail->Status = "Approved";
                            // $emp_leave_detail->save();
                            // man approval
                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");
                            // $man_approval->save();
                    
                        }

                        if($request->ApprovalStatus != "Approved")
                        {
                            EmpLeaveDetail::where('RefRowId',$emp_leave->RowID)->delete();
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);

                    }

                    // HR-Movement

                    elseif($request->TypeKey == "HR-Movement")
                    {
                        $emp_move = EmployeeMovement::where('RowID',$man_approval->rIdentityValue)->firstOrFail();
                        
                        if(TRIM($emp_move->ApprovedByd) == TRIM($man_approval->RequestingID) && TRIM($emp_move->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            // return $emp_leave->ApprovedByd;
                            
                            if($request->ApprovalStatus == "Approved")
                            {
                                $emp_move->ApprovedStatusd = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                                if(!empty($emp_move->ApprovedBy))
                                {
                                    $RequestingID =  TRIM($emp_move->ApprovedBy);
                                    $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn="ApprovedBy",$rAppovalVColumn="ApprovedStatus",$rApprovalStatus="ApprovalStatus",$ApprovalStatus="Pending",$ApprovalValue="No");
                                }

                                else 
                                {
                                    EmployeeMovementDetail::where('RefRowID',$emp_move->RowID)->update([
                                        'Status' => $request->ApprovalStatus
                                        ]);
                                }
                                
                            }
                            else 
                            {
                                
                                    EmployeeMovementDetail::where('RefRowID',$emp_move->RowID)->update([
                                        'Status' => $request->ApprovalStatus
                                        ]);
                                
                                $emp_move->ApprovedStatusd      = "No";
                                $man_approval->ApprovalValue    = "No";
                            }

                            EmployeeMovement::where('RowID', $man_approval->rIdentityValue)->update([
                                'ApprovedStatusd'=>$emp_move->ApprovedStatusd,
                                'ApprovalStatusd'=>$request->ApprovalStatus
                        ]);

                            ManApproval::where('RowId', '=' ,$request->RowID)->update([
                            'ApprovalStatus' => $request->ApprovalStatus,
                            'ApprovalValue' => $man_approval->ApprovalValue,
                            'ApprovalDate' => Date("Y-m-d"),
                            'Remark' => $request->Remark,
                            'RemarkBy' => $request->EmployeeCode,
                            'RemarkDate' => Date("Y-m-d")
                        ]);

                            // $emp_move->ApprovalStatusd = $request->ApprovalStatus;
                            // $emp_move->save();

                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");                        
                            // $man_approval->save(); 
                        }
                        elseif(TRIM($emp_move->ApprovedBy) == TRIM($man_approval->RequestingID) && TRIM($emp_move->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {
                                $emp_move->ApprovedStatus      = "Yes";
                                $man_approval->ApprovalValue    = "Yes";
                            
                            }
                            else 
                            {
                                $emp_move->ApprovedStatus       = "No";
                                $man_approval->ApprovalValue    = "No";
                            }

                            EmployeeMovement::where('RowID', $man_approval->rIdentityValue)->update([
                                'ApprovedStatus'=>$emp_move->ApprovedStatus,
                                'ApprovalStatus'=>$request->ApprovalStatus
                            ]);

                            EmployeeMovementDetail::where('RefRowID',$emp_move->RowID)->update([
                                'Status' => $request->ApprovalStatus
                                ]);

                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                'ApprovalStatus' => $request->ApprovalStatus,
                                'ApprovalValue' => $man_approval->ApprovalValue,
                                'ApprovalDate' => Date("Y-m-d"),
                                'Remark' => $request->Remark,
                                'RemarkBy' => $request->EmployeeCode,
                                'RemarkDate' => Date("Y-m-d")
                            ]);

                            // emp movement
                            // $emp_move->ApprovalStatus = $request->ApprovalStatus;
                            // $emp_move->save();
                            
                            // emp movement detail
                            
                        
                            // $emp_movement_detail->Status = "Approved";
                            // $emp_movement_detail->save();
                            // man approval
                            // $man_approval->ApprovalStatus   = $request->ApprovalStatus;
                            // $man_approval->ApprovalDate     = Date("Y-m-d");
                            // $man_approval->Remark           = $request->Remark;
                            // $man_approval->RemarkBy         = $request->EmployeeCode;
                            // $man_approval->RemarkDate       = Date("Y-m-d");                        
                            // $man_approval->save();
                        }

                        // elseif(!empty($emp_move->taApprovedBy) && TRIM($emp_move->taApprovedBy) == TRIM($man_approval->RequestingID) && TRIM($emp_move->RowID) == TRIM($man_approval->rIdentityValue))
                        // {
                        //     if($request->ApprovalStatus == "Approved")
                        //     {
                        //         $emp_move->taApprovedStatus      = "Yes";
                        //         $man_approval->ApprovalValue    = "Yes";
                            
                        //     }
                        //     else 
                        //     {
                        //         $emp_move->taApprovedStatus       = "No";
                        //         $man_approval->ApprovalValue    = "No";
                        //     }

                        //     EmployeeMovement::where('RowID', $man_approval->rIdentityValue)->update([
                        //         'taApprovedStatus'=> $emp_move->taApprovedStatus,
                        //         'taApprovalStatus'=>$request->ApprovalStatus
                        //     ]);


                        //     ManApproval::where('RowId', '=' ,$request->RowID)->update([
                        //         'ApprovalStatus' => $request->ApprovalStatus,
                        //         'ApprovalValue' => $man_approval->ApprovalValue,
                        //         'ApprovalDate' => Date("Y-m-d"),
                        //         'Remark' => $request->Remark,
                        //         'RemarkBy' => $request->EmployeeCode,
                        //         'RemarkDate' => Date("Y-m-d")

                           
                        // }

                        if($request->ApprovalStatus != "Approved")
                        {
                            EmployeeMovementDetail::where('RefRowId',$emp_move->RowID)->delete();
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);
                    }

                    // White_Voucher

                    elseif($request->TypeKey == "White_Voucher")
                    {
                        $voucher_applist = WhiteVoucherListApp::where('RowID', $man_approval->rIdentityValue)->firstOrFail();
                        $approvalid_lists = explode(',',$voucher_applist->ApprovalIDs);
                        $approvalValue_lists = explode(',',$voucher_applist->ApprovalValue);
                        $approval_id_length = count($approvalid_lists) - 1 ;
                        if(in_array(TRIM($man_approval->RequestingID),$approvalid_lists))
                        { 
                        foreach($approvalid_lists as $key => $apprpvaid_list)
                        { 
                            if($apprpvaid_list == TRIM($man_approval->RequestingID))
                                {
                                    if($request->ApprovalStatus == "Approved")
                                    {
                                        if($key < $approval_id_length )
                                        {
                                            $RequestingID =  $approvalid_lists[$key+1];
                                            $finalapproval = "";
                                            $finalremark = "";
                                            $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn="GoAppReq",$rAppovalVColumn="GoAppVal",$rApprovalStatus="SalesGoFlag",$ApprovalStatus="Pending",$ApprovalValue="No");
                                        }
                                        else
                                        {
                                            $finalapproval = $request->ApprovalStatus;
                                            $finalremark = $request->Remark;
                                        }


                                        $approvalValue_lists[$key] = "Yes";
                                        $man_approval->ApprovalValue   = "Yes";
                                        
                                        
                                    }
                                    else 
                                    {
                                        if($key < $approval_id_length )
                                        {
                                            $finalapproval = "";
                                            $finalremark = "";
                                        }
                                        else 
                                        {
                                            $finalapproval = $request->ApprovalStatus;
                                            $finalremark = $request->Remark;
                                        }

                                        $approvalValue_lists[$key] = "No";
                                        $man_approval->ApprovalValue    = "No";
                                    }
                                    
                                    WhiteVoucherListApp::where('RowID', $man_approval->rIdentityValue)->update([
                                        'ApprovalValue' => implode(",",$approvalValue_lists),
                                        'FinalApproval' =>  $finalapproval,
                                        'FinalRemark' => $finalremark
                                    ]);

                                    ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                        'ApprovalStatus' => $request->ApprovalStatus,
                                        'ApprovalValue' => $man_approval->ApprovalValue,
                                        'ApprovalDate' => Date("Y-m-d"),
                                        'Remark' => $request->Remark,
                                        'RemarkBy' => $request->EmployeeCode,
                                        'RemarkDate' => Date("Y-m-d")
                                    ]);
                                    break;
                                }
                        }

                        if($request->ApprovalStatus != "Approved")
                            {
                                // EmpLeaveDetail::where('RefRowId',$emp_leave->RowID)->delete();
                            
                                return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                            }

                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);
                        
                        }
                        else 
                        {
                            return response()->json(['code'=>404,'message' => 'Approval Not Found!'],404);
                        }

                    }
                    elseif($request->TypeKey == "Vehicle")
                    {
                        $vehicle = AdminVehiclesRequisition::where('RowID', $man_approval->rIdentityValue)->firstOrFail();

                        if(TRIM($vehicle->AppBy) == TRIM($man_approval->RequestingID) && TRIM($vehicle->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {
                                $vehicle->AppState = "Yes";
                                $vehicle->AppStatus = "Approved";
                                $vehicle->AppDate = date('Y-m-d');
                                $man_approval->ApprovalValue    = "Yes";
                                $RequestingID =  '02016';
                                $this->new_entry_insert($man_approval,$RequestingID,$rAppVColumn="AssignedBy",$rAppovalVColumn="AppStateA",$rApprovalStatus="AppStatusA",$ApprovalStatus="Pending",$ApprovalValue="No",$TypeKey = "VehicleAdmin");
                            }
                            else 
                            {
                                $vehicle->AppState = "No";
                                $vehicle->AppStatus = "Rejected";
                                $vehicle->AppDate = date('Y-m-d');
                                $man_approval->ApprovalValue    = "No";
                            }
                            
                            
                        
                            AdminVehiclesRequisition::where('RowID', $vehicle->RowID)->update([
                                    'AppState'=>$vehicle->AppState,
                                    'AppDate'=>$vehicle->AppDate,
                                    'AppStatus'=>$vehicle->AppStatus
                            ]);
                            ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                'ApprovalStatus' => $request->ApprovalStatus,
                                'ApprovalValue' => $man_approval->ApprovalValue,
                                'ApprovalDate' => Date("Y-m-d"),
                                'Remark' => $request->Remark,
                                'RemarkBy' => $request->EmployeeCode,
                                'RemarkDate' => Date("Y-m-d")
                            ]);

                        }


                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);


                    }
                    else if($request->TypeKey == "VehicleAdmin")
                    {
                        $vehicle = AdminVehiclesRequisition::where('RowID', $man_approval->rIdentityValue)->firstOrFail();
                        if(TRIM($vehicle->AssignedBy) == TRIM($man_approval->RequestingID) && TRIM($vehicle->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {


                                $requestionData[] = [
                                    'AppStateA' => "Yes",
                                    'AppStatusA' => "Approved",
                                    'DriverID' => $request->DriverID,
                                    'DMobile' => $request->DMobile,
                                    'VNo' => $request->VNo,
                                    'UseStatus' => 'Assigned',
                                    'AssignedBy' => $request->EmployeeCode,
                                    'AssignedDate' => $request->AssignedDate,
                                    'AssignTime' => $request->AssignTime,
                                    'AssignedUpto' => $request->AssignedUpto,
                                    'AssignUptoTime' => $request->AssignUptoTime
                                ];

                                $man_approval->ApprovalValue    = "Yes";

                                $assignment_log = new AdminVehiclesAssignmentLog();

        
                                $assignment_log->RefRowID  = $vehicle->RowID;
                                $assignment_log->DriverID  = $request->DriverID;
                                $assignment_log->VNo  = $request->VNo;
                                $assignment_log->UseStatus  = 'Assigned';
                                $assignment_log->AssignedBy  = $request->EmployeeCode;
                                $assignment_log->AssignedDate  = $request->AssignedDate;
                                $assignment_log->AssignTime  = $request->AssignTime;
                                $assignment_log->AssignedUpto  = $request->AssignedUpto;
                                $assignment_log->AssignUptoTime  = $request->AssignUptoTime;
                                $assignment_log->EntryBy  = $request->EmployeeCode;
                                $assignment_log->EntryDate  = date('Y-m-d');
                                $assignment_log->save();
                               
                            }
                            else 
                            {
                                $requestionData[] = [
                                    'AppStateA' => "No",
                                    'AppStatusA' => "Rejected",
                                    'AssignedBy' => $request->EmployeeCode,
                                    'AssignedDate' => $request->AssignedDate,
                                    'AssignTime' => $request->AssignTime
                                ];

                                $man_approval->ApprovalValue    = "No";
                            }
                            
                            
                        
                            AdminVehiclesRequisition::where('RowID', $vehicle->RowID)->update($requestionData);
                            ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                'ApprovalStatus' => $request->ApprovalStatus,
                                'ApprovalValue' => $man_approval->ApprovalValue,
                                'ApprovalDate' => Date("Y-m-d"),
                                'Remark' => $request->Remark,
                                'RemarkBy' => $request->EmployeeCode,
                                'RemarkDate' => Date("Y-m-d")
                            ]);

                        }

                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);

                    }
                    else if($request->TypeKey == "'StationeryRequisition'")
                    {
                        $stationary = AdminOfficeStationeryRequisition::where('RefNo', $man_approval->rIdentityValue)->firstOrFail();
                        if(TRIM($stationary->ApprovalRequest) == TRIM($man_approval->RequestingID) && TRIM($stationary->RefNo) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {


                                $requestionData[] = [
                                    'ApprovalStatus' => "Yes",
                                    'GoStatus' => "Approved"
                                ];

                                $man_approval->ApprovalValue    = "Yes";

                                
                               
                            }
                            else 
                            {
                                $requestionData[] = [
                                    'ApprovalStatus' => "No",
                                    'GoStatus' => "Rejected"
                                ];

                                $man_approval->ApprovalValue    = "No";
                            }
                            
                            
                        
                            AdminOfficeStationeryRequisition::where('RefNo', $stationary->RefNo)->update($requestionData);
                            ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                'ApprovalStatus' => $request->ApprovalStatus,
                                'ApprovalValue' => $man_approval->ApprovalValue,
                                'ApprovalDate' => Date("Y-m-d"),
                                'Remark' => $request->Remark,
                                'RemarkBy' => $request->EmployeeCode,
                                'RemarkDate' => Date("Y-m-d")
                            ]);

                        }

                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);

                    }
                    elseif($request->TypeKey == "MPR_BOQ")
                    {
                        $MprBoq = AptCnbBoqMpr::where('RowID', $man_approval->rIdentityValue)->firstOrFail();

                        if(in_array(TRIM($MprBoq->PROINCID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_Inventory';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }



                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);


                            }
                            else 
                            {


                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                             


                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => "No",
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);
                            }
                            
                            
                        



                        }
                        else if(in_array(TRIM($MprBoq->HOInvID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_Concern_Department';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                            }
                            
                            
                        


                        }
                        else if(in_array(TRIM($MprBoq->ConcernDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_BOQ_Dep';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->BOQDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                                                        if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_EnC';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->EncHead), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";


                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        


                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);


                    }
                    elseif($request->TypeKey == "MPR_BOQ_ROD")
                    {
                        $MprBoq = AptCnbBoqMpr::where('RowID', $man_approval->rIdentityValue)->firstOrFail();

                        if(in_array(TRIM($MprBoq->PROINCID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ_ROD';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_Inventory';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }



                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);


                            }
                            else 
                            {


                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                             


                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => "No",
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);
                            }
                            
                            
                        



                        }
                        else if(in_array(TRIM($MprBoq->HOInvID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ_ROD';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_Concern_Department';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                            }
                            
                            
                        


                        }
                        else if(in_array(TRIM($MprBoq->ConcernDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ_ROD';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_BOQ_Dep';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->BOQDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ_ROD';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_EnC';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->EncHead), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";


                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        


                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);


                    }
                    elseif($request->TypeKey == "BOQ-S")
                    {
                        $MprBoq = AptCnbBoqMpr::where('RowID', $man_approval->rIdentityValue)->firstOrFail();

                        if(in_array(TRIM($MprBoq->PROINCID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'BOQ-S';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_Inventory_Support';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ_Support')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'MPRBalance' => DB::raw('MPRBalance - '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance - '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }



                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);


                            }
                            else 
                            {


                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ_Support')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                             


                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => "No",
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'PROINCID'=>$request->EmployeeCode,
                                        'PROINCStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'PROINCDate'=>Date("Y-m-d"),
                                        'PROINCRemark'=>$request->CRemark
                                ]);
                            }
                            
                            
                        



                        }
                        else if(in_array(TRIM($MprBoq->HOInvID), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_Concern_Department';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'HOInvID'=>$request->EmployeeCode,
                                        'HOInvStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'HOInvDate'=>Date("Y-m-d"),
                                        'HOInvRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                            }
                            
                            
                        


                        }
                        else if(in_array(TRIM($MprBoq->ConcernDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_BOQ_Dep';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'ConcernDept'=>$request->EmployeeCode,
                                        'ConcernDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'ConcernDeptDate'=>Date("Y-m-d"),
                                        'ConcernDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->BOQDept), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                                                        if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";

                                /*insert man app approval*/
                                $data = [];
                                $data['SetIdentificationKey'] = $request->EmployeeCode.':'.date('Y-m-d:H:i:s');
                                $data['AppType'] = 'Any';
                                $data['TypeKey'] = 'MPR_BOQ';
                                $data['RequestDate'] = date('Y-m-d');
                                $data['RequestingID'] = $request->ApprovingIDs;
                                $data['rTopic'] = 'MPR_HO_EnC';
                                $data['rTable'] = 'dbo.APT_CNB_BOQ_MPR';
                                $data['MessageDetail'] = $request->MessageDetail;
                                $data['rIdentityColumn'] = 'RowID';
                                $data['rIdentityValue'] = TRIM($MprBoq->RowID);
                                $data['rAppVColumn'] = 'ApprovedBy';
                                $data['rAppovalVColumn'] = 'ApprovedStatus';
                                $data['rApprovalStatus'] = 'ApprovalStatus';
                                $data['EntryBy'] = $request->EmployeeCode;
                                $data['EntryDate'] = date('Y-m-d');


                                CrudModel::save('MAN_Approval', $data);

                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'BOQDept'=>$request->EmployeeCode,
                                        'BOQDeptStatus'=>$request->ApprovalStatus,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'BOQDeptDate'=>Date("Y-m-d"),
                                        'BOQDeptRemark'=>$request->CRemark
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        else if(in_array(TRIM($MprBoq->EncHead), explode(",", TRIM($man_approval->RequestingID)))  && TRIM($MprBoq->RowID) == TRIM($man_approval->rIdentityValue))
                        {
                            if($request->ApprovalStatus == "Approved")
                            {

                                $man_approval->ApprovalValue    = "Yes";


                                foreach ($request->MPRQty as $key => $qty) 
                                {
                                    $dData = [];
                                    $dData['MPRQty'] = $qty;
                                    $dData['EntryBy'] = $request->EmployeeCode;
                                    $dData['ItemBrand'] = $request->ItemBrand[$key];
                                    $dData['ItemSpec'] = $request->ItemSpec[$key];
                                    $dData['ItemRemark'] = $request->ItemRemark[$key];
                                    $dData['ItemVendorCode'] = $request->ItemVendorCode[$key];
                                    CrudModel::update('APT_CNB_BOQ_MPR_Details', $dData, ['RowID' => $request->DRowID[$key]]);
                                }

                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            if($qty != $request->oldMPRQty[$key])
                                            {

                                                $netQty = $qty - $request->oldMPRQty[$key];
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRQty' => DB::raw('MPRQty + '.$netQty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty + '.$netQty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$netQty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            }
                                        }


                                    }

                                }

                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'Remark' => $request->Remark,
                                    'RemarkBy' => $request->EmployeeCode,
                                    'RemarkDate' => Date("Y-m-d")
                                ]);


                            }
                            else 
                            {

                                $man_approval->ApprovalValue    = "No";
                                AptCnbBoqMpr::where('RowID', $vehicle->RowID)->update([
                                        'EncHead'=>$request->EmployeeCode,
                                        'EncHeadStatus'=>$request->ApprovalStatus,
                                        'EncHeadDate'=>Date("Y-m-d"),
                                        'EnCHeadRemark'=>$request->CRemark,
                                        'MPRStatus'=>$request->ApprovalStatus,
                                        'UpdateBy'=>$request->EmployeeCode,
                                        'UpdateDate'=>Date("Y-m-d")
                                ]);
                                ManApproval::where('RowId', '=' ,$request->RowID)->update([
                                    'ApprovalStatus' => $request->ApprovalStatus,
                                    'ApprovalValue' => $man_approval->ApprovalValue,
                                    'ApprovalDate' => Date("Y-m-d"),
                                    'RejectionRemark' => $request->Remark,
                                    'RejectionRemarkBy' => $request->EmployeeCode,
                                    'RejectionRemarkDate' => Date("Y-m-d")
                                ]);
                                if($request->ProjectBOQ == 'Y')
                                {
                                    if($request->MPRType == 'BOQ' || $request->MPRType == 'BOQ-R')
                                    {
                                        foreach ($request->MPRQty as $key => $qty) 
                                        {
                                            

                                                
                                                DB::table('APT_CNB_BOQ')
                                                   ->where('Project_Code', $request->Project_Code)
                                                   ->where('JOBID', $request->JOBID[$key])
                                                   ->where('ItemID', $request->ItemID[$key])
                                                   ->where('ItemCode', $request->ItemCode[$key])
                                                   ->where('ItemType ', $request->ItemType[$key])
                                                   ->where('ProfileStatus ', 'Active')
                                                   ->update([
                                                       'MPRCancelQty' => DB::raw('MPRCancelQty + '.$qty),
                                                       'NetMPRQty' => DB::raw('NetMPRQty - '.$qty),
                                                       'MPRBalance' => DB::raw('MPRBalance + '.$qty),
                                                       'NetMPRBalance' => DB::raw('NetMPRBalance + '.$qty),
                                                       'UpdateBy' => $request->EmployeeCode,
                                                       'UpdateDate' => date('Y-m-d')
                                                ]);
                                            
                                        }


                                    }

                                }
                            }
                            
                            
                        

                            
                        }
                        


                        if($request->ApprovalStatus != "Approved")
                        {
                            
                        
                            return response()->json(['code'=>200,'message' => 'Approval Reject Successfully'],200);
                        }

                
                        return response()->json(['code'=>200,'message' => 'Approval Updated Successfully'],200);


                    }
                    

                } 
                catch (\Exception $e) 
                {

                    return response()->json(['code'=>404,'message' => 'Approval Not Updated!'],404);
                }
            }

            else
            {
                auth()->logout();
                return response()->json(['message' => 'Unauthorized'], 401);
            }

        }

        else
        {
            auth()->logout();
            return response()->json(['message' => 'Unauthorized'], 401);
        }



       
    }

    public function new_entry_insert($man_approval,$RequestingID,$rAppVColumn,$rAppovalVColumn,$rApprovalStatus,$ApprovalStatus,$ApprovalValue,$TypeKey=null)

    {
        $new_approval_enty = new ManApproval();
        if($TypeKey == null)
        {
            $new_approval_enty->TypeKey  = $man_approval->TypeKey;
        }
        else
        {
            $new_approval_enty->TypeKey  = $TypeKey;
        }
        
        $new_approval_enty->SetIdentificationKey  = $man_approval->SetIdentificationKey;
        $new_approval_enty->AppType  = $man_approval->AppType;
        $new_approval_enty->RequestDate  = $man_approval->RequestDate;
        $new_approval_enty->RequestingID  = $RequestingID;
        $new_approval_enty->rTopic  = $man_approval->rTopic;
        $new_approval_enty->rTable  = $man_approval->rTable;
        $new_approval_enty->rIdentityColumn  = $man_approval->rIdentityColumn;
        $new_approval_enty->rIdentityValue  = $man_approval->rIdentityValue;
        $new_approval_enty->MessageDetail  = $man_approval->MessageDetail;
        $new_approval_enty->rAppVColumn  = $rAppVColumn;
        $new_approval_enty->rAppovalVColumn  = $rAppovalVColumn;
        $new_approval_enty->rApprovalStatus  = $rApprovalStatus;
        $new_approval_enty->ApprovalStatus  = $ApprovalStatus;
        $new_approval_enty->ApprovalValue  = $ApprovalValue;
        $new_approval_enty->EntryBy  = $man_approval->EntryBy;
        $new_approval_enty->EntryDate  = $man_approval->EntryDate;
        $new_approval_enty->save();
        
    }


    public function TypeDetail($keytype,$RowID,$EmployeeCode)
    {
        // HR Leave 
        if($keytype == "HR-Leave")
        {
            $application_detail = EmpLeave::where('RowID',$RowID)->select('RowID','EmployeeCode','DayCount','LeaveDate as Date','ApprovalStatusJT','ApprovalStatusSv','ApprovalStatusHOD','ApprovalStatus','Leave_Type as Type','LeaveDetail as Detail')->first();



            if(!empty($application_detail))
            {

                if(TRIM($application_detail->ApprovalStatus) == 'Approved')
                {
                    $status = 'Approved';
                }
                elseif(TRIM($application_detail->ApprovalStatusSv) == 'Rejected' || $TRIM($application_detail->ApprovalStatusHOD) == 'Rejected' || TRIM($application_detail->ApprovalStatus) == 'Rejected')
                {
                    $status = 'Rejected';
                }
                elseif(TRIM($application_detail->ApprovalStatusSv) == 'Pending' || TRIM($application_detail->ApprovalStatusHOD) == 'Pending' || TRIM($application_detail->ApprovalStatus) == 'Pending')
                {
                    $status = 'Pending';
                }

                $application_detail['Status'] = $status;

                $user_info = User::where('EmployeeCode',TRIM($application_detail->EmployeeCode))
            ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
            if(!empty($user_info))
            {
                $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                    ->select('HRTitle')->first();
                if(!empty($dept)){
                    $user_info['Department'] =  $dept->HRTitle;
                }else{
                    $user_info['Department'] =  "";
                }    
                
                $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                $application_detail['user_info'] =  $user_info;
            }
            else 
            {
                $application_detail['user_info'] = [];
            }
            
            $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;
        }

        // // HR Movement

        elseif($keytype == "HR-Movement")
        {
            $application_detail = EmployeeMovement::where('RowID',$RowID)->select('RowID','EmployeeCode','DayCount','NoteDate as Date','ApprovalStatusd','ApprovalStatus','AttendanceStatus as Type','NoteDetail as Detail','StartPoint','EndPoint','tMedia as media','taBill as amount')->first();
          
            if(!empty($application_detail))
            {

                if(TRIM($application_detail->ApprovalStatus) == 'Approved')
                {
                    $status = 'Approved';
                }
                elseif(TRIM($application_detail->ApprovalStatusd) == 'Rejected' || TRIM($application_detail->ApprovalStatus) == 'Rejected')
                {
                    $status = 'Rejected';
                }
                elseif(TRIM($application_detail->ApprovalStatusd) == 'Pending' || TRIM($application_detail->ApprovalStatus) == 'Pending')
                {
                    $status = 'Pending';
                }
                $application_detail['Status'] = $status;
                $current_date = Date('Y-m-d');
                $attendance_info =   DB::connection('sqlsrv2')
                                ->table('tEnter')
                                ->where("C_Unique", TRIM($application_detail->EmployeeCode))
                                ->Join('tTerminal','tEnter.L_TID','=','L_ID')
                                ->groupBy('C_Date','C_Unique','L_TID','tTerminal.C_Name','C_Card')
                                ->select('tTerminal.C_Name','C_Date',\DB::raw("MIN(C_Time) AS I_Time"), \DB::raw("MAX(C_Time) AS O_Time"),'C_Card','C_Unique','L_TID',)
                                ->orderby('C_Date','DESC')
                                ->take(1)
                                ->get();
                                
                $attendance =  [];
                $attendance['C_Unique'] = $attendance_info[0]->C_Unique;
                $attendance['C_Date'] =  substr($attendance_info[0]->C_Date,0,4).'-'. substr($attendance_info[0]->C_Date,-4,2) . '-'. substr($attendance_info[0]->C_Date,-2,2);
                $attendance['I_Time'] =  substr($attendance_info[0]->I_Time,0,2).'-'. substr($attendance_info[0]->I_Time,-4,2) . '-'. substr($attendance_info[0]->I_Time,-2,2);
                $attendance['O_Time'] =  substr($attendance_info[0]->O_Time,0,2).'-'. substr($attendance_info[0]->O_Time,-4,2) . '-'. substr($attendance_info[0]->O_Time,-2,2);
                $attendance['L_TID'] = $attendance_info[0]->L_TID;
                $attendance['C_Name'] = $attendance_info[0]->C_Name;
                if(!empty($attendance_info[0]->C_Card))
                {
                    $attendance['C_Card'] = 'http://205.188.5.54/images/icons/Card%20(3).png';
                }
                else
                {
                    $attendance['C_Card'] = 'http://205.188.5.54/images/icons/finger.png'; 
                }
                
                
                
            $application_detail['attendance_info'] = $attendance;

            $user_info = User::where('EmployeeCode',TRIM($application_detail->EmployeeCode))
            ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();

                if(!empty($user_info))
                {
                    $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                        ->select('HRTitle')->first();
                    $user_info['Department'] =  $dept->HRTitle;
                    $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                    $application_detail['user_info'] =  $user_info;
                }
                else 
                {
                    $application_detail['user_info'] = [];
                }
            
            // $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')
            // ->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
            
            }
            else 
            {
                $application_detail = [];
            }
            
            return $application_detail;
        }

        // white voucher
        elseif($keytype == "White_Voucher")

        {
            $application_detail = WhiteVoucherListApp::where('RowID',$RowID)->select('RowID','IssueFor as EmployeeCode','VrNo','VrDate as Date','_Type as Type','VrAmount','Ratio','VrCategory','SBU','Department','Project_Code','SaveStatus','SaveStatus','ApprovalValue','FinalApproval')->first();
           
            if(!empty($application_detail))
            {
                $SBU = UCompanies::where('CompanyID', $application_detail->SBU)->select('CompanyName')->first();
                $Department = User::where('HRCode', $application_detail->Department)->select('HRTitle')->first();
                $project = AptProject::where('Project_Code',$application_detail->Project_Code)->select('ProjectName')->first();
                $detail = WhiteVoucherApt::where('VrNo',$application_detail->VrNo)->select('SubCategory','Particulars','MyNote','Amount')->first();
                
                $voucher_detail['SBU'] = $SBU ? $SBU->CompanyName : '';
                $voucher_detail['Department'] = $Department ? $Department->HRTitle : '' ;
                $voucher_detail['ProjectName'] =  $project ? $project->ProjectName : '' ;
                $voucher_detail['SubCategory'] =  $detail->SubCategory;
                $voucher_detail['Particulars'] =   $detail->Particulars;
                $voucher_detail['MyNote'] =   $detail->MyNote;

                $application_detail['voucher_info'] = $voucher_detail;

                $user_info = User::where('EmployeeCode',TRIM($application_detail->EmployeeCode))
                 ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();

                if(!empty($user_info))
                {
                    $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                        ->select('HRTitle')->first();
                    $user_info['Department'] =  $dept->HRTitle;
                    $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                    $application_detail['user_info'] =  $user_info;
                }
                else 
                {
                    $application_detail['user_info'] = NULL;
                }
               
                $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')
                                                                ->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
                
            }
            else 
            {
                $application_detail = [];
            }

            return $application_detail;
        }
        else if($keytype == "Vehicle")
        {

            $application_detail = AdminVehiclesRequisition::where('RowID',$RowID)->select('RowID','EmployeeCode','MobileNo','VisitType','Seats','StartFrom','Destination','Purpose', 'DVS', 'TVS', 'DVE', 'TVE','AppBy', 'AppStatus', 'EntryBy', 'EntryDate', 'DateofIssue', 'IssueTime', 'AppMobile')->first();

            if(!empty($application_detail))
            {
                $user_info = User::where('EmployeeCode',TRIM($application_detail->EmployeeCode))
            ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
            if(!empty($user_info))
            {
                $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                    ->select('HRTitle')->first();
                if(!empty($dept)){
                    $user_info['Department'] =  $dept->HRTitle;
                }else{
                    $user_info['Department'] =  "";
                }    
                
                $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                $application_detail['user_info'] =  $user_info;
            }
            else 
            {
                $application_detail['user_info'] = [];
            }
            
            $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')
                                                                ->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
        else if($keytype == "VehicleAdmin")
        {

            $application_detail = AdminVehiclesRequisition::where('RowID',$RowID)->select('RowID','EmployeeCode','MobileNo','VisitType','Seats','StartFrom','Destination','Purpose', 'DVS', 'TVS', 'DVE', 'TVE','AppBy', 'AppStatus', 'EntryBy', 'EntryDate', 'DateofIssue', 'IssueTime', 'AppMobile')->first();

            if(!empty($application_detail))
            {
                $user_info = User::where('EmployeeCode',TRIM($application_detail->EmployeeCode))
            ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
            if(!empty($user_info))
            {
                $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                    ->select('HRTitle')->first();
                if(!empty($dept)){
                    $user_info['Department'] =  $dept->HRTitle;
                }else{
                    $user_info['Department'] =  "";
                }    
                
                $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                $application_detail['user_info'] =  $user_info;
            }
            else 
            {
                $application_detail['user_info'] = [];
            }
            
            $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
        elseif($keytype == "StationeryRequisition")
        {
           

            $application_detail = AdminOfficeStationeryRequisition::where('RowID',$RowID)->select('Remark', 'ApprovalRequest', 'ApprovalStatus', 'RequisitorID', 'RequisitionDate', 'RefNo','EntryBy', 'EntryDate', 'GoStatus as FinalApproval')->first();

            if(!empty($application_detail))
            {
                 $details = JoinModel::findStationaryDetailsInfo($application_detail->RefNo);

                 $application_detail['details'] =  $details;
                $user_info = User::where('EmployeeCode',TRIM($application_detail->RequisitorID))
            ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
            if(!empty($user_info))
            {
                $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                    ->select('HRTitle')->first();
                if(!empty($dept)){
                    $user_info['Department'] =  $dept->HRTitle;
                }else{
                    $user_info['Department'] =  "";
                }    
                
                $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                $application_detail['user_info'] =  $user_info;
            }
            else 
            {
                $application_detail['user_info'] = [];
            }
            
            $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$application_detail->RefNo)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
        else if($keytype == "MPR_BOQ")
        {

            $application_detail = AptCnbBoqMpr::where('RowID',$RowID)->select('APT_CNB_BOQ_MPR.*')->first();

            if(!empty($application_detail))
            {
            
                $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
              
                $application_detail['project'] = JoinModel::findProject($application_detail->Project_Code);

                $application_detail['details'] = JoinModel::findAptCnbBoqMpr($RowID);
                
                $application_detail['product_details'] = JoinModel::findMprProductDetails($RowID);
               


                if(TRIM($application_detail->PROINCStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {


                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal1();

                }
                else if(TRIM($application_detail->HOInvStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                    
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal2();

                    $user_info = User::where('EmployeeCode',TRIM($application_detail->EntryBy))
                    ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
                    if(!empty($user_info))
                    {
                        $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                            ->select('HRTitle')->first();
                        if(!empty($dept)){
                            $user_info['Department'] =  $dept->HRTitle;
                        }else{
                            $user_info['Department'] =  "";
                        }    
                        
                        $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                        $application_detail['user_info'] =  $user_info;
                    }
                }
                else if(TRIM($application_detail->ConcernDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                    
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal3();
                    
                }
                else if(TRIM($application_detail->BOQDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                    
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal4();
                    
                }
                else if(TRIM($application_detail->MPRStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {

                    $application_detail['approval_details'] = JoinModel::findApprovalStatusDetails($RowID);
                    
                }
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
        else if($keytype == "MPR_BOQ_ROD")
        {

            $application_detail = AptCnbBoqMpr::where('RowID',$RowID)->select('APT_CNB_BOQ_MPR.*')->first();

            if(!empty($application_detail))
            {
            
                $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();

                $application_detail['project'] = JoinModel::findProject($application_detail->Project_Code);

                $application_detail['details'] = JoinModel::findAptCnbBoqMpr($RowID);
               
                $application_detail['product_details'] = JoinModel::findMprProductDetailsRod($RowID);
               

                if(TRIM($application_detail->PROINCStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {

                    

                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal1();

                }
                else if(TRIM($application_detail->HOInvStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                   
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal2();

                    $user_info = User::where('EmployeeCode',TRIM($application_detail->EntryBy))
                    ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
                    if(!empty($user_info))
                    {
                        $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                            ->select('HRTitle')->first();
                        if(!empty($dept)){
                            $user_info['Department'] =  $dept->HRTitle;
                        }else{
                            $user_info['Department'] =  "";
                        }    
                        
                        $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                        $application_detail['user_info'] =  $user_info;
                    }
                }
                else if(TRIM($application_detail->ConcernDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                   
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal3();
                    
                }
                else if(TRIM($application_detail->BOQDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                    
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal4();
                    
                }
                else if(TRIM($application_detail->MPRStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {

                    $application_detail['approval_details'] = JoinModel::findApprovalStatusDetails($RowID);
                    
                }
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
        else if($keytype == "BOQ-S")
        {
            $application_detail = AptCnbBoqMpr::where('RowID',$RowID)->select('APT_CNB_BOQ_MPR.*')->first();

            if(!empty($application_detail))
            {
            
                $application_detail['authority_approval'] =  ManApproval::Join('AMG_HR','MAN_Approval.RequestingID','=','AMG_HR.EmployeeCode')->where('rIdentityValue',$RowID)->where('TypeKey',$keytype)->select('MAN_Approval.RowID','AMG_HR.EmployeeCode','AMG_HR.HRTitle as name','MAN_Approval.ApprovalStatus','MAN_Approval.ApprovalDate')->orderBy('RowID','ASC')->get();
                
                $application_detail['project'] = JoinModel::findProject($application_detail->Project_Code);

                $application_detail['details'] = JoinModel::findAptCnbBoqMpr($RowID);

                if($application_detail->JobCode == '200')
                {
                    $application_detail['product_details'] = JoinModel::findMprProductDetailsRod($RowID);
                }
                else
                {
                    $application_detail['product_details'] = JoinModel::findMprProductDetails($RowID);
                }
               
               

                if(TRIM($application_detail->PROINCStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {

                    

                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal1();

                }
                else if(TRIM($application_detail->HOInvStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                   
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal2();

                    $user_info = User::where('EmployeeCode',TRIM($application_detail->EntryBy))
                    ->select('EmployeeCode','HRTitle as name','Designation','HRCode')->first();
                    if(!empty($user_info))
                    {
                        $dept =User::where('HRCode',substr($user_info->HRCode, 0, 7))
                            ->select('HRTitle')->first();
                        if(!empty($dept)){
                            $user_info['Department'] =  $dept->HRTitle;
                        }else{
                            $user_info['Department'] =  "";
                        }    
                        
                        $user_info['image'] = 'http://205.188.5.54:92/images/uploads/members/'.TRIM($user_info->EmployeeCode).'.'.'jpg';
                        $application_detail['user_info'] =  $user_info;
                    }
                }
                else if(TRIM($application_detail->ConcernDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                   
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal3();
                    
                }
                else if(TRIM($application_detail->BOQDeptStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {
                    
                    $application_detail['ApprovingIDs'] = JoinModel::findApprovalMatrixVal4();
                    
                }
                else if(TRIM($application_detail->MPRStatus) == 'Pending' && TRIM($application_detail->MPRStatus) == 'Pending')
                {

                    $application_detail['approval_details'] = JoinModel::findApprovalStatusDetails($RowID);
                    
                }
            
            }
            else
            {
                $application_detail = [];
            }
            
            return $application_detail;

        }
    }

    public function AllCarList()
    {

        $data['AllCarList'] = JoinModel::findAllCarList();

        return response()->json(['code' => 200,'data' => $data],200);
    }

    public function carLog($vno)
    {

        $data['carLog'] = JoinModel::findCarLog($vno);

        return response()->json(['code' => 200,'data' => $data],200);
    }

    public function driverInfo($emp_code)
    {

        $data['driverInfo'] = JoinModel::findDriverInfo($emp_code);

        return response()->json(['code' => 200,'data' => $data],200);
    }

}

