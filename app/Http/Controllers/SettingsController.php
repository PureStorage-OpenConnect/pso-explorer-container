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

        if ($request->has('clean_db_vols')) {
            $settings = $pso->settings();
            $array_name = '';
            $array_count = 0;
            $volumes = [];

            foreach ($settings['dbvols'] as $item) {
                if ($item['unhealthy']) {
                    if (($array_name !== $item['pureArrayName']) and ($item['pureArrayName'] !== null)) {
                        $array_name = $item['pureArrayName'];
                        $array_count = $array_count + 1;
                        $volumes[$array_count]['name'] = $item['pureArrayName'];
                        $volumes[$array_count]['url'] = $item['pureArrayMgmtEndPoint'];
                        $volumes[$array_count]['api'] = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
                        $volumes[$array_count]['type'] = $item['pureArrayType'];

                        $volumes[$array_count]['volumes'] = [];
                    }
                    if ($item['pureName'] !== null) {
                        array_push($volumes[$array_count]['volumes'], $item['pureName']);
                    }
                }
            }

            if (count($volumes)) {
                $ansible_yaml = '- name: Clean stale PSO 6.0 database backend volumes<br>';
                $ansible_yaml = $ansible_yaml . '  hosts: localhost<br>';
                $ansible_yaml = $ansible_yaml . '  gather_facts: no<br>';
                $ansible_yaml = $ansible_yaml . '  collections:<br>';
                $ansible_yaml = $ansible_yaml . '    - purestorage.flasharray<br>';
                $ansible_yaml = $ansible_yaml . '    - purestorage.flashblade<br>';
                $ansible_yaml = $ansible_yaml . '  vars:<br>';

                foreach ($volumes as $index => $array) {
                    $ansible_yaml = $ansible_yaml . '    pure' . $index . '_name: "' . $array['name'] . '"<br>';
                    $ansible_yaml = $ansible_yaml . '    pure' . $index . '_url: "' . $array['url'] . '"<br>';
                    $ansible_yaml = $ansible_yaml . '    pure' . $index . '_api: "' . $array['api'] . '"<br>';
                }

                $ansible_yaml = $ansible_yaml . '  tasks:<br>';
                foreach ($volumes as $index => $array) {
                    $ansible_yaml = $ansible_yaml . '  - name: Delete volumes from {{ pure' . $index . '_name }}<br>';
                    if ($array['type'] == 'FA') {
                        $ansible_yaml = $ansible_yaml . '    purefa_volume:<br>';
                        $ansible_yaml = $ansible_yaml . '      fa_url: "{{ pure' . $index . '_url }}"<br>';
                    } else {
                        $ansible_yaml = $ansible_yaml . '    purefb_fs:<br>';
                        $ansible_yaml = $ansible_yaml . '      fb_url: "{{ pure' . $index . '_url }}"<br>';
                    }
                    $ansible_yaml = $ansible_yaml . '      api_token: "{{ pure' . $index . '_api }}"<br>';
                    $ansible_yaml = $ansible_yaml . '      name: "{{ item }}"<br>';
                    $ansible_yaml = $ansible_yaml . '      state: absent<br>';
                    $ansible_yaml = $ansible_yaml . '      eradicate: true<br>';
                    $ansible_yaml = $ansible_yaml . '    loop:<br>';
                    foreach ($array['volumes'] as $volume) {
                        $ansible_yaml = $ansible_yaml . '      - "' . $volume . '"<br>';
                    }
                }
            } else {
                $ansible_yaml = '# No unhealthy volume found';
            }

            return view('settings/pso', ['ansible_yaml' => $ansible_yaml]);
        }

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
        $pso = new Pso();

        // If $pso is false, an error was returned
        $nodes = $pso->nodes();
        $portalInfo = $pso->portalInfo();
        if (!$pso) {
            // Do not show errors for Nodes page, since it's available before PSO is installed
            $request->session()->forget(['alert-class', 'message', 'source', 'yaml']);
        }
        return view('settings/nodes', ['nodes' => $nodes, 'portalInfo' => $portalInfo]);
    }
}
