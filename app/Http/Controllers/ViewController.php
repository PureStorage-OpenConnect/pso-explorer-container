<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ViewController extends Controller
{
    private function getPso(Request $request)
    {
        $pso = new Pso();

        if (!$pso->psoFound) {
            $request->session()->flash('alert-class', 'alert-danger');
            $request->session()->flash('message', $pso->errorMessage);
            $request->session()->flash('source', $pso->errorSource);
            $request->session()->flash('yaml', $pso->psoInfo->yaml);
            if ($pso->psoInfo->namespace !== null) $request->session()->flash('yaml', $pso->psoInfo->yaml);

            return false;
        } else {
            Session::forget('alert-class');
            Session::forget('message');
            Session::forget('source');
            Session::forget('yaml');

            return $pso;
        }
    }

    public function Namespaces (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_namespaces = $pso->namespaces();
            $portalInfo = $pso->portalInfo();

            return view('namespaces', ['pso_namespaces' => $pso_namespaces, 'portalInfo' => $portalInfo]);
        }
    }

    public function StorageClasses (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_storageclasses = $pso->storageclasses();
            $pso_volumesnapshotclasses = $pso->volumesnapshotclasses();
            $portalInfo = $pso->portalInfo();

            return view('storageclasses', ['pso_storageclasses' => $pso_storageclasses, 'pso_volumesnapshotclasses' => $pso_volumesnapshotclasses, 'portalInfo' => $portalInfo]);
        }
    }

    public function Labels (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_labels = $pso->labels();
            $portalInfo = $pso->portalInfo();

            return view('labels', ['pso_labels' => $pso_labels, 'portalInfo' => $portalInfo]);
        }
    }

    public function Pods (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_pods = $pso->pods();
            $portalInfo = $pso->portalInfo();

            return view('pods', ['pso_pods' => $pso_pods, 'portalInfo' => $portalInfo]);
        }
    }

    public function Jobs (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_jobs = $pso->jobs();
            $portalInfo = $pso->portalInfo();

            return view('jobs', ['pso_jobs' => $pso_jobs, 'portalInfo' => $portalInfo]);
        }
    }

    public function Deployments (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_deployments = $pso->deployments();
            $portalInfo = $pso->portalInfo();

            return view('deployments', ['pso_deployments' => $pso_deployments, 'portalInfo' => $portalInfo]);
        }
    }

    public function StatefulSets (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_statefulsets = $pso->statefulsets();
            $portalInfo = $pso->portalInfo();

            return view('statefulsets', ['pso_statefulsets' => $pso_statefulsets, 'portalInfo' => $portalInfo]);
        }
    }

    public function Snapshots (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_volsnaps = $pso->volumesnapshots();
            $portalInfo = $pso->portalInfo();

            return view('snapshots', ['pso_volsnaps' => $pso_volsnaps, 'portalInfo' => $portalInfo, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
        }
    }

    public function Volumes (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_vols = $pso->volumes();
            $orphaned_vols = $pso->orphaned();
            $portalInfo = $pso->portalInfo();

            return view('volumes', ['pso_vols' => $pso_vols, 'orphaned_vols' => $orphaned_vols, 'portalInfo' => $portalInfo, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
        }
    }

    public function StorageArrays (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_arrays = $pso->arrays();
            $portalInfo = $pso->portalInfo();

            return view('arrays', ['pso_arrays' => $pso_arrays, 'portalInfo' => $portalInfo]);
        }
    }
}
