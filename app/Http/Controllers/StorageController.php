<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StorageController extends Controller
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

            return view('storage/arrays', ['pso_arrays' => $pso_arrays, 'portalInfo' => $portalInfo]);
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

            return view('storage/storageclasses', ['pso_storageclasses' => $pso_storageclasses, 'pso_volumesnapshotclasses' => $pso_volumesnapshotclasses, 'portalInfo' => $portalInfo]);
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

            return view('storage/volumes', ['pso_vols' => $pso_vols, 'orphaned_vols' => $orphaned_vols, 'portalInfo' => $portalInfo, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
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

            return view('storage/snapshots', ['pso_volsnaps' => $pso_volsnaps, 'portalInfo' => $portalInfo, 'volume_keyword' => $request->input('volume_keyword') ?? '']);
        }
    }
}
