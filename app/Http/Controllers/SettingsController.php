<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    private function getPso(Request $request)
    {
        $pso = new Pso();

        if (!$pso->psoFound) {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->errorMessage);
            $request->session()->flash('source', $pso->errorSource);
            $request->session()->flash('yaml', $pso->psoInfo->yaml);
            if ($pso->psoInfo->namespace !== null) {
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

    public function pso(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $settings = $pso->settings();
            $log = $pso->log();
            $portalInfo = $pso->portalInfo();

            return view('settings/pso', ['settings' => $settings, 'log' => $log, 'portalInfo' => $portalInfo]);
        }
    }

    public function nodes(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $nodes = $pso->nodes();
            $portalInfo = $pso->portalInfo();

            return view('settings/nodes', ['nodes' => $nodes, 'portalInfo' => $portalInfo]);
        }
    }
}
