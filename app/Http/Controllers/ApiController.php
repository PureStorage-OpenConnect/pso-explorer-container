<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ApiController extends Controller
{
    public function Dashboard (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->dashboard();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function StorageArrays (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->arrays();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Namespaces (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->namespaces();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function StorageClasses (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->storageclasses();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Labels (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->labels();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Pods (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->pods();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Jobs (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->jobs();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Deployments (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->deployments();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function StatefulSets (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->statefulsets();
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Snapshots (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return ['volumes' => $pso->volumesnapshots()];
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }

    public function Volumes (Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return ['volumes' => $pso->volumes(), 'orphaned' => $pso->orphaned()];
        } else {
            $response = array('Error' => 'The Pure Storage - Pure Service Orchestrator (PSO) was not found');
            return $response;
        }
    }
}
