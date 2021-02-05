<?php

namespace App\Http\Controllers;

use App\Pso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ApiController extends Controller
{
    public const PSO_NOTFOUND = 'The Pure Storage® - Pure Service Orchestrator™ (PSO) was not found';

    public function dashboard(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return [$pso->dashboard(), $pso->portalInfo()];
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function storageArrays(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->arrays();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function namespaces(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->namespaces();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function storageClasses(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->storageclasses();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function labels(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->labels();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function pods(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->pods();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function jobs(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->jobs();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function deployments(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->deployments();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function statefulSets(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return $pso->statefulsets();
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function snapshots(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return ['volumes' => $pso->volumesnapshots()];
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function volumes(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return ['volumes' => $pso->volumes(), 'orphaned' => $pso->orphaned()];
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function settingsPso(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            $settings = $pso->settings();
            // Remove yaml element to keep API Token private
            unset($settings['yaml']);
            return ['settings' => $settings, 'log' => $pso->log()];
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }

    public function settingsNodes(Request $request)
    {
        $pso = new Pso();

        if ($pso->psoFound) {
            return ['nodes' => $pso->nodes()];
        } else {
            $response = array('Error' => self::PSO_NOTFOUND);
            return $response;
        }
    }
}
