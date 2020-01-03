<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::any('/', function () {
    return 'Welcome!';
});

Route::namespace('Admin\\System')->group(function () {
    Route::post('admin/login/password', 'AuthController@loginWithPassword')->name('login.password');
});

Route::namespace('Admin\\System')->middleware('auth:api')->group(function () {
    Route::get('admin/initialization', 'AppController@initialization')->name('app.init');

    Route::get('admin/profile', function (Request $request) {
        return $request->user();
    })->name('user.profile');

    // PermissionController
    Route::get('admin/permissions/all', 'PermissionController@all')->name('permissions.all');
    Route::apiResource('admin/permissions', 'PermissionController');

    // PolicyController
    Route::get('admin/policies/all', 'PolicyController@all')->name('policies.all');
    Route::apiResource('admin/policies', 'PolicyController');

    // MenuController
    Route::apiResource('admin/menus', 'MenuController');

    // RoleController
    Route::get('admin/roles/all', 'RoleController@all')->name('roles.all');
    Route::apiResource('admin/roles', 'RoleController');

    // UserController
    Route::get('admin/users/all', 'UserController@all')->name('users.all');
    Route::apiResource('admin/users', 'UserController');
});

Route::namespace('Admin\\Workflow')->middleware('auth:api')->group(function () {

    // WorkflowController
    Route::get('admin/workflow_model/columns', 'WorkflowController@modelColumns');
    Route::apiResource('admin/workflow', 'WorkflowController');

    Route::get('admin/workflow_node/design', 'WorkflowNodeController@design')->name('workflow.design');
    Route::get('admin/workflow_edges/{edges}', 'WorkflowNodeController@edge')->name('edge.show');
    Route::post('admin/workflow_edges', 'WorkflowNodeController@edgeStore')->name('edge.create');
    Route::put('admin/workflow_edges/{edges}', 'WorkflowNodeController@edgeUpdate')->name('edge.update');
    Route::patch('admin/workflow_edges/{edges}', 'WorkflowNodeController@edgeUpdate')->name('edge.update');
    Route::delete('admin/workflow_edges/{edges}', 'WorkflowNodeController@edgeDestroy')->name('edge.destroy');
    Route::apiResource('admin/workflow_nodes', 'WorkflowNodeController')->except('index');
});

Route::namespace('Admin\\resource')->middleware('auth:api')->group(function () {
    //departmentsController
    Route::get('admin/department/members', 'DepartmentController@members');
    Route::post('admin/department/set_managers', 'DepartmentController@setManagers');
    Route::post('admin/department/remove_managers', 'DepartmentController@removeManagers');
    Route::apiResource('admin/departments', 'DepartmentController');
});

Route::namespace('Admin\\Reimbursement')->middleware('auth:api')->group(function () {
    //departmentsController
    Route::get('admin/reimbursement/audit_index', 'ReimbursementController@auditIndex');
    Route::get('admin/reimbursement/columns', 'ReimbursementController@conditionColumns');
    Route::get('admin/reimbursement/operators', 'ReimbursementController@conditionOperators');
    Route::get('admin/reimbursement/process', 'ReimbursementController@process');
    Route::get('admin/reimbursement/logs', 'ReimbursementController@logs');
    Route::post('admin/reimbursement/submit', 'ReimbursementController@submit');
    Route::post('admin/reimbursement/audit', 'ReimbursementController@audit');
    Route::apiResource('admin/reimbursements', 'ReimbursementController');

    Route::get('admin/reimbursement_types/all', 'TypeController@all');
    Route::apiResource('admin/reimbursement_types', 'TypeController');

    Route::get('admin/reimbursement_projects/all', 'ProjectController@all');
    Route::apiResource('admin/reimbursement_projects', 'ProjectController');
});
