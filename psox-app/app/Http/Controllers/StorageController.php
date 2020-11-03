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

    public function storageArrays(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoArrays = $pso->arrays();
            $portalInfo = $pso->portalInfo();

            return view(
                'storage/arrays',
                [
                    'psoArrays' => $psoArrays,
                    'portalInfo' => $portalInfo,
                    'array_keyword' => $request->input('array_keyword') ?? ''
                ]
            );
        }
    }

    public function storageClasses(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoStorageClasses = $pso->storageclasses();
            $psoVolumeSnapshotClasses = $pso->volumesnapshotclasses();
            $portalInfo = $pso->portalInfo();

            return view(
                'storage/storageclasses',
                [
                    'psoStorageClasses' => $psoStorageClasses,
                    'psoVolumeSnapshotClasses' => $psoVolumeSnapshotClasses,
                    'portalInfo' => $portalInfo
                ]
            );
        }
    }

    public function volumes(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoVols = $pso->volumes();
            $orphanedVols = $pso->orphaned();
            $portalInfo = $pso->portalInfo();

            return view(
                'storage/volumes',
                [
                    'psoVols' => $psoVols,
                    'orphanedVols' => $orphanedVols,
                    'portalInfo' => $portalInfo,
                    'volume_keyword' => $request->input('volume_keyword') ?? ''
                ]
            );
        }
    }
    public function snapshots(Request $request)
    {
        // Get PSO instance
        $pso = $this->getPso($request);

        // If $pso is false, an error was returned
        if (!$pso) {
            return view('dashboard');
        } else {
            $psoVolsnaps = $pso->volumesnapshots();
            $orphanedSnaps = $pso->orphanedsnapshots();
            $portalInfo = $pso->portalInfo();

            return view(
                'storage/snapshots',
                [
                    'psoVolsnaps' => $psoVolsnaps,
                    'orphanedSnaps' => $orphanedSnaps,
                    'portalInfo' => $portalInfo,
                    'volume_keyword' => $request->input('volume_keyword') ?? ''
                ]
            );
        }
    }
}
