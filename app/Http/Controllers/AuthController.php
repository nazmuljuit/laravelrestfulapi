<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\User;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use DB;
// use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        try {

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);

            $user->save();

            //return successful response
            return response()->json(['code' => 201,'user' => $user, 'message' => 'CREATED']);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['code' => 409,'message' => 'User Registration Failed!']);
        }

    }

    public function login(Request $request)
    {
        
       
        $jwt_key = env("JWT_SECRET");

        $client = new Client();
        
        $response = $client->request('POST', 'http://205.188.5.54:92/android/LoginVarify_GET.asp', ['query' => [
            'UID' => $request->uid,
            'PWD' => $request->pwd,
            'RegCode' => $request->regcode,
            'IMEI' => $request->imie,
            'AppFCMToken' => $request->fcmtoken,
        ]]);
     
           //dd($response);  
        $data =  json_decode($response->getBody(), true);
      
        if(!empty($data['LoginVarify'][0]['EmployeeCode']) && $data['LoginVarify'][0]['RegedUser'] == "Y")
        {
            $user_info = [];
            foreach($data['LoginVarify'] as $i => $v)
            {
                $user_info['EmployeeCode'] = $v['EmployeeCode'];
                $user_info['EmployeeName'] = $v['EmployeeName'];
                $user_info['Dept']         = $v['Dept'];
                $user_info['Designation']  = $v['Designation'];
                $user_info['OfficeMobile'] = $v['OfficeMobile'];
                $user_info['Company']      = $v['Company'];
                $user_info['image'] = 'http://205.188.5.54/images/uploads/members/'.TRIM($v['EmployeeCode']).'.'.'jpg';

                       // attendance 

                $EmployeeCode  = TRIM($v['EmployeeCode']);
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
            $user_info['attendance'] = $attendance_status;


            }

                
               
                // return $user_info;
                $this->validate($request, [
                'uid' => 'required',
                'pwd' => 'required',
                'regcode' => 'required',
                'imie' => 'required',

            ]);

            $checkData = User::where('EmployeeCode', $user_info['EmployeeCode'])->where('status','Active')->select('RowID','HRTitle','EmployeeCode','Designation','status')->first();
           
            if($checkData)
            {
                    $token =  Auth::guard('api')->login($checkData);

                    if (!$token) {
                        return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
                    }
                    else
                    {
                        return $this->respondWithToken($token,$user_info);
                    }

            }
            else
            {
                    return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
            }

        }
        else
        {
                return response()->json(['code' => 401,'message' => 'Unauthorized'],401);
        }


            //  $credentials = $request->only(['EmployeeCode']);

            // if (! $token = Auth::attempt($credentials)) {
            //     return response()->json(['message' => 'Unauthorized'], 401);
            // }

            // return $this->respondWithToken($token);



    }

    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }


}
