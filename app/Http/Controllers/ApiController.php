<?php

namespace App\Http\Controllers;

use App\pso;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function Dashboard (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->dashboard();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function StorageArrays (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->arrays();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function Namespaces (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->namespaces();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function StorageClasses (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->storageclasses();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function Labels (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->labels();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function Deployments (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->deployments();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function StatefulSets (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return $pso->statefulsets();
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function Snapshots (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return ['volumes' => $pso->volumesnapshots()];
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }

    public function Volumes (Request $request)
    {
        $pso = new pso();

        if ($pso->pso_found) {
            return ['volumes' => $pso->volumes(), 'orphaned' => $pso->orphaned()];
        } else {
            $response = array('Error' => 'The Pure Storage Pure Service Orchestrator was not found');
            return $response;
        }
    }
}
