<?php

namespace App\Http\Controllers;

use App\Api\GitHubApi;
use App\Http\Classes\PsoArray;
use App\Pso;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
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

    public function initialize(Request $request)
    {
        // Get PSO instance
        $pso = new Pso();

        if ($pso->psoFound) {
            // If PSO is installed propose a migration
            return redirect('/settings/config-builder/upgrade-helper');
        } else {
            // Do not show errors for Config page, since it's available before PSO is installed
            $request->session()->forget(['alert-class', 'message', 'source', 'yaml']);

            // If PSO is NOT installed start new deplpyment wizard
            return redirect('/settings/config-builder/install-helper');
        }
    }

    public function installHelper(Request $request)
    {
        return view('settings/config-builder/install-helper');
    }

    public function installSource(Request $request)
    {
        // Validate input
        $validatedInputs = $request->validate([
            'mode' => [
                'required',
                Rule::in(['github', 'upload'])
            ],
        ]);

        if ($validatedInputs['mode'] == 'github') {
            return redirect('settings/config-builder/install-github-edition');
        } else {
            return redirect('settings/config-builder/install-upload');
        }
    }

    public function installGithubEdition(Request $request)
    {
        return view('settings/config-builder/install-github-edition');
    }

    public function installGithubVersion(Request $request)
    {
        // Validate input
        $validatedInputs = $request->validate([
            'edition' => ['required',
                Rule::in(['FLEX', 'PSO5', 'PSO6'])
            ],
        ]);

        $edition = $request->input('edition');
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

        $settings['psoEdition'] = $edition;
        return view('settings/config-builder/install-github-version', [
            'releases' => $releases,
            'settings' => $settings,
        ]);
    }

    public function installUpload(Request $request)
    {

        return view('settings/config-builder/install-upload');
    }

    public function installBuilder(Request $request)
    {
        // Build input validator
        $validator = Validator::make($request->all(), [
            'edition' => ['required',
                Rule::in(['FLEX', 'PSO5', 'PSO6'])
            ],
            'version' => 'required|string|min:1|max:20',
            'values_file' => 'file',
        ]);

        // Validate input and redirect to start of the wizard in case of an error
        if ($validator->fails()) {
            return redirect('/settings/config-builder/install-helper')
                ->withErrors($validator)
                ->withInput();
        }

        $validatedInputs = $validator->validated();
        $edition = $validatedInputs['edition'];
        $version = $validatedInputs['version'];
        if (array_key_exists('values_file', $validatedInputs)) {
            $yaml = $request->file('values_file')->get();
            try {
                $psoValues = yaml_parse($yaml);
            } catch (\ErrorException $e) {
                $request->session()->flash('alert-class', 'alert-danger');
                $request->session()->flash(
                    'message',
                    'The format of the values.yaml file supplied is invalid, please upload a valid file'
                );
                $request->session()->flash('source', 'generic');
                return redirect()->back();
            }

            if (array_key_exists('clusterID', $psoValues)) {
                $edition = 'PSO6';
            } else {
                if (array_key_exists('image', $psoValues)) {
                    if (array_key_exists('tag', $psoValues['image'])) {
                        if (substr($psoValues['image']['tag'], 0, 1) == 5) {
                            $edition = 'PSO5';
                        } else {
                            $edition = 'FLEX';
                        }
                    }
                }
            }
        } else {
            // If GitHub was selected, download the file from the GitHub repo
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

            // Download values.yaml from GitHub
            try {
                $myGit = new GitHubApi($repoUri, $valuesUri);
                $yaml = $myGit->values($version);
                if ($yaml == '404: Not Found') {
                    // TODO: Error handling
                    $yaml = '[]';
                }
            } catch (ConnectionException $e) {
                unset($e);
                // TODO: Error handling
                $yaml = '[]';
            }

            if ($yaml !== '[]') {
                $psoValues = yaml_parse($yaml);
            } else {
                $request->session()->flash('alert-class', 'alert-danger');
                $request->session()->flash('message', 'Unable to download a valid values.yaml from GitHub');
                $request->session()->flash('source', 'generic');
                return view('/settings/config-builder/install-helper');
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

        $settings['psoEdition'] = $edition;
        $settings['provisionerTag'] = $version;
        return view('settings/config-builder/config-builder', [
            'psoValues' => $psoValues,
            'settings' => $settings,
        ]);
    }

    public function installFinal(Request $request)
    {
        // Build input validator
        $validator = Validator::make($request->all(), [
            'edition' => ['required',
                Rule::in(['FLEX', 'PSO5', 'PSO6'])
            ],
            'version' => 'required|string|min:1|max:20',
            'arrays' => 'required|array|min:1',
            'clusterID:string' => 'string|min:1|max:22',
        ]);

        // Validate input and redirect to start of the wizard in case of an error
        if ($validator->fails()) {
            return redirect('/settings/config-builder/install-helper')
                ->withErrors($validator)
                ->withInput();
        }

        $psoValues = [];
        $inputs = $request->all();
        if (isset($inputs['arrays'])) {
            if (isset($inputs['arrays']['FlashArray'])) {
                $flasharrays = [];
                foreach ($inputs['arrays']['FlashArray'] as $key => $value) {
                    if ($value['Labels'] !== null) {
                        $inputLabels = explode(',', $value['Labels']);
                        $newLabel = [];
                        foreach ($inputLabels as $inputLabel) {
                            $input = explode(':', $inputLabel);
                            $newLabel[trim($input[0])] = str_replace('"', '', trim($input[1]));
                        }
                        $value['Labels'] = $newLabel;
                    } else {
                        unset($value['Labels']);
                    }
                    array_push($flasharrays, $value);
                }
                $psoValues['arrays']['FlashArrays'] = $flasharrays;
            }
            if (isset($inputs['arrays']['FlashBlade'])) {
                $flashblades = [];
                foreach ($inputs['arrays']['FlashBlade'] as $key => $value) {
                    if ($value['Labels'] !== null) {
                        $inputLabels = explode(',', $value['Labels']);
                        $newLabel = [];
                        foreach ($inputLabels as $inputLabel) {
                            $input = explode(':', $inputLabel);
                            $newLabel[trim($input[0])] = str_replace('"', '', trim($input[1]));
                        }
                        $value['Labels'] = $newLabel;
                    } else {
                        unset($value['Labels']);
                    }
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



        // Convert our $psoValues to YAML
        $yaml = yaml_emit($psoValues);
        $settings['psoEdition'] = $inputs['edition'];
        $settings['provisionerTag'] = $inputs['version'];
        $settings['helmChart'] = 'pure-pso';

        return view('settings/config-builder/config-download', [
            'yaml' => $yaml,
            'isUpgrade' => false,
            'settings' => $settings,
        ]);
    }

    public function upgradeHelper(Request $request)
    {
        // Get PSO instance
        $pso = new Pso();

        $settings = $pso->settings();
        return view('settings/config-builder/upgrade-helper', ['settings' => $settings]);
    }

    public function upgradeSource(Request $request)
    {
        // Validate input
        $validatedInputs = $request->validate([
            'mode' => [
                'required',
                Rule::in(['github', 'upload'])
            ],
        ]);

        // Get PSO instance
        $pso = new Pso();
        $settings = $pso->settings();

        if ($validatedInputs['mode'] == 'github') {
            $releases = $this->getReleases($pso->psoInfo->psoEdition, $pso->psoInfo->repoUri, $pso->psoInfo->valuesUri);
            return view(
                'settings/config-builder/upgrade-github',
                ['settings' => $settings, 'releases' => $releases]
            );
        } else {
            return view('settings/config-builder/upgrade-upload', ['settings' => $settings]);
        }
    }

    public function upgradeFinal(Request $request)
    {
        // Validate input
        $validatedInputs = $request->validate([
            'version' => 'string|min:3|max:10',
            'values_file' => 'file',
        ]);

        // Either a release or values_file needs to be submitted. Otherwise we cannot proceed.
        if (!array_key_exists('version', $validatedInputs) and !array_key_exists('values_file', $validatedInputs)) {
            return redirect()->back();
        }

        // Get PSO instance
        $pso = new Pso();
        if (!$pso->psoFound) {
            return redirect()->back();
        }

        // Get the values.yaml file
        if (array_key_exists('version', $validatedInputs)) {
            // If GitHub was selected, download the file from the GitHub repo
            $repoUri = $pso->psoInfo->repoUri ?? '';
            $valuesUri = $pso->psoInfo->valuesUri ?? '';
            $version = $validatedInputs['version'];

            // Download values.yaml from GitHub
            try {
                $myGit = new GitHubApi($repoUri, $valuesUri);
                $yaml = $myGit->values($version);
                if ($yaml == '404: Not Found') {
                    // TODO: Error handling
                    $yaml = '[]';
                }
            } catch (ConnectionException $e) {
                unset($e);
                // TODO: Error handling
                $yaml = '[]';
            }

            if ($yaml !== '[]') {
                $psoValues = yaml_parse($yaml);
            } else {
                $request->session()->flash('alert-class', 'alert-danger');
                $request->session()->flash('message', 'Unable to download a valid values.yaml from GitHub');
                $request->session()->flash('source', 'generic');
                return redirect()->back();
            }
        } elseif (array_key_exists('values_file', $validatedInputs)) {
            $yaml = $request->file('values_file')->get();
            try {
                $psoValues = yaml_parse($yaml);
            } catch (\ErrorException $e) {
                $request->session()->flash('alert-class', 'alert-danger');
                $request->session()->flash(
                    'message',
                    'The format of the values.yaml file supplied is invalid, please upload a valid file'
                );
                $request->session()->flash('source', 'generic');
                return redirect()->back();
            }

            $version = 'upload';
            if (array_key_exists('clusterID', $psoValues)) {
                if (array_key_exists('images', $psoValues)) {
                    if (array_key_exists('plugin', $psoValues['images'])) {
                        if (array_key_exists('tag', $psoValues['images']['plugin'])) {
                            $version = str_replace('v', '', $psoValues['images']['plugin']['tag']);
                        }
                    }
                }
            }
        }

        // Update the values.yaml file with the settings from our cluster

        // Set the clusterID or namespace.pure
        if (array_key_exists('namespace', $psoValues)) {
            if (array_key_exists('pure', $psoValues['namespace'])) {
                $psoValues['namespace']['pure'] = $pso->psoInfo->prefix;
            }
        } else {
            $psoValues['clusterID'] = $pso->psoInfo->prefix;
        }

        // Set the orchestrator.name as openshift or k8s
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

        // Set storagetopology.enable
        if (array_key_exists('enable', ($psoValues['storagetopology'] ?? []))) {
            if ($pso->psoInfo->psoStorageTopology ?? false) {
                $psoValues['storagetopology']['enable'] = true;
            } else {
                $psoValues['storagetopology']['enable'] = false;
            }
        }
        // Set storagetopology.strictTopology
        if (array_key_exists('strictTopology', ($psoValues['storagetopology'] ?? []))) {
            if ($pso->psoInfo->psoStrictTopology ?? false) {
                $psoValues['storagetopology']['strictTopology'] = true;
            } else {
                $psoValues['storagetopology']['strictTopology'] = false;
            }
        }

        // Set arrays section and change deprecated NfsEndPoint to NFSEndPoint if applicable
        $arrays = $pso->psoInfo->yaml;
        $arrays = str_replace('NfsEndPoint', 'NFSEndPoint', $arrays);
        $psoValues['arrays'] = yaml_parse($arrays)['arrays'] ?? [];

        // Set the values under the flasharray section
        if (array_key_exists('flasharray', $psoValues)) {
            // Set flasharray.sanType
            if (
                array_key_exists('sanType', $psoValues['flasharray'])
                and ($pso->psoInfo->sanType !== null)
            ) {
                $psoValues['flasharray']['sanType'] = $pso->psoInfo->sanType;
            }
            // Set flasharray.sanType
            if (
                array_key_exists('defaultFSType', $psoValues['flasharray'])
                and ($pso->psoInfo->faDefaultFsType !== null)
            ) {
                $psoValues['flasharray']['defaultFSType'] = $pso->psoInfo->faDefaultFsType;
            }
            // Set flasharray.defaultFSOpt
            if (
                array_key_exists('defaultFSOpt', $psoValues['flasharray'])
                and ($pso->psoInfo->faDefaultFSOpt !== null)
            ) {
                $psoValues['flasharray']['defaultFSOpt'] = $pso->psoInfo->faDefaultFSOpt;
            }
            // Set flasharray.defaultMountOpt
            if (
                array_key_exists('defaultMountOpt', $psoValues['flasharray'])
                and ($pso->psoInfo->faDefaultMountOpt !== null)
            ) {
                if (is_array($pso->psoInfo->faDefaultMountOpt)) {
                    $psoValues['flasharray']['defaultMountOpt'] = $pso->psoInfo->faDefaultMountOpt;
                } else {
                    $psoValues['flasharray']['defaultMountOpt'] = [$pso->psoInfo->faDefaultMountOpt];
                }
            }
            // Set flasharray.faPreemptAttachments
            if (
                array_key_exists('preemptAttachments', $psoValues['flasharray'])
                and ($pso->psoInfo->faPreemptAttachments !== null)
            ) {
                $psoValues['flasharray']['preemptAttachments'] = $pso->psoInfo->faPreemptAttachments;
            }
            // Set flasharray.iSCSILoginTimeout
            if (
                array_key_exists('iSCSILoginTimeout', $psoValues['flasharray'])
                and ($pso->psoInfo->faIscsiLoginTimeout !== null)
            ) {
                $psoValues['flasharray']['iSCSILoginTimeout'] = intval($pso->psoInfo->faIscsiLoginTimeout);
            }
        }

        // Set the values under the flashblade section
        if (array_key_exists('flashblade', $psoValues)) {
            // Set flashblade.snapshotDirectoryEnabled
            if (array_key_exists('snapshotDirectoryEnabled', $psoValues['flashblade'])) {
                $psoValues['flashblade']['snapshotDirectoryEnabled'] = $pso->psoInfo->enableFbNfsSnapshot;
            }
            // Set flashblade.exportRules
            if (array_key_exists('exportRules', $psoValues['flashblade'])) {
                $psoValues['flashblade']['exportRules'] = $pso->psoInfo->nfsExportRules;
            }
        }

        // All of the following sections are not touched:
        // images, clusterrolebinding, nodeSelector, tolerations, affinity, nodeServer
        // controllerServer, database

        // Any sections that have no value, should be removed before converting our variable to YAML
        foreach ($psoValues as $key => $value) {
            // If there are no settings in a key of type array, remove the key
            if ((is_array($value)) and (count($value) == 0)) {
                unset($psoValues[$key]);
            }

            // If there are no settings in a subkey of type array, remove the key
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

        // Convert our $psoValues to YAML
        $yaml = yaml_emit($psoValues);
        $settings = $pso->settings();
        $settings['provisionerTag'] = $version;

        return view('settings/config-builder/config-download', [
            'yaml' => $yaml,
            'isUpgrade' => true,
            'settings' => $settings,
        ]);
    }

    public function deleteDbvols(Request $request)
    {
        $pso = $this->getPso($request);

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
                    $apiToken = new PsoArray($item['pureArrayMgmtEndPoint']);
                    $volumes[$array_count]['api'] = $apiToken->apiToken;
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

        return view('settings/delete_dbvols', ['ansible_yaml' => $ansible_yaml]);
    }
}
