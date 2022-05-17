<?php

namespace App\Models;

use DB;
use Auth;
use App;
use Carbon\Carbon;

class CrudModel {





    public static function save($table, $data)
    {

        return DB::table($table)->insertGetId($data);
    }

    public static function saveBatch($table, $batchData)
    {
        $data = [];
        foreach ($batchData as $v) {
            $data[] = $v;
        }

        DB::table($table)->insert($data);

        return true;
    }



    public static function find($table, $where = [], $orderBy = [])
    {
        $query = DB::table($table)->where($where);



            foreach ($orderBy as $key => $value) {
            $query->orderBy($key, $value);
            }
            return $query->first();



    }

    public static function findAll($table, $where = [], $orderBy = [], $groupBy = [])
    {
        $query = DB::table($table);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);



            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
            if (!empty($groupBy))
                $query->groupBy($groupBy);
            return $query->get();



    }

    public static function findAllArray($table, $where = [],$whereIn = [],$column_name, $orderBy = [], $groupBy = [])
    {
        $query = DB::table($table)->whereIn($column_name,$whereIn);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);

               


            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
            if (!empty($groupBy))
                $query->groupBy($groupBy);
            return $query->get();



    }

    public static function findAllSum($table, $where = [], $orderBy = [], $groupBy = [])
    {
        $query = DB::table($table);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);



            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
            if (!empty($groupBy))
                $query->groupBy($groupBy);
            return $query;



    }

    public static function findAllDateRange($table, $where = [], $date,$first_column,$last_column)
    {
        $query = DB::table($table)->where($table.'.'.$first_column,'<=',$date)->where($table.'.'.$last_column,'>=',$date);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);


            return $query->get();



    }

    public static function findAllDateRangeCount($table, $where = [], $from_date,$to_date,$date_column,$groupBy = [],$count_column,$find_column)
    {
        $query = DB::table($table)->select(DB::raw('count(".$count_column.") as total'),$table.'.'.$find_column)->where($table.'.'.$date_column,'<=',$to_date)->where($table.'.'.$date_column,'>=',$from_date);

        if (!empty($where)) $query->where($where);
            if (!empty($groupBy))
            $query->groupBy($groupBy);

            return $query->get();



    }

    public static function findAllDateRangeSum($table, $where = [], $from_date,$to_date,$date_column,$groupBy = [],$sum_column,$find_column)
    {
          //DB::enableQueryLog();
        $query = DB::table($table)->select(DB::raw("SUM($sum_column) as total"),$table.'.'.$find_column)->where($table.'.'.$date_column,'<=',$to_date)->where($table.'.'.$date_column,'>=',$from_date);

        if (!empty($where)) $query->where($where);
            if (!empty($groupBy))
            $query->groupBy($groupBy);


            return $query->get();

            //dd(DB::getQueryLog());



    }

    public static function findAllDateRangeColumn($table, $where = [], $from_date,$to_date,$date_column,$groupBy = [],$find_column,$associate_column)
    {
        $query = DB::table($table)->select($table.'.'.$find_column,$table.'.'.$associate_column)->where($table.'.'.$date_column,'<=',$to_date)->where($table.'.'.$date_column,'>=',$from_date);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);
            if (!empty($groupBy))
            $query->groupBy($groupBy);

            return $query->get();



    }

    public static function findDateRange($table, $where = [], $date,$first_column,$last_column)
    {
        $query = DB::table($table)->where($table.'.'.$first_column,'<=',$date)->where($table.'.'.$last_column,'>=',$date);

        is_string($where) ? $query->whereRaw($where) : $query->where($where);


            return $query->first();



    }

    public static function findAllTeamEmp($table, $team_id, $emp_id)
    {
        $query = DB::table($table)
                ->whereIn('team_id',$team_id)
                ->where('emp_id','!=',$emp_id);





            return $query->get();



    }




    public static function findPaginate($table, $where = [], $orderBy = [], $groupBy = [])
    {
        $query = DB::table($table)->where($where);

        foreach ($orderBy as $key => $value) {
            $query->orderBy($key, $value);
        }
        if (!empty($groupBy))
            $query->groupBy($groupBy);

        return $query->paginate(15);

    }

    public static function update($table, $data, $where)
    {
        DB::table($table)->where($where)->update($data);
        return true;
    }

    public static function updateArray($table, $data, $where,$array,$column_name)
    {
        DB::table($table)->where($where)->update($data);
        return true;
    }

    public static function delete($table, $where)
    {

        DB::table($table)->where($where)->delete();
        return true;
    }


}