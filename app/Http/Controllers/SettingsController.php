<?php

namespace App\Http\Controllers;

use App\pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
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

    public function Pso(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $settings = $pso->settings();
            $log = $pso->log();
            $portal_info = $pso->portal_info();

            return view('settings', ['settings' => $settings, 'log' => $log, 'portal_info' => $portal_info]);
        }
    }

    public function Nodes(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $nodes = $pso->nodes();
            $portal_info = $pso->portal_info();

            return view('nodes', ['nodes' => $nodes, 'portal_info' => $portal_info]);
        }
    }
}
