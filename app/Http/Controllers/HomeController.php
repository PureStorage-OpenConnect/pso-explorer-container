<?php

namespace App\Http\Controllers;

use App\pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    private function getPso(Request $request)
    {
        $pso = new pso();

        if (!$pso->pso_found) {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->error_message);
            $request->session()->flash('source', $pso->error_source);
            $request->session()->flash('yaml', $pso->pso_info->yaml);
            if ($pso->pso_info->namespace !== null) $request->session()->flash('yaml', $pso->pso_info->yaml);

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
     * @return \Illuminate\Http\Response
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
            $portal_info = $pso->portal_info();
            return view('dashboard', ['dashboard' => $dashboard, 'portal_info' => $portal_info]);
        }
    }

    public function refreshdata(Request $request)
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
            return redirect()->route($redirectTo);
        }
    }

    public function settings(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $settings = $pso->settings();
            $portal_info = $pso->portal_info();

            return view('settings', ['settings' => $settings, 'portal_info' => $portal_info]);
        }
    }
}
