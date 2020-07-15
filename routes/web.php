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

// *** GUI routes ***
// --- Dashboard
Route::get('/', 'DashboardController@index')->name('Dashboard');
Route::get('/refreshdata', function () { return redirect('/'); });
Route::post('/refreshdata', 'DashboardController@refreshdata')->name('RefreshData');
// --- Analysis
Route::get('/analysis/pods', 'AnalysisController@Pods')->name('Analysis-Pods');
Route::get('/analysis/jobs', 'AnalysisController@Jobs')->name('Analysis-Jobs');
Route::get('/analysis/deployments', 'AnalysisController@Deployments')->name('Analysis-Deployments');
Route::get('/analysis/statefulsets', 'AnalysisController@StatefulSets')->name('Analysis-StatefulSets');
Route::get('/analysis/labels', 'AnalysisController@Labels')->name('Analysis-Labels');
Route::get('/analysis/namespaces', 'AnalysisController@Namespaces')->name('Analysis-Namespaces');
// --- Storage
Route::get('/storage/storagearrays', 'StorageController@StorageArrays')->name('Storage-StorageArrays');
Route::get('/storage/storageclasses', 'StorageController@StorageClasses')->name('Storage-StorageClasses');
Route::get('/storage/volumes', 'StorageController@Volumes')->name('Storage-Volumes');
Route::get('/storage/snapshots', 'StorageController@Snapshots')->name('Storage-Snapshots');

// *** Settings routes
Route::get('/settings/pso', 'SettingsController@Pso')->name('Settings-Pso');
Route::get('/settings/nodes', 'SettingsController@Nodes')->name('Settings-Nodes');

// *** API routes
// --- Dashboard
Route::get('/api/dashboard', 'ApiController@Dashboard')->name('Dashboard-Api');

// --- Analysis
Route::get('/api/analysis/pods', 'ApiController@Pods')->name('Analysis-Pods-Api');
Route::get('/api/analysis/jobs', 'ApiController@Jobs')->name('Analysis-Jobs-Api');
Route::get('/api/analysis/deployments', 'ApiController@Deployments')->name('Analysis-Deployments-Api');
Route::get('/api/analysis/statefulsets', 'ApiController@StatefulSets')->name('Analysis-StatefulSets-Api');
Route::get('/api/analysis/labels', 'ApiController@Labels')->name('Analysis-Labels-Api');
Route::get('/api/analysis/namespaces', 'ApiController@Namespaces')->name('Analysis-Namespaces-Api');

// --- Storage
Route::get('/api/storage/storagearrays', 'ApiController@StorageArrays')->name('Storage-StorageArrays-Api');
Route::get('/api/storage/storageclasses', 'ApiController@StorageClasses')->name('Storage-StorageClasses-Api');
Route::get('/api/storage/volumes', 'ApiController@Volumes')->name('Storage-Volumes-Api');
Route::get('/api/storage/snapshots', 'ApiController@Snapshots')->name('Storage-Snapshots-Api');

// *** Settings routes
Route::get('/api/settings/pso', 'ApiController@SettingsPso')->name('Settings-Pso-Api');
Route::get('/api/settings/nodes', 'ApiController@SettingsNodes')->name('Settings-Nodes-Api');
