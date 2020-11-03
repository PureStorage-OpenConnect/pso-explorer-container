<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AnalysisController extends Controller
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

    public function pods(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoPods = $pso->pods();
            $portalInfo = $pso->portalInfo();

            return view('analysis/pods', ['psoPods' => $psoPods, 'portalInfo' => $portalInfo]);
        }
    }

    public function jobs(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoJobs = $pso->jobs();
            $portalInfo = $pso->portalInfo();

            return view('analysis/jobs', ['psoJobs' => $psoJobs, 'portalInfo' => $portalInfo]);
        }
    }

    public function deployments(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoDeployments = $pso->deployments();
            $portalInfo = $pso->portalInfo();

            return view('analysis/deployments', ['psoDeployments' => $psoDeployments, 'portalInfo' => $portalInfo]);
        }
    }

    public function statefulSets(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoStatefulsets = $pso->statefulsets();
            $portalInfo = $pso->portalInfo();

            return view(
                'analysis/statefulsets',
                [
                    'psoStatefulsets' => $psoStatefulsets,
                    'portalInfo' => $portalInfo
                ]
            );
        }
    }

    public function labels(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoLabels = $pso->labels();
            $portalInfo = $pso->portalInfo();

            return view('analysis/labels', ['psoLabels' => $psoLabels, 'portalInfo' => $portalInfo]);
        }
    }

    public function namespaces(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoNamespaces = $pso->namespaces();
            $portalInfo = $pso->portalInfo();

            return view('analysis/namespaces', ['psoNamespaces' => $psoNamespaces, 'portalInfo' => $portalInfo]);
        }
    }
}
