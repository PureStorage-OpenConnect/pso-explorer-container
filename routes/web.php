<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/refreshdata', function () { return redirect('/'); });
Route::post('/refreshdata', 'HomeController@refreshdata')->name('RefreshData');

// *** GUI routes ***
// --- Dashboard
Route::get('/', 'HomeController@index')->name('Dashboard');
// --- Analysis
Route::get('/view/pods', 'ViewController@Pods')->name('Pods');
Route::get('/view/jobs', 'ViewController@Jobs')->name('Jobs');
Route::get('/view/deployments', 'ViewController@Deployments')->name('Deployments');
Route::get('/view/statefulsets', 'ViewController@StatefulSets')->name('StatefulSets');
Route::get('/view/labels', 'ViewController@Labels')->name('Labels');
Route::get('/view/namespaces', 'ViewController@Namespaces')->name('Namespaces');
// --- Storage
Route::get('/view/storagearrays', 'ViewController@StorageArrays')->name('StorageArrays');
Route::get('/view/storageclasses', 'ViewController@StorageClasses')->name('StorageClasses');
Route::get('/view/volumes', 'ViewController@Volumes')->name('Volumes');
Route::get('/view/snapshots', 'ViewController@Snapshots')->name('Snapshots');

// *** Settings routes
Route::get('/settings/pso', 'SettingsController@Pso')->name('SettingsPso');
Route::get('/settings/nodes', 'SettingsController@Nodes')->name('SettingsNodes');

// *** API routes
// --- Dashboard
Route::get('/api/dashboard', 'ApiController@Dashboard')->name('DashboardApi');

// --- Analysis
Route::get('/api/pods', 'ApiController@Pods')->name('PodsApi');
Route::get('/api/jobs', 'ApiController@Jobs')->name('JobsApi');
Route::get('/api/deployments', 'ApiController@Deployments')->name('DeploymentsApi');
Route::get('/api/statefulsets', 'ApiController@StatefulSets')->name('StatefulSetsApi');
Route::get('/api/labels', 'ApiController@Labels')->name('LabelsApi');
Route::get('/api/namespaces', 'ApiController@Namespaces')->name('NamespacesApi');

// --- Storage
Route::get('/api/storagearrays', 'ApiController@StorageArrays')->name('StorageArraysApi');
Route::get('/api/storageclasses', 'ApiController@StorageClasses')->name('StorageClassesApi');
Route::get('/api/volumes', 'ApiController@Volumes')->name('VolumesApi');
Route::get('/api/snapshots', 'ApiController@Snapshots')->name('SnapshotsApi');
