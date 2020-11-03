<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    private function getPso(Request $request)
    {
        $pso = new Pso();

        if (!$pso->psoFound) {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->errorMessage);
            $request->session()->flash('source', $pso->errorSource);
            if ($pso->errorSource !== 'redis') {
                $request->session()->flash('yaml', $pso->psoInfo->yaml);
            }

            return false;
        } else {
            Session::forget('alert-class');
            Session::forget('message');
            Session::forget('source');
            Session::forget('yaml');

            return $pso;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $dashboard = $pso->dashboard();
            $portalInfo = $pso->portalInfo();
            return view('dashboard', ['dashboard' => $dashboard, 'portalInfo' => $portalInfo]);
        }
    }

    public function refreshData(Request $request)
    {
        // Check if a return route was included in the request
        if ($request->input('route') !== 'RefreshData') {
            $redirectTo = $request->input('route');
        } else {
            // If not set, return to 'Dashboard' after the refresh
            $redirectTo = 'Dashboard';
        }

        // Make sure PSO data is refreshed on next data collection
        pso::requestRefresh();

        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            Log::info($redirectTo);
            Log::info(redirect()->route($redirectTo));
            if (\Illuminate\Support\Facades\Route::has($redirectTo)) {
                return redirect()->route($redirectTo);
            } else {
                return redirect()->route('Dashboard');
            }
        }
    }
}
