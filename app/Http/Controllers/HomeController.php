<?php

namespace App\Http\Controllers;

use App\pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pso = new pso();

        if (!$pso->pso_found) {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->error_message);
            $request->session()->flash('source', $pso->error_source);
            if ($pso->pso_info->namespace !== null) $request->session()->flash('yaml', $pso->pso_info->yaml);

            return view('dashboard');
        } else {
            Session::forget('alert-class');
            Session::forget('message');
            Session::forget('source');
            Session::forget('yaml');
        }

        $dashboard = $pso->dashboard();
        $portal_info = $pso->portal_info();
        return view('dashboard', ['dashboard' => $dashboard, 'portal_info' => $portal_info]);
    }

    public function error(Request $request)
    {
        return view('dashboard');
    }

    public function refreshdata(Request $request)
    {
        $pso = new pso();

        if ($pso->RefreshData(true)) {
            if ($request['route'] !== 'Error') {
                $dashboard = $pso->dashboard();
                $portal_info = $pso->portal_info();
                return view('dashboard', ['dashboard' => $dashboard, 'portal_info' => $portal_info]);

                //return redirect()->route($request['route']);
            } else {
                return redirect()->route('Dashboard');
            }
        } else {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->error_message);
            $request->session()->flash('source', $pso->error_source);
            $request->session()->flash('yaml', $pso->pso_info->yaml);

            return redirect()->route('Dashboard');
        }
    }
}
