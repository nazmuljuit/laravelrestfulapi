<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
  
    return $router->app->version();
    
});

$router->get('/privacy-policy-html', function () use ($router) {
    return view('privacy-policy');
});

Route::get('approvallistbycode','UserController@get_approval_list_by_emp_code');
Route::get('/test','TestController@testdata');
// API route group
$router->group(['prefix' => 'api'], function () use ($router) {
    // Matches "/api/register
   $router->post('register', 'AuthController@register');
     // Matches "/api/login
    $router->post('login', 'AuthController@login');
    $router->post('/logout', 'AuthController@logout');
    $router->put('/user-update', 'UserController@update_user');


    // Matches "/api/profile
    $router->get('profile/{EmployeeCode}', 'UserController@profile');

    // Matches "/api/users/1
    //get one user by id
    $router->get('users/{id}/{EmployeeCode}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');
    $router->post('search', 'UserController@Search_Users');

    // Matches "/api/manproval
    $router->get('approval-list/{EmployeeCode}[/{ApprovalStatus}]', 'ManApprovalController@AllApprovalList');
    $router->get('approval-list-type/{keytype}/{ApprovalStatus}/{EmployeeCode}/{RowID}', 'ManApprovalController@Approve_list_by_keytype');
    $router->put('approval-update', 'ManApprovalController@update_approval_byId');
    $router->get('type-detail/{keytype}/{RowID}/{EmployeeCode}', 'ManApprovalController@TypeDetail');

    //Get Vehicle Info
    $router->get('car-list', 'ManApprovalController@AllCarList');
    $router->get('car-log/{vno}', 'ManApprovalController@carLog');
    $router->get('driver-info/{emp_code}', 'ManApprovalController@driverInfo');


    // dashboard

    $router->get('salse_target/{EmployeeCode}', 'DashboardController@SalseTarget');
    $router->get('attendance/{EmployeeCode}','DashboardController@employee_attendance');
    $router->post('file-upload','DashboardController@FileUpload');
});
