<?php

namespace App\Http\Controllers;

use App\Api\GitHubApi;
use App\Pso;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getReleases($edition, $repo, $values)
    {
        try {
            $myGit = new GitHubApi($repo, $values);

            $releases = [];

            foreach ($myGit->releases() as $release) {
                $tag = $release->tag_name;
                $majorRelease = intval(explode('.', str_ireplace('v', '', $tag))[0]);
                if (($edition == 'FLEX') and ($majorRelease < 5)) {
                    $releases['releases'][$tag] = $release->name;
                    $releases['descriptions'][$tag] = $release->body;
                } elseif (($edition !== 'FLEX') and ($majorRelease >= 5)) {
                    $releases['releases'][$tag] = $release->name;
                    $releases['descriptions'][$tag] = $release->body;
                }
            }
            return $releases;
        } catch (ConnectionException $e) {


            unset($e);
            return null;
        }
    }

    public function config(Request $request)
    {
        // Get PSO instance
        $pso = new Pso();
        $releases = [];
        $edition = '';

        if ($pso->psoFound) {
            $edition = $pso->psoInfo->psoEdition;
            $releases = $this->getReleases($edition, $pso->psoInfo->repoUri, $pso->psoInfo->valuesUri);
            $phase = 2;
            $upgrade = true;
        } else {
            if (!$pso->psoFound) {
                // Do not show errors for Config page, since it's available before PSO is installed
                $request->session()->forget(['alert-class', 'message', 'source', 'yaml']);
            }
            $phase = 1;
            $upgrade = false;
        }

        $settings = $pso->settings();
        return view('settings/config', [
            'isUpgrade' => $upgrade,
            'psoEdition' => $edition,
            'psoRelease' => '',
            'releases' => $releases,
            'phase' => $phase
        ]);
    }

    public function configPost(Request $request)
    {
        $isUpgrade = $request->input('_isUpgrade');
        switch ($request->input('_phase')) {
            case '1':
                // This is a new installation
                $phase = 2;
                $edition = $request->input('_edition');
                switch ($edition) {
                    case 'FLEX':
                        $repoUri = env('FLEX_GITREPO');
                        $valuesUri = env('FLEX_VALUES');
                        break;
                    case 'PSO5':
                        $repoUri = env('PSO5_GITREPO');
                        $valuesUri = env('PSO5_VALUES');
                        break;
                    default:
                        $repoUri = env('PSO6_GITREPO');
                        $valuesUri = env('PSO6_VALUES');
                        break;
                }

                $releases = $this->getReleases($edition, $repoUri, $valuesUri);

                return view('settings/config', [
                    'isUpgrade' => $isUpgrade,
                    'releases' => $releases,
                    'psoEdition' => $edition,
                    'psoRelease' => '',
                    'phase' => $phase
                ]);
            case'2':
                // Could be a new install or upgrade
                $pso = new Pso();

                if ($pso->psoFound) {
                    if ($request->input('_release') == null) {
                        $request->session()->flash('alert-class', 'alert-danger');
                        $request->session()->flash('message', 'Unable to connect to GitHub');
                        $request->session()->flash('source', 'generic');

                        return redirect()->route('Settings-Config');
                    }
                    $repoUri = $pso->psoInfo->repoUri ?? '';
                    $valuesUri = $pso->psoInfo->valuesUri ?? '';

                    try {
                        $myGit = new GitHubApi($repoUri, $valuesUri);
                        $yaml = $myGit->values($request->input('_release'));
                        if ($yaml == '404: Not Found') {
                            // TODO: Error handling
                            $yaml = '[]';
                        }
                    } catch (ConnectionException $e) {
                        unset($e);
                        // TODO: Error handling
                        $yaml = '[]';
                    }
                    $psoValues = yaml_parse($yaml);

                    // Set the clusterID or pure.namespace
                    if (array_key_exists('namespace', $psoValues)) {
                        if (array_key_exists('pure', $psoValues['namespace'])) {
                            $psoValues['namespace']['pure'] = $pso->psoInfo->prefix;
                        } else {
                            $psoValues['clusterID'] = $pso->psoInfo->prefix;
                        }
                    } else {
                        $psoValues['clusterID'] = $pso->psoInfo->prefix;
                    }

                    // Set the orchestrator as openshift or k8s
                    if (array_key_exists('name', ($psoValues['orchestrator'] ?? []))) {
                        if ($pso->psoInfo->isOpenShift) {
                            $psoValues['orchestrator']['name'] = 'openshift';
                        } else {
                            $psoValues['orchestrator']['name'] = 'k8s';
                        }
                    }

                    // TODO: add the following field?:
                    //app:
                    //  debug: false

                    // Set storagetopology
                    if (array_key_exists('enable', ($psoValues['storagetopology'] ?? []))) {
                        if (in_array('Topology=true', $pso->psoInfo->psoArgs)) {
                            $psoValues['storagetopology']['enable'] = true;
                        } else {
                            $psoValues['storagetopology']['enable'] = false;
                        }
                    }

                    // Set arrays section and change deprecated NfsEndPoint to NFSEndPoint
                    $arrays = $pso->psoInfo->yaml;
                    $arrays = str_replace('NfsEndPoint', 'NFSEndPoint', $arrays);
                    $psoValues['arrays'] = yaml_parse($arrays)['arrays'] ?? [];

                    if (array_key_exists('flasharray', $psoValues)) {
                        if (array_key_exists('sanType', $psoValues['flasharray']) and ($pso->psoInfo->sanType !== null)) {
                            $psoValues['flasharray']['sanType'] = $pso->psoInfo->sanType;
                        }
                        if (array_key_exists('defaultFSType', $psoValues['flasharray']) and ($pso->psoInfo->faDefaultFsType !== null)) {
                            $psoValues['flasharray']['defaultFSType'] = $pso->psoInfo->faDefaultFsType;
                        }
                        if (array_key_exists('defaultFSOpt', $psoValues['flasharray']) and ($pso->psoInfo->faDefaultFSOpt !== null)) {
                            $psoValues['flasharray']['defaultFSOpt'] = $pso->psoInfo->faDefaultFSOpt;
                        }
                        if (array_key_exists('defaultMountOpt', $psoValues['flasharray']) and ($pso->psoInfo->faDefaultMountOpt !== null)) {
                            if (is_array($pso->psoInfo->faDefaultMountOpt)) {
                                $psoValues['flasharray']['defaultMountOpt'] = $pso->psoInfo->faDefaultMountOpt;
                            } else {
                                $psoValues['flasharray']['defaultMountOpt'] = [$pso->psoInfo->faDefaultMountOpt];
                            }
                        }
                        if (array_key_exists('preemptAttachments', $psoValues['flasharray']) and ($pso->psoInfo->faPreemptAttachments !== null)) {
                            $psoValues['flasharray']['preemptAttachments'] = $pso->psoInfo->faPreemptAttachments;
                        }
                        if (array_key_exists('iSCSILoginTimeout', $psoValues['flasharray']) and ($pso->psoInfo->faIscsiLoginTimeout !== null)) {
                            $psoValues['flasharray']['iSCSILoginTimeout'] = intval($pso->psoInfo->faIscsiLoginTimeout);
                        }
                    }

                    if (array_key_exists('flashblade', $psoValues)) {
                        if (array_key_exists('snapshotDirectoryEnabled', $psoValues['flashblade'])) {
                            $psoValues['flashblade']['snapshotDirectoryEnabled'] = $pso->psoInfo->enableFbNfsSnapshot;
                        }
                        if (array_key_exists('exportRules', $psoValues['flashblade'])) {
                            $psoValues['flashblade']['exportRules'] = $pso->psoInfo->nfsExportRules;
                        }
                    }

                    if (array_key_exists('database', $psoValues)) {
                        if (array_key_exists('maxSuspectSeconds', $psoValues['database'])) {
                            $psoValues['database']['maxSuspectSeconds'] = intval($pso->psoInfo->dbMaxSuspectSeconds);
                        }
                        if (array_key_exists('maxStartupSeconds', $psoValues['database'])) {
                            $psoValues['database']['maxStartupSeconds'] = intval($pso->psoInfo->dbMaxStartupSeconds);
                        }
                    }

                    // Remove any (empty) sections
                    foreach ($psoValues as $key => $value) {
                        if ((is_array($value)) and (count($value) == 0)) {
                            unset($psoValues[$key]);
                        }

                        if (is_array($value)) {
                            foreach ($value as $subkey => $subvalue) {
                                if ((is_array($subvalue)) and (count($subvalue) == 0)) {
                                    unset($psoValues[$key][$subkey]);
                                }
                            }
                            if ((isset($psoValues[$key])) and (count($psoValues[$key]) == 0)) {
                                unset($psoValues[$key]);
                            }
                        }
                    }

                    $yaml = yaml_emit($psoValues);
                    return view('settings/config-values', [
                        'isUpgrade' => $isUpgrade,
                        'yaml' => $yaml,
                        'psoNamespace' => $pso->psoInfo->namespace,
                        'psoEdition' => $request->input('_edition'),
                        'psoRelease' => $request->input('_release'),
                        ]);
                } else {
                    // This is when it's a new install
                    switch ($request->input('_edition')) {
                        case 'FLEX':
                            $repoUri = env('FLEX_GITREPO');
                            $valuesUri = env('FLEX_VALUES');
                            break;
                        case 'PSO5':
                            $repoUri = env('PSO5_GITREPO');
                            $valuesUri = env('PSO5_VALUES');
                            break;
                        default:
                            $repoUri = env('PSO6_GITREPO');
                            $valuesUri = env('PSO6_VALUES');
                            break;
                    }

                    // Release change the values.yaml path going from 6.0.0 to 6.0.1, so for backwards compatibility
                    // if version is 6.0.0 we need to reference the values.yaml at a different uri
                    $release = $request->input('_release');
                    if (substr(str_replace('v', '', $release), 0, 5) == '6.0.0') {
                        $valuesUri = '/pureStorageDriver/values.yaml';
                    }

                    $myGit = new GitHubApi($repoUri, $valuesUri);
                    $yaml = $myGit->values($release);
                    $psoValues = yaml_parse($yaml);

                    // Remove any (empty) sections
                    foreach ($psoValues as $key => $value) {
                        if ((is_array($value)) and (count($value) == 0)) {
                            unset($psoValues[$key]);
                        }

                        if (is_array($value)) {
                            foreach ($value as $subkey => $subvalue) {
                                if ((is_array($subvalue)) and (count($subvalue) == 0)) {
                                    unset($psoValues[$key][$subkey]);
                                }
                            }
                            if ((isset($psoValues[$key])) and (count($psoValues[$key]) == 0)) {
                                unset($psoValues[$key]);
                            }
                        }
                    }

                    return view('settings/config-builder', [
                        'psoValues' => $psoValues,
                        'isUpgrade' => $isUpgrade,
                        'psoEdition' => $request->input('_edition'),
                        'psoRelease' => $request->input('_release')
                    ]);
                }
            case '3':
                switch ($request->input('_edition')) {
                    case 'FLEX':
                        $repoUri = env('FLEX_GITREPO');
                        $valuesUri = env('FLEX_VALUES');
                        break;
                    case 'PSO5':
                        $repoUri = env('PSO5_GITREPO');
                        $valuesUri = env('PSO5_VALUES');
                        break;
                    default:
                        $repoUri = env('PSO6_GITREPO');
                        $valuesUri = env('PSO6_VALUES');
                        break;
                }
                $psoValues = [];

                $inputs = $request->all();
                if (isset($inputs['arrays'])) {
                    if (isset($inputs['arrays']['FlashArray'])) {
                        $flasharrays = [];
                        foreach ($inputs['arrays']['FlashArray'] as $key => $value) {
                            $inputLabels = explode(',', $value['Labels']);
                            $newLabel = [];
                            foreach ($inputLabels as $inputLabel) {
                                $input = explode(':', $inputLabel);
                                $newLabel[trim($input[0])] = str_replace('"','', trim($input[1]));
                            }
                            $value['Labels'] = $newLabel;
                            array_push($flasharrays, $value);
                        }
                        $psoValues['arrays']['FlashArrays'] = $flasharrays;
                    }
                    if (isset($inputs['arrays']['FlashBlade'])) {
                        $flashblades = [];
                        foreach ($inputs['arrays']['FlashBlade'] as $key => $value) {
                            $inputLabels = explode(',', $value['Labels']);
                            $newLabel = [];
                            foreach ($inputLabels as $inputLabel) {
                                $input = explode(':', $inputLabel);
                                $newLabel[trim($input[0])] = str_replace('"','', trim($input[1]));
                            }
                            $value['Labels'] = $newLabel;
                            array_push($flashblades, $value);
                        }
                        $psoValues['arrays']['FlashBlades'] = $flashblades;
                    }
                }

                foreach ($request->all() as $key => $value) {
                    if ($key[0] !== '_') {
                        $keys = explode(':', $key);
                        switch ($keys[count($keys) - 1]) {
                            case 'string':
                                $theValue = $value;
                                break;
                            case 'boolean':
                                $theValue = ($value == 'on');
                                break;
                            case 'integer':
                                $theValue = intval($value);
                                break;
                        }

                        switch (count($keys)) {
                            case 2:
                                $psoValues[$keys[0]] = $theValue;
                                break;
                            case 3:
                                $psoValues[$keys[0]][$keys[1]] = $theValue;
                                break;
                            case 4:
                                $psoValues[$keys[0]][$keys[1]][$keys[2]] = $theValue;
                                break;
                            case 5:
                                $psoValues[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $theValue;
                                break;
                        }
                    }
                }

                $yaml = yaml_emit($psoValues);
                return view('settings/config-values', [
                    'yaml' => $yaml,
                    'isUpgrade' => $isUpgrade,
                    'psoEdition' => $request->input('_edition'),
                    'psoRelease' => $request->input('_release'),
                ]);
                break;
        }
    }
}