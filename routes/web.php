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
Route::get('/refreshdata', function () {
    return redirect('/');
});
Route::post('/refreshdata', 'DashboardController@refreshData')->name('RefreshData');
// --- Analysis
Route::get('/analysis/pods', 'AnalysisController@pods')->name('Analysis-Pods');
Route::get('/analysis/jobs', 'AnalysisController@jobs')->name('Analysis-Jobs');
Route::get('/analysis/deployments', 'AnalysisController@deployments')->name('Analysis-Deployments');
Route::get('/analysis/statefulsets', 'AnalysisController@statefulSets')->name('Analysis-StatefulSets');
Route::get('/analysis/labels', 'AnalysisController@labels')->name('Analysis-Labels');
Route::get('/analysis/namespaces', 'AnalysisController@namespaces')->name('Analysis-Namespaces');
// --- Storage
Route::get('/storage/storagearrays', 'StorageController@storageArrays')->name('Storage-StorageArrays');
Route::get('/storage/storageclasses', 'StorageController@storageClasses')->name('Storage-StorageClasses');
Route::get('/storage/volumes', 'StorageController@volumes')->name('Storage-Volumes');
Route::get('/storage/snapshots', 'StorageController@snapshots')->name('Storage-Snapshots');

// *** Settings routes
Route::get('/settings/pso', 'SettingsController@pso')->name('Settings-Pso');
Route::get('/settings/nodes', 'SettingsController@nodes')->name('Settings-Nodes');
Route::get('/settings/config', 'SettingsController@config')->name('Settings-Config');
Route::post('/settings/config', 'SettingsController@configPost');

// *** API routes
// --- Dashboard
Route::get('/api/dashboard', 'ApiController@dashboard')->name('Dashboard-Api');

// --- Analysis
Route::get('/api/analysis/pods', 'ApiController@pods')->name('Analysis-Pods-Api');
Route::get('/api/analysis/jobs', 'ApiController@jobs')->name('Analysis-Jobs-Api');
Route::get('/api/analysis/deployments', 'ApiController@deployments')->name('Analysis-Deployments-Api');
Route::get('/api/analysis/statefulsets', 'ApiController@statefulSets')->name('Analysis-StatefulSets-Api');
Route::get('/api/analysis/labels', 'ApiController@labels')->name('Analysis-Labels-Api');
Route::get('/api/analysis/namespaces', 'ApiController@namespaces')->name('Analysis-Namespaces-Api');

// --- Storage
Route::get('/api/storage/storagearrays', 'ApiController@storageArrays')->name('Storage-StorageArrays-Api');
Route::get('/api/storage/storageclasses', 'ApiController@storageClasses')->name('Storage-StorageClasses-Api');
Route::get('/api/storage/volumes', 'ApiController@volumes')->name('Storage-Volumes-Api');
Route::get('/api/storage/snapshots', 'ApiController@snapshots')->name('Storage-Snapshots-Api');

// *** Settings routes
Route::get('/api/settings/pso', 'ApiController@settingsPso')->name('Settings-Pso-Api');
Route::get('/api/settings/nodes', 'ApiController@settingsNodes')->name('Settings-Nodes-Api');
