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
            $pso_pods = $pso->pods();
            $portalInfo = $pso->portalInfo();

            return view('analysis/pods', ['pso_pods' => $pso_pods, 'portalInfo' => $portalInfo]);
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
            $pso_jobs = $pso->jobs();
            $portalInfo = $pso->portalInfo();

            return view('analysis/jobs', ['pso_jobs' => $pso_jobs, 'portalInfo' => $portalInfo]);
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
            $pso_deployments = $pso->deployments();
            $portalInfo = $pso->portalInfo();

            return view('analysis/deployments', ['pso_deployments' => $pso_deployments, 'portalInfo' => $portalInfo]);
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
            $pso_statefulsets = $pso->statefulsets();
            $portalInfo = $pso->portalInfo();

            return view(
                'analysis/statefulsets',
                [
                    'pso_statefulsets' => $pso_statefulsets,
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
            $pso_labels = $pso->labels();
            $portalInfo = $pso->portalInfo();

            return view('analysis/labels', ['pso_labels' => $pso_labels, 'portalInfo' => $portalInfo]);
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
            $pso_namespaces = $pso->namespaces();
            $portalInfo = $pso->portalInfo();

            return view('analysis/namespaces', ['pso_namespaces' => $pso_namespaces, 'portalInfo' => $portalInfo]);
        }
    }
}
