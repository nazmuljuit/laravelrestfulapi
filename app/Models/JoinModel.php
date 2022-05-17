<?php

namespace App\Models;

use DB;
use Illuminate\Support\Facades\Auth;

class JoinModel
{

    public static function findEmpInfo($emp_code)
    {
        $query = "SELECT dbo.AMG_HR.HRTitle, dbo.AMG_HR.JoiningDate, dbo.AMG_HR.ResignDate, dbo.AMG_HR.FunctionalDesignation AS Designation, AMG_HR_1.HRTitle AS Department, dbo.U_Companies.CompanyName FROM dbo.U_Companies RIGHT OUTER JOIN dbo.AMG_HR AS AMG_HR_1 ON dbo.U_Companies.CompanyID = LEFT(AMG_HR_1.HRCode, 4) RIGHT OUTER JOIN dbo.AMG_HR ON AMG_HR_1.HRCode = dbo.AMG_HR.pHead WHERE (dbo.AMG_HR.EmployeeCode = '".$emp_code."')";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findEmpPresentInfo($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT A.eDate, A.ShiftID, S.ShiftName, A.MC, A.sTime, A.eTime, A.eLateTime, A.cMin, A.hrBuffer, A.pMode, A.InTime, A.InMode, A.OutTime, A.OutMode, A.LateInTime, A.WorkingHour, A.OTHour, A.HDay, A.LeaveType, A.LeaveStatus, A.Movement, A.MovementStatus, A.Exception, A.Status, CASE WHEN A.[Status] = 'Holiday' THEN 1 ELSE 0 END AS cHoliDay, CASE WHEN A.[Status] IN ('InTimePresent') THEN 1 ELSE 0 END AS cIntimePresent, CASE WHEN A.[Status] IN ('InTimePresent', 'LatePresent') THEN 1 ELSE 0 END AS cPresent, CASE WHEN A.[Status] IN ('InTimePresent', 'LatePresent') AND NOT LateInTime = '00:00:00' THEN 1 ELSE 0 END AS cLate, CASE WHEN A.[Status] = 'LatePresent' THEN 1 ELSE 0 END AS cgLatePresent,CASE WHEN A.[Status] = 'Absent' THEN 1 ELSE 0 END AS cAbsent, CASE WHEN A.[Status] = 'Leave' THEN 1 ELSE 0 END AS cLeave FROM TA_AttendanceSummaryDetail_Auto AS A LEFT OUTER JOIN TA_Shift AS S ON A.ShiftID = S.RowID LEFT OUTER JOIN AMG_HR AS H ON A.EmployeeCode = H.EmployeeCode WHERE A.EmployeeCode = '".$emp_code."' AND A.eDate BETWEEN '".$fromdate."' AND '".$toddate."' ORDER BY A.eDate";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findEmpMovementInfo($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT * FROM dbo.TA_EmployeeMovement WHERE EmployeeCode = '".$emp_code."' AND (NoteDate BETWEEN '".$fromdate."' AND '".$toddate."')";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findEmpAbsentInfo($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT A.eDate, A.ShiftID, S.ShiftName, A.MC, A.InTime, A.inMode,A.OutTime,A.OutMode,A.sTime, A.eTime, A.cMin,A.Movement,A.MovementStatus,A.HDay as Holiday,A.LeaveType,A.LeaveStatus,A.Status,A.Exception FROM TA_AttendanceSummaryDetail_Auto AS A LEFT OUTER JOIN TA_Shift AS S ON A.ShiftID = S.RowID LEFT OUTER JOIN AMG_HR AS H ON A.EmployeeCode = H.EmployeeCode WHERE A.EmployeeCode = '".$emp_code."' AND A.eDate BETWEEN '".$fromdate."' AND '".$toddate."' and A.Status='Absent' ORDER BY A.eDate";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findEmpAbsent2Info($emp_code,$fromdate,$toddate)
    {
        $query = "WITH DT AS (SELECT Date FROM dbo.DateSequence('".$fromdate."', '".$toddate."') AS DateSequence_1), EA AS (SELECT e_idno, e_date, InTime, OutTime, sTime, eTime, cMin FROM dbo.InTimeBasic_V5000 WHERE (e_idno = '".$emp_code."') AND e_Date BETWEEN '".$fromdate."' AND '".$toddate."'), EL AS     (SELECT TOP (100) PERCENT dbo.TA_EmpLeave_Detail.RefRowID, dbo.TA_EmpLeave_Detail.EmployeeCode, dbo.TA_EmpLeave_Detail.LeaveDate, dbo.TA_EmpLeave.Leave_Type AS LeaveType   FROM dbo.TA_EmpLeave RIGHT OUTER JOIN                        dbo.TA_EmpLeave_Detail ON dbo.TA_EmpLeave.RowID = dbo.TA_EmpLeave_Detail.RefRowID  WHERE ((dbo.TA_EmpLeave_Detail.LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND dbo.TA_EmpLeave_Detail.EmployeeCode = '".$emp_code."')) , EM AS         (SELECT EmployeeCode, LeaveDate, LeaveDetail        FROM dbo.TA_EmployeeMovement_Detail         WHERE ((LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND EmployeeCode = '".$emp_code."') AND LeaveDetail = 'InTime Present'         GROUP BY EmployeeCode, LeaveDate, LeaveDetail), HD AS (SELECT YearMonth, HDay, HDayType, HolidayName, RowID         FROM dbo.TA_Holiday         WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')), EX AS (SELECT HDay, HDayType   FROM dbo.TA_Exception   WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')  ),               M AS (SELECT Date, A.InTime, A.OutTime, A.sTime, A.eTime, A.cMin, L.LeaveType AS Leave, ISNULL(M.LeaveDetail, '-') AS Movement, H.HDayType AS Holiday, X.HDayType FROM DT LEFT OUTER JOIN EA AS A ON DT.Date = A.e_date LEFT OUTER JOIN EL AS L ON DT.Date = L.LeaveDate LEFT OUTER JOIN EM AS M ON DT.Date = M.LeaveDate LEFT OUTER JOIN HD AS H ON DT.Date = H.HDay LEFT OUTER JOIN EX AS X ON DT.Date = X.HDay), MT AS (SELECT M.Date,           ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':'),108), '') AS InTime,              ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.OutTime, 1, 0, REPLICATE('0', 6 - LEN(M.OutTime))),3,0,':'),6,0,':'),108), '') AS OutTime,  M.sTime, M.eTime, M.cMin,               M.Movement AS Movement, ISNULL(M.Holiday, '-') AS Holiday, ISNULL(M.Leave,'-') AS Leave, CASE                    WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                   ELSE                     CASE                   WHEN DATEDIFF(MINUTE, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) <= 0 THEN 'InTimePresent'                     WHEN M.Movement = 'InTime Present' THEN 'InTimePresent'                     WHEN DATEDIFF(MINUTE, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) > 0 AND NOT M.Movement = 'InTime Present' THEN 'LatePresent'                  WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                    WHEN M.InTime IS NULL AND NOT M.Leave = '-' THEN 'Leave'                    ELSE 'Absent'                   END END AS 'FinalStatus', ISNULL(M.HDayType, '-') AS Exception FROM M) SELECT * FROM MT AS F WHERE FinalStatus='Absent' ORDER BY Date option (maxrecursion 0)";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findEmpLeave1Info($emp_code,$fromdate,$toddate)
    {
        $query = "WITH DT AS (SELECT Date FROM dbo.DateSequence('".$fromdate."', '".$toddate."') AS DateSequence_1), EA AS (SELECT e_idno, e_date, InTime, OutTime, sTime, eTime, cMin FROM dbo.InTimeBasic_V5000 WHERE (e_idno = '".$emp_code."') AND e_Date BETWEEN '".$fromdate."' AND '".$toddate."'), EL AS  (SELECT TOP (100) PERCENT dbo.TA_EmpLeave_Detail.RefRowID, dbo.TA_EmpLeave_Detail.EmployeeCode, dbo.TA_EmpLeave_Detail.LeaveDate, dbo.TA_EmpLeave.Leave_Type AS LeaveType   FROM dbo.TA_EmpLeave RIGHT OUTER JOIN                        dbo.TA_EmpLeave_Detail ON dbo.TA_EmpLeave.RowID = dbo.TA_EmpLeave_Detail.RefRowID  WHERE ((dbo.TA_EmpLeave_Detail.LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND dbo.TA_EmpLeave_Detail.EmployeeCode = '".$emp_code."')) , EM AS         (SELECT EmployeeCode, LeaveDate, LeaveDetail        FROM dbo.TA_EmployeeMovement_Detail         WHERE ((LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND EmployeeCode = '".$emp_code."') AND LeaveDetail = 'InTime Present'         GROUP BY EmployeeCode, LeaveDate, LeaveDetail), HD AS (SELECT YearMonth, HDay, HDayType, HolidayName, RowID         FROM dbo.TA_Holiday         WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')), EX AS (SELECT HDay, HDayType   FROM dbo.TA_Exception   WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')  ),               M AS (SELECT Date, A.InTime, A.OutTime, A.sTime, A.eTime, A.cMin, L.RefRowID, L.LeaveType AS Leave, ISNULL(M.LeaveDetail, '-') AS Movement, H.HDayType AS Holiday, X.HDayType FROM DT LEFT OUTER JOIN EA AS A ON DT.Date = A.e_date LEFT OUTER JOIN EL AS L ON DT.Date = L.LeaveDate LEFT OUTER JOIN EM AS M ON DT.Date = M.LeaveDate LEFT OUTER JOIN HD AS H ON DT.Date = H.HDay LEFT OUTER JOIN EX AS X ON DT.Date = X.HDay), MT AS (SELECT M.Date, M.RefRowID,           ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':'),108), '') AS InTime,              ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.OutTime, 1, 0, REPLICATE('0', 6 - LEN(M.OutTime))),3,0,':'),6,0,':'),108), '') AS OutTime,  M.sTime, M.eTime, M.cMin,               M.Movement AS Movement, ISNULL(M.Holiday, '-') AS Holiday, ISNULL(M.Leave,'-') AS Leave, CASE                    WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                   ELSE                     CASE                   WHEN DATEDIFF(MINUTE, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) <= 0 THEN 'InTimePresent'                     WHEN M.Movement = 'InTime Present' THEN 'InTimePresent'                     WHEN DATEDIFF(MINUTE, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) > 0 AND NOT M.Movement = 'InTime Present' THEN 'LatePresent'                  WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                    WHEN M.InTime IS NULL AND NOT M.Leave = '-' THEN 'Leave'                    ELSE 'Absent'                   END END AS 'FinalStatus', ISNULL(M.HDayType, '-') AS Exception FROM M) SELECT * FROM MT AS F WHERE not Leave='-' ORDER BY Date option (maxrecursion 0)";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findEmpLeave2Info($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT A.eDate, A.ShiftID, S.ShiftName, A.MC, A.InTime, A.inMode,A.OutTime,A.OutMode,A.sTime, A.eTime, A.cMin,A.Movement,A.MovementStatus,A.HDay as Holiday,A.LeaveType,A.LeaveStatus,A.Status,A.Exception FROM TA_AttendanceSummaryDetail_Auto AS A LEFT OUTER JOIN TA_Shift AS S ON A.ShiftID = S.RowID LEFT OUTER JOIN AMG_HR AS H ON A.EmployeeCode = H.EmployeeCode WHERE A.EmployeeCode = '".$emp_code."' AND A.eDate BETWEEN '".$fromdate."' AND '".$toddate."' and A.Status='Leave' ORDER BY A.eDate";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findEmpLate1Info($emp_code,$fromdate,$toddate)
    {
        $query = "WITH DT AS (SELECT Date FROM dbo.DateSequence('".$fromdate."', '".$toddate."') AS DateSequence_1), EA AS (SELECT e_idno, e_date, InTime, OutTime, sTime, eTime, cMin FROM dbo.InTimeBasic_V5000 WHERE (e_idno = '".$emp_code."') AND e_Date BETWEEN '".$fromdate."' AND '".$toddate."'), EL AS    (SELECT TOP (100) PERCENT dbo.TA_EmpLeave_Detail.RefRowID, dbo.TA_EmpLeave_Detail.EmployeeCode, dbo.TA_EmpLeave_Detail.LeaveDate, dbo.TA_EmpLeave.Leave_Type AS LeaveType   FROM dbo.TA_EmpLeave RIGHT OUTER JOIN                        dbo.TA_EmpLeave_Detail ON dbo.TA_EmpLeave.RowID = dbo.TA_EmpLeave_Detail.RefRowID  WHERE ((dbo.TA_EmpLeave_Detail.LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND dbo.TA_EmpLeave_Detail.EmployeeCode = '".$emp_code."')) , EM AS         (SELECT EmployeeCode, LeaveDate, LeaveDetail        FROM dbo.TA_EmployeeMovement_Detail         WHERE ((LeaveDate BETWEEN '".$fromdate."' AND '".$toddate."') AND EmployeeCode = '".$emp_code."') AND LeaveDetail = 'InTime Present'         GROUP BY EmployeeCode, LeaveDate, LeaveDetail), HD AS (SELECT YearMonth, HDay, HDayType, HolidayName, RowID         FROM dbo.TA_Holiday         WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')), EX AS (SELECT HDay, HDayType   FROM dbo.TA_Exception   WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')  ),               M AS (SELECT Date, A.InTime, A.OutTime, A.sTime, A.eTime, A.cMin, L.LeaveType AS Leave, ISNULL(M.LeaveDetail, '-') AS Movement, H.HDayType AS Holiday, X.HDayType FROM DT LEFT OUTER JOIN EA AS A ON DT.Date = A.e_date LEFT OUTER JOIN EL AS L ON DT.Date = L.LeaveDate LEFT OUTER JOIN EM AS M ON DT.Date = M.LeaveDate LEFT OUTER JOIN HD AS H ON DT.Date = H.HDay LEFT OUTER JOIN EX AS X ON DT.Date = X.HDay), MT AS (SELECT M.Date,           ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':'),108), '') AS InTime,              ISNULL(CONVERT(varchar,STUFF(STUFF(STUFF(M.OutTime, 1, 0, REPLICATE('0', 6 - LEN(M.OutTime))),3,0,':'),6,0,':'),108), '') AS OutTime,  M.sTime, M.eTime, M.cMin,               M.Movement AS Movement, ISNULL(M.Holiday, '-') AS Holiday, ISNULL(M.Leave,'-') AS Leave, CASE                    WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                   ELSE                     CASE                   WHEN DATEDIFF(SECOND, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) <= 0 THEN 'InTimePresent'                     WHEN M.Movement = 'InTime Present' THEN 'InTimePresent'                     WHEN DATEDIFF(SECOND, M.cMin, STUFF(STUFF(STUFF(M.InTime, 1, 0, REPLICATE('0', 6 - LEN(M.InTime))),3,0,':'),6,0,':')) > 0 AND NOT M.Movement = 'InTime Present' THEN 'LatePresent'                  WHEN NOT ISNULL(M.Holiday, '-') = '-' THEN 'Holiday'                    WHEN M.InTime IS NULL AND NOT M.Leave = '-' THEN 'Leave'                    ELSE 'Absent'                   END END AS 'FinalStatus', ISNULL(M.HDayType, '-') AS Exception FROM M) SELECT * FROM MT AS F WHERE FinalStatus='LatePresent' ORDER BY Date option (maxrecursion 0)";

        $result  = DB::Select($query);

        return $result;
    }

        public static function findEmpLate2Info($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT A.eDate, A.ShiftID, S.ShiftName, A.MC, A.InTime, A.inMode,A.OutTime,A.OutMode,A.sTime, A.eTime, A.cMin,A.Movement,A.MovementStatus,A.HDay as Holiday,A.LeaveType,A.LeaveStatus,A.Status,A.Exception FROM TA_AttendanceSummaryDetail_Auto AS A LEFT OUTER JOIN TA_Shift AS S ON A.ShiftID = S.RowID LEFT OUTER JOIN AMG_HR AS H ON A.EmployeeCode = H.EmployeeCode WHERE A.EmployeeCode = '".$emp_code."' AND A.eDate BETWEEN '".$fromdate."' AND '".$toddate."' and A.Status='LatePresent' ORDER BY A.eDate";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findEmpHolidayInfo($emp_code,$fromdate,$toddate)
    {
        $query = "SELECT * FROM dbo.TA_Holiday WHERE (HDay BETWEEN '".$fromdate."' AND '".$toddate."')";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findAllCarList()
    {
        $query = "WITH TripCounts AS (SELECT VNo, COUNT(VNo) AS TripToday FROM dbo.Admin_Vehicles_Assignment_Log WHERE (AssignedDate = CONVERT(char(10), GetDate(),126)) GROUP BY VNo) SELECT RTRIM(dbo.Admin_Vehicles.Brand) + '-' + RTRIM(dbo.Admin_Vehicles.Type) + '- Seat(s):(' + RTRIM(dbo.Admin_Vehicles.SeatCapacity) + ') - No:' + RTRIM(dbo.Admin_Vehicles.RegiNumber) + ' CC:' + RTRIM(dbo.Admin_Vehicles.CC) + ' Fuel:' + RTRIM(dbo.Admin_Vehicles.FuelType) + ' BU:' + RTRIM(dbo.Admin_Vehicles.BU) AS value, dbo.Admin_Vehicles.Brand, dbo.Admin_Vehicles.Type, dbo.Admin_Vehicles.SeatCapacity, dbo.Admin_Vehicles.RegiNumber, dbo.Admin_Vehicles.CC, dbo.Admin_Vehicles.FuelType, dbo.Admin_Vehicles.UseType, ISNULL(TC.TripToday, 0) AS TripToday FROM dbo.Admin_Vehicles LEFT OUTER JOIN TripCounts AS TC ON dbo.Admin_Vehicles.RegiNumber = TC.VNo WHERE (dbo.Admin_Vehicles.UseType IN ('Pool', 'Rental')) ORDER BY RIGHT(RTRIM(dbo.Admin_Vehicles.RegiNumber),4) ASC";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findCarLog($vno)
    {
        $query = "SELECT L.RefRowID, D.HRTitle, L.DriverID, D.mobilePhone, L.VNo, L.UseStatus, A.HRTitle AS AName, L.AssignedBy, L.AssignedDate, L.AssignTime, L.AssignedUpto, L.AssignUptoTime, L.EntryBy, L.EntryDate FROM dbo.Admin_Vehicles_Assignment_Log AS L LEFT OUTER JOIN dbo.AMG_HR AS A ON L.AssignedBy = A.EmployeeCode LEFT OUTER JOIN dbo.AMG_HR AS D ON L.DriverID = D.EmployeeCode WHERE L.UseStatus='Assigned' AND L.AssignedDate > = CONVERT(char(10), GetDate(),126) AND L.VNo='".$vno."'";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findDriverInfo($emp_code)
    {
        $query = "SELECT RTRIM(dbo.AMG_HR.HRTitle) + '-' + RTRIM(dbo.AMG_HR.EmployeeCode) + '-' + RTRIM(ISNULL(dbo.AMG_HR.FunctionalDesignation, N'----')) AS value, RTRIM(dbo.AMG_HR.Designation) AS Designation, RTRIM(dbo.AMG_HR.EmployeeCode) AS EmployeeID, RTRIM(dbo.AMG_HR.HRTitle) AS Employeename, RTRIM(AMG_HR_1.HRTitle) AS Department, RTRIM(dbo.U_Companies.CompanyName) AS CompanyName, LEFT(RTRIM(dbo.AMG_HR.HRCode), 4) AS Company, RTRIM(dbo.AMG_HR.HRCode) AS HRCode, RTRIM(dbo.AMG_HR.pHead) AS pHead, RTRIM(dbo.AMG_HR.SupervisorID) AS SupervisorID, RTRIM(dbo.AMG_HR.LeaveAuthority) AS HOD, RTRIM(dbo.AMG_HR.FunctionalDesignation) AS FunctionalDesignation,RTRIM(dbo.AMG_HR.HOD) AS isHOD, CASE WHEN dbo.AMG_HR.MobilePhone = '' THEN RTRIM(dbo.AMG_HR.OfficeMobile) ELSE RTRIM(dbo.AMG_HR.MobilePhone) END AS MobileNo FROM dbo.U_Companies RIGHT OUTER JOIN dbo.AMG_HR AS AMG_HR_1 ON dbo.U_Companies.CompanyID = LEFT(AMG_HR_1.HRCode, 4) RIGHT OUTER JOIN dbo.AMG_HR ON AMG_HR_1.HRCode = dbo.AMG_HR.pHead WHERE ((dbo.AMG_HR.EmployeeCode LIKE N'%".$emp_code."%') OR (dbo.AMG_HR.HRTitle LIKE N'% %') OR (dbo.AMG_HR.PABX LIKE N'% %') OR (dbo.AMG_HR.eMail LIKE N'% %')) AND (dbo.AMG_HR.isHead = 'No') AND dbo.AMG_HR.Designation LIKE N'%Driver%'";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findStationaryDetailsInfo($refno)
    {
        $query = "SELECT I.RowID, RTRIM(I.ItemName) ItemName, RTRIM(I.ItemClass) ItemClass, RTRIM(I.ItemType) ItemType, RTRIM(I.MajorUnit) AS Unit, R.ItemQty, R.SupplyQty FROM AMG.dbo.Admin_OfficeStationery_Requisition_Details as R LEFT OUTER JOIN [AMG].[dbo].[Inv_ItemName] as I ON R.ItemID = I.RowID LEFT OUTER JOIN AMG.dbo.Admin_OfficeStationery_Requisition AS RQ ON R.RefReqID = RQ.RowID WHERE RQ.RefNo='".$refno."'";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findAptCnbBoqMpr($RowID)
    {
        $query = "SELECT a.RowID,a.Project_Code,a.JobCode,a.MPRNo,a.MPRDate,a.EntryBy,a.EntryDate,a.MPRStatus,S.ProjectName AS ProjectName,S.Project_Incharge,a.FloorLocation,a.MPR_Remark FROM dbo.Apt_Projects AS S RIGHT OUTER JOIN dbo.APT_CNB_BOQ_MPR AS a ON S.Project_Code = a.Project_Code LEFT OUTER JOIN dbo.Apt_Projects AS T ON a.Project_Code = T.Project_Code WHERE a.RowID ='".$RowID."'";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findMprProductDetails($RowID)
    {
        $query = "SELECT * FROM dbo.APT_CNB_MPR_Split_V2($RowID) ORDER By RowID";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findMprProductDetailsRod($RowID)
    {
        $query = "SELECT * FROM dbo.APT_CNB_MPR_Split_ROD_V2($RowID) ORDER By RowID";

        $result  = DB::Select($query);

        return $result;
    }




    public static function findApprovalMatrixVal1()
    {
        $query = "SELECT ApprovingIDs,DepartmentName FROM dbo.APT_CNB_ApprovalMatrix WHERE  DepartmentName in ('Inventory')";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findApprovalMatrixVal2()
    {
        $query = "SELECT ApprovingIDs,DepartmentName FROM dbo.APT_CNB_ApprovalMatrix WHERE  DepartmentName in ('CSD','EnC','EE','Plumbing','MS','EnCHead')";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findApprovalMatrixVal3()
    {
        $query = "SELECT ApprovingIDs,DepartmentName FROM dbo.APT_CNB_ApprovalMatrix WHERE  DepartmentName in ('CnB')";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findApprovalMatrixVal4()
    {
        $query = "SELECT ApprovingIDs,DepartmentName FROM dbo.APT_CNB_ApprovalMatrix WHERE  DepartmentName in ('EnCHead')";

        $result  = DB::Select($query);

        return $result;
    }


    public static function findApprovalStatusDetails($RowID)
    {
        $query = "SELECT RequestingID,ApprovalDate,Remark, cast((SELECT ( rtrim(ltrim(isnull(AMG_HR.HRTitle, 'Not Valid HR ID')))+' ('+ rtrim(ltrim( sm.ECode))+'), ' ) FROM dbo.SplitSalesPerson( rtrim(ltrim(isnull(MA.RequestingID,'o'))),',') AS sm LEFT OUTER JOIN AMG_HR ON rtrim(ltrim(sm.ECode)) = rtrim(ltrim(AMG_HR.EmployeeCode)) FOR XML PATH('')) as varchar(250)) as _Name FROM[AMG].[dbo].[MAN_Approval] AS MA WHERE rIdentityValue = '".$RowID."' AND rTable Like N'%dbo.APT_CNB_BOQ_MPR%' AND MA.ApprovalStatus <> 'Pending' ORDER BY MA.RowID";

        $result  = DB::Select($query);

        return $result;
    }

    public static function findProject($Project_Code)
    {
        $query = "SELECT CompanyID, Project_Code, ProjectName, Land_Area, SaleableArea, ProjectType, Address, ProjectArea,ProjectCity, BOQ, RodEntryMethod, Inventory_Officer, Project_Incharge FROM   dbo.Apt_Projects  WHERE Project_Code='".$Project_Code."'";

        $result  = DB::Select($query);

        return $result;
    }

   




}