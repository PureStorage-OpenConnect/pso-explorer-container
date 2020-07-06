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

Route::get('/', 'HomeController@index')->name('Dashboard');
Route::post('/refreshdata', 'HomeController@refreshdata')->name('RefreshData');
Route::get('/settings', 'HomeController@settings')->name('Settings');

// GUI routes
Route::get('/view/storagearrays', 'ViewController@StorageArrays')->name('StorageArrays');
Route::get('/view/namespaces', 'ViewController@Namespaces')->name('Namespaces');
Route::get('/view/storageclasses', 'ViewController@StorageClasses')->name('StorageClasses');
Route::get('/view/labels', 'ViewController@Labels')->name('Labels');
Route::get('/view/pods', 'ViewController@Pods')->name('Pods');
Route::get('/view/deployments', 'ViewController@Deployments')->name('Deployments');
Route::get('/view/statefulsets', 'ViewController@StatefulSets')->name('StatefulSets');
Route::get('/view/snapshots', 'ViewController@Snapshots')->name('Snapshots');
Route::get('/view/volumes', 'ViewController@Volumes')->name('Volumes');

// GUI routes
Route::get('/api/dashboard', 'ApiController@Dashboard')->name('DashboardApi');
Route::get('/api/storagearrays', 'ApiController@StorageArrays')->name('StorageArraysApi');
Route::get('/api/namespaces', 'ApiController@Namespaces')->name('NamespacesApi');
Route::get('/api/storageclasses', 'ApiController@StorageClasses')->name('StorageClassesApi');
Route::get('/api/labels', 'ApiController@Labels')->name('LabelsApi');
Route::get('/api/pods', 'ApiController@Pods')->name('PodsApi');
Route::get('/api/deployments', 'ApiController@Deployments')->name('DeploymentsApi');
Route::get('/api/statefulsets', 'ApiController@StatefulSets')->name('StatefulSetsApi');
Route::get('/api/snapshots', 'ApiController@Snapshots')->name('SnapshotsApi');
Route::get('/api/volumes', 'ApiController@Volumes')->name('VolumesApi');
