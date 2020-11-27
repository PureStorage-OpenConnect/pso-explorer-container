@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($yaml)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO {{ $isUpgrade ? 'upgrade' : 'installation' }} helper</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <div class="with-padding">
                                    <div class="heading">
                                        <strong>How to {{ $isUpgrade ? 'upgrade' : 'install' }} Pure Storage® Pure Service Orchestrator™</strong>
                                    </div>
                                    <div>
                                        Please follow the steps below to {{ $isUpgrade ? 'upgrade' : 'install' }} the Pure Service Orchestrator™.
                                    </div>
                                    <hr>
                                    <ul>
                                        @if(! $isUpgrade)
                                        <li>
                                            Make sure the namespace you wish to install PSO to is created.
                                            <div class="pre-scrollable">
                                                <pre>kubectl create namespace {{ $psoNamespace ?? 'pure-pso' }}</pre>
                                            </div>
                                        </li>
                                            @if(($settings['psoEdition'] !== 'FLEX') and ($settings['psoEdition'] !== 'PSO5'))
                                                <li>
                                                    (Optional) If you want to use Snapshots, make sure you install the Snapshot Beta CRDs:
                                                    <pre>kubectl create -f  https://raw.githubusercontent.com/kubernetes-csi/external-snapshotter/release-2.0/config/crd/snapshot.storage.k8s.io_volumesnapshotclasses.yaml
kubectl create -f  https://raw.githubusercontent.com/kubernetes-csi/external-snapshotter/release-2.0/config/crd/snapshot.storage.k8s.io_volumesnapshotcontents.yaml
kubectl create -f  https://raw.githubusercontent.com/kubernetes-csi/external-snapshotter/release-2.0/config/crd/snapshot.storage.k8s.io_volumesnapshots.yaml</pre>
                                                </li>
                                                <li>
                                                    (Optional) To use Snapshots, you also need to install the Snapshot Controller:
                                                    <pre>kubectl apply -n default -f https://raw.githubusercontent.com/kubernetes-csi/external-snapshotter/release-2.0/deploy/kubernetes/snapshot-controller/rbac-snapshot-controller.yaml
kubectl apply -n default -f https://raw.githubusercontent.com/kubernetes-csi/external-snapshotter/release-2.0/deploy/kubernetes/snapshot-controller/setup-snapshot-controller.yaml</pre>
                                                </li>
                                            @endif
                                        @endif
                                        <li>
                                            Download the <code>values.yaml</code> file you've created below.
                                        </li>
                                        <li>
                                            Run the following commands from the directory where you've saved the <code>values.yaml</code> file.
                                            <div class="pre-scrollable">
                                                @if($settings['psoEdition'] == 'FLEX')
                                                    <pre>helm repo add pure https://purestorage.github.io/helm-charts
helm repo update
helm {{ $isUpgrade ? 'upgrade' : 'install' }} {{ $settings['helmChart'] }} {{ env('FLEX_HELM') }} @if($settings['provisionerTag'] !== 'upload')--version {{ $settings['provisionerTag'] ?? '' }} @endif()--namespace {{ $psoNamespace ?? 'pure-pso' }} -f values.yaml</pre>
                                                @elseif(($settings['psoEdition'] == 'PSO5'))
                                                        <pre>helm repo add pure https://purestorage.github.io/helm-charts
helm repo update
helm {{ $isUpgrade ? 'upgrade' : 'install' }} {{ $settings['helmChart'] }} {{ env('PSO5_HELM') }} @if($settings['provisionerTag'] !== 'upload')--version {{ '1'. substr($settings['provisionerTag'], 1) ?? '' }} @endif()--namespace {{ $psoNamespace ?? 'pure-pso' }} -f values.yaml</pre>
                                                @else
                                                    @if(substr(str_replace('v', '', $settings['provisionerTag']), 0, 5) == '6.0.0')
                                                        <pre>helm repo add pure https://purestorage.github.io/pso-csi
helm repo update
helm {{ $isUpgrade ? 'upgrade' : 'install' }} {{ $settings['helmChart'] }} {{ str_replace('pure/pure-pso', 'pure/pureStorageDriver', env('PSO6_HELM')) }} --version 6.0.0 --namespace {{ $psoNamespace ?? 'pure-pso' }} -f values.yaml</pre>
                                                    @else
                                                    <pre>helm repo add pure https://purestorage.github.io/pso-csi
helm repo update
helm {{ $isUpgrade ? 'upgrade' : 'install' }} {{ $settings['helmChart'] }} {{ env('PSO6_HELM') }} @if($settings['provisionerTag'] !== 'upload')--version {{ $settings['provisionerTag'] ?? '' }} @endif()--namespace {{ $psoNamespace ?? 'pure-pso' }} -f values.yaml</pre>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-pure btn-block" type="button" value="save" id="save">Download <b>values.yaml</b></button>
                                <pre id="values_yaml">{!! $yaml !!}</pre>
                                <br><br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset
@endsection

@section('script')
    <script>
        function saveTextAsFile()
        {
            var textToWrite = document.getElementById('values_yaml').innerText;
            var textFileAsBlob = new Blob([textToWrite], {type:'text/plain'});
            var fileNameToSaveAs = "values.yaml";

            var downloadLink = document.createElement("a");
            downloadLink.download = fileNameToSaveAs;
            downloadLink.innerHTML = "Download File";
            if (window.webkitURL != null)
            {
                // Chrome allows the link to be clicked
                // without actually adding it to the DOM.
                downloadLink.href = window.webkitURL.createObjectURL(textFileAsBlob);
            }
            else
            {
                // Firefox requires the link to be added to the DOM
                // before it can be clicked.
                downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
                downloadLink.onclick = function(){
                    document.body.removeChild(downloadLink);
                };
                downloadLink.style.display = "none";
                document.body.appendChild(downloadLink);
            }

            downloadLink.click();
        }

        var button = document.getElementById('save');
        button.addEventListener('click', saveTextAsFile);
    </script>
@endsection
