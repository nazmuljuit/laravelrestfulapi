<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\User;
// use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
class TestController extends Controller
{
    public function testdata()
    {
		return "okkkk";

        $client = new Client();
        $response = $client::withOptions([
            'debug' => true,
        ])->get('http://205.188.5.54:92/android/LoginVarify_GET.asp?UID=6252&PWD=Rasel@06252&RegCode=QWERTYUI&IMEI=0123456789');
        return $response;
        // $client = new GuzzleHttp\Client();
       $client =  new Client;

$res = $client->request('GET', 'http://205.188.5.54:92/android/LoginVarify_GET.asp?UID=6252&PWD=Rasel@06252&RegCode=QWERTYUI&IMEI=0123456789');
return $res;
        $a  = Http::get('http://205.188.5.54:92/android/LoginVarify_GET.asp?UID=6252&PWD=Rasel@06252&RegCode=QWERTYUI&IMEI=0123456789');
        return $a->json();
        $pass = User::where('EmployeeCode','06252')->limit(1)->get();
       return $pass;

    }




}
