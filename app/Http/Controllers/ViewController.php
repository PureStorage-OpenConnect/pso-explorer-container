<?php

namespace App\Http\Controllers;

use App\pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ViewController extends Controller
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

    public function StorageArrays (Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $pso_arrays = $pso->arrays();
            $portal_info = $pso->portal_info();

            return view('arrays', ['pso_arrays' => $pso_arrays, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('namespaces', ['pso_namespaces' => $pso_namespaces, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('storageclasses', ['pso_storageclasses' => $pso_storageclasses, 'pso_volumesnapshotclasses' => $pso_volumesnapshotclasses, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('labels', ['pso_labels' => $pso_labels, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('pods', ['pso_pods' => $pso_pods, '$pods' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('deployments', ['pso_deployments' => $pso_deployments, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('statefulsets', ['pso_statefulsets' => $pso_statefulsets, 'portal_info' => $portal_info]);
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
            $portal_info = $pso->portal_info();

            return view('snapshots', ['pso_volsnaps' => $pso_volsnaps, 'portal_info' => $portal_info, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
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
            $portal_info = $pso->portal_info();

            return view('volumes', ['pso_vols' => $pso_vols, 'orphaned_vols' => $orphaned_vols, 'portal_info' => $portal_info, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
        }
    }
}
