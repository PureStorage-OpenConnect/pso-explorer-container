{{-- Show error message if K8S is not found --}}
@if ((session('source') !== null) and (session('source') !== 'generic'))
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            @if (session('source') == 'k8s')
                                <span>Error while connecting to Kubernetes</span>
                            @elseif (session('source') == 'refresh')
                                <span>{{ config('app.name', 'PSO eXplorer') }} is currently collecting data</span>
                            @else
                                <span>Pure Service Orchestrator™ not found</span>
                            @endif
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding margin-left">
                                @if (session('source') == 'k8s')
                                    <div class="alert alert-danger alert-message">We ran into an error while connecting to the Kubernetes API service. To resolve this issue, make sure {{ config('app.name', 'PSO eXplorer') }} has access to the Kubernetes API services and that the roles and rolebindings are configured correctly.

                                        For more information on how to install and configure {{ config('app.name', 'PSO eXplorer') }} correctly, please visit: <br><a href="https://github.com/PureStorage-OpenConnect/pure-container-explorer" target="_blank">https://github.com/PureStorage-OpenConnect/pure-container-explorer</a>
                                    </div>
                                @elseif (session('source') == 'refresh')
                                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 text-center">
                                        <h4>Please standby, while we are collecting data...</h4>
                                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                            <img src="/images/spinner.gif" class="rounded mx-auto d-block" alt="Loading...">
                                        </div>
                                        <div>We are collecting data from your environment.</div>
                                        <div>This should only take a couple of seconds. This page will reload automatically.</div>
                                    </div>
                                @else
                                    <div class="alert-message"><strong>The Pure Storage® Pure Service Orchestrator™ was not found or not correctly configured</strong>
                                    Please make sure you have installed the Pure Service Orchstrator™ (PSO) in your Kubernetes cluster.

                                    For installation instruction of PSO, please visit<br>
                                    <a href="https://github.com/purestorage/helm-charts" target="_blank">https://github.com/purestorage/helm-charts</a>
                                    For installation instruction of PSO v6, please visit<br>
                                    <a href="https://github.com/purestorage/pso-csi" target="_blank">https://github.com/purestorage/pso-csi</a>

                                    <strong>Validation of values.yaml syntax:</strong>
                                    Also make sure your values.yaml file is formatted as shown below. Please note that YAML is case sensitive

                                    <table style="vertical-align:top; width:100%">
                                        <tr>
                                            <td style="vertical-align:top; width:45%">
                                                <strong>Correct usage</strong>
                                                <pre>arrays:
  FlashArrays:
    - MgmtEndPoint: "IP address"
      APIToken: "API token"
  FlashBlades:
    - MgmtEndPoint: "IP address"
      APIToken: "API token"
      NfsEndPoint: "IP address"</pre>

                                                <p>Or when using labels:</p>

                                                <pre>arrays:
  FlashArrays:
    - MgmtEndPoint: "IP address"
      APIToken: "API token"
      Labels:
        topology.purestorage.com/datacenter: "my datacenter"
  FlashBlades:
    - MgmtEndPoint: "IP address"
      APIToken: "API token"
      NfsEndPoint: "IP address"
      Labels:
        topology.purestorage.com/datacenter: "my datacenter"</pre>
                                            </td>
                                            <td style="vertical-align:top; width:10%"> </td>
                                            <td style="vertical-align:top; width:45%">
                                                <strong>Current settings for PSO</strong>
                                                <pre>{{ session('yaml') }}</pre>
                                            </td>
                                        </tr>
                                    </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Show error messages if set --}}
@if (session('message') or $errors->any())
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Error message returned</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding margin-left">
                                @if(session('message') !== null)
                                    <div class="alert alert-message {{ Session::get('alert-class', 'alert-info') }}">{{ session('message') }}</div>
                                @endif
                                @if ($errors->any())
                                    @foreach ($errors->all() as $error)
                                        <div class="ibox-content" style="">
                                            <div class="alert alert-danger alert-message">{{ $error }}</div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif