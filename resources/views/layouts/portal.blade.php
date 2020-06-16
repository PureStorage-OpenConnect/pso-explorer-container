<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Metadata -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>{{ config('app.name', 'PSO Analytics GUI') . ' - ' . config('app.version', '0.1')}}</title>

    <!-- CSS Files -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/metisMenu.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/common.css') }}" rel="stylesheet">
    <link href="{{ asset('css/page.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pso.css') }}" rel="stylesheet">

    <!-- Blade specific css -->
    @yield('css')
</head>

<body>
<div id="wrapper">
    <!-- Navigation sidebar -->
    <sidebar>
        <div id="sidebar-wrapper">
            <!-- Pure Storage logo -->
            <div id="sidebar-branding">
                <pure-logo>
                    <svg viewBox="0 0 356.9 62.6" x="0px" y="0px">
                        <style type="text/css"> .pure-logo-bug{fill:#FE5000;} .pure-logo-text{fill: #FFF;} </style>
                        <path class="pure-logo-bug" d="M37.2,62.6H21.8c-3.2,0-6.1-1.7-7.7-4.5L1.2,35.8c-1.6-2.7-1.6-6.2,0-8.9L14.1,4.5C15.7,1.7,18.7,0,21.8,0h25.9
            c3.2,0,6.1,1.7,7.7,4.5l12.9,22.4c1.6,2.7,1.6,6.2,0,8.9L65,41.1c-1.6,2.7-4.5,4.4-7.7,4.4H41.9l8.4-14.2l-7.8-13.5H27l-7.8,13.5
            L37.2,62.6L37.2,62.6z"></path><!----><g><g><path class="pure-logo-text" d="M92.5,45.7h-7.2V18h10.2c6.5,0,10.5,3,10.5,8.8c0,6.5-4.7,9.7-11.4,9.7h-2.1V45.7z M94.1,31
                    c2.8,0,4.5-0.8,4.5-3.7c0-2.8-1.5-3.6-4.1-3.6h-2V31H94.1z"></path><path class="pure-logo-text" d="M117.4,18v18.1c0,3.2,1.6,4.1,3.9,4.1c2.2,0,3.7-1.3,3.7-3.9V18h7.2v19.2c0,5.6-4.5,9-11,9
                    c-7.2,0-10.9-2.9-10.9-9.7V18H117.4z"></path><path class="pure-logo-text" d="M162.8,18H181v6.3h-11v4.2h9.4v6.3H170v4.8h11.3v6.3h-18.4V18z"></path><path class="pure-logo-text" d="M188.2,41c1.3,0.9,4.3,2.4,7.5,2.4c2.8,0,5.8-0.9,5.8-5.1c0-3.3-2.9-4.4-6.3-5.3c-4-1.1-7.9-2.6-7.9-7.7
                    c0-4.6,3.6-7.7,8.8-7.7c3.8,0,7,1.8,9,3.7l-1.8,2.2c-2.2-1.9-4.6-3.1-7.2-3.1c-2.3,0-5.3,1-5.3,4.5c0,3.3,2.8,4.3,6.4,5.3
                    c3.8,1.2,7.8,2.7,7.8,7.9c0,5.2-3.5,8.1-9.3,8.1c-4,0-7.7-1.6-8.8-2.5L188.2,41z"></path><path class="pure-logo-text" d="M228.3,18v2.8h-8.4v24.9h-3.3V20.8h-8.3V18H228.3z"></path><path class="pure-logo-text" d="M241.6,46.2c-7.1,0-11.4-4.8-11.4-14.3c0-10.7,6.2-14.4,11.8-14.4c6,0,11.2,3.8,11.2,14.4
                    C253.3,41.5,248.8,46.2,241.6,46.2z M242,43.4c6,0,7.9-5.7,7.9-11.4c0-4.9-1.6-11.7-8-11.7c-6.1,0-8.3,5.8-8.3,11.5
                    C233.6,37.3,235.1,43.4,242,43.4z"></path><path class="pure-logo-text" d="M280.2,45.7l9.8-27.7h3.9l10,27.7h-3.5l-3-8.5h-10.9l-3,8.5H280.2z M287.2,34.5h9.3
                    C291.8,21,291.8,21,291.8,21L287.2,34.5z"></path><path class="pure-logo-text" d="M326.1,43.7c-2.3,1.5-5.6,2.5-9.2,2.5c-7.3,0-11.8-4.6-11.8-14.3c0-10.8,6.3-14.4,11.8-14.4
                    c2.8,0,6.5,0.9,9.3,4.5l-2.4,1.9c-1.8-2.2-4-3.5-7-3.5c-5.3,0-8.4,4.3-8.4,11.3c0,7.5,2.7,11.9,8.7,11.9c2.2,0,4.6-0.8,5.6-1.5
                    v-8.3h-6.3V31h9.6V43.7z"></path><path class="pure-logo-text" d="M331.7,18h15.5v2.8H335v9.3h10.5V33H335v9.9h12.5v2.8h-15.8V18z"></path><path class="pure-logo-text" d="M273.6,37c-0.6-1.4-1-2.1-1.9-2.5c3.1-1.4,5-4.1,5-8.2c0-5.4-3.5-8.3-9.3-8.3h-9v27.7h3.3V35.5h4.8
                    c0.3,0,0.7,0,1,0l0,0c1.7,0,2.5,1.1,3.2,2.6l3.5,7.6h3.5L273.6,37z M265.7,32.8h-4.2V20.7h3.6c4.7,0,8,0.5,8,6
                    C273.3,31.2,270.2,32.8,265.7,32.8z"></path><path class="pure-logo-text" d="M155.5,36.9c-0.4-1-1.1-1.8-1.8-2.2c2.6-1.6,4.2-4.2,4.2-8c0-5.8-4-8.8-10.5-8.8h-10.2v27.7h7.2v-9.2h1.8
                    c1,0.1,1.6,0.6,2,1.6l3.4,7.6h7.7L155.5,36.9z M144.4,30.9v-7.3h2c2.6,0,4.1,0.8,4.1,3.6c0,2.9-1.7,3.7-4.5,3.7H144.4z"></path></g><g><path class="pure-logo-text" d="M353.2,24.6c-2.1,0-3.7-1.6-3.7-3.7c0-2.2,1.7-3.7,3.7-3.7c2,0,3.7,1.5,3.7,3.7
                    C356.9,23.2,355.2,24.6,353.2,24.6z M353.2,17.8c-1.6,0-2.9,1.3-2.9,3.1c0,1.7,1.1,3.1,2.9,3.1c1.6,0,2.9-1.3,2.9-3.1
                    C356.1,19.1,354.8,17.8,353.2,17.8z M352.5,23.1h-0.7v-4.1h1.6c1,0,1.5,0.3,1.5,1.2c0,0.7-0.5,1.1-1.1,1.1l1.2,1.8h-0.8l-1.1-1.8
                    h-0.6V23.1z M353.2,20.7c0.5,0,1-0.1,1-0.6c0-0.5-0.5-0.6-0.9-0.6h-0.8v1.2H353.2z"></path></g></g>
                    </svg>
                </pure-logo>
            </div>

            <!-- Navivation side bar -->
            <nav class="sidebar-nav">
                <ul id="sidebar-menu">
                    <li @IF(Request::is('/'))class="sidebar-item dropdown mm-active"@ELSE()class="sidebar-item dropdown"@ENDIF>
                        <a class="nav-link" href="{{ route('Dashboard') }}">
                            <!-- Dashboard -->
                            <div class="nav-content">
                                <img class="nav-icon" src="/images/dashboard_icon.svg">
                                <span class="sidebar-expanded-only">Dashboard</span>
                            </div>
                        </a>
                    </li>

                    <li class="sidebar-item dropdown">
                        <a class="nav-link" href="#" aria-expanded="false">
                            <div class="nav-content">
                                <img class="nav-icon" src="/images/analysis_icon.svg">
                                <span class="sidebar-expanded-only">Analysis</span>
                            </div>
                        </a>
                        <ul class="mm-collapse">
                            <li @IF(Request::is('view/storagearrays'))class="mm-active"@ENDIF>
                                <a href="{{ route('StorageArrays') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> Storage arrays
                                </a>
                            </li>
                            <li @IF(Request::is('view/namespaces'))class="mm-active"@ENDIF>
                                <a href="{{ route('Namespaces') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> Namespaces
                                </a>
                            </li>
                            <li @IF(Request::is('view/storageclasses'))class="mm-active"@ENDIF>
                                <a href="{{ route('StorageClasses') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> StorageClasses
                                </a>
                            </li>
                            <li @IF(Request::is('view/labels'))class="mm-active"@ENDIF>
                                <a href="{{ route('Labels') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> Labels
                                </a>
                            </li>
                            <li @IF(Request::is('view/deployments'))class="mm-active"@ENDIF>
                                <a href="{{ route('Deployments') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> Deployments
                                </a>
                            </li>
                            <li @IF(Request::is('view/statefulsets'))class="mm-active"@ENDIF>
                                <a href="{{ route('StatefulSets') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> StatefullSets
                                </a>
                            </li>
                            <li @IF(Request::is('view/volumes'))class="mm-active"@ENDIF>
                                <a href="{{ route('Volumes') }}">
                                    <span class="fa fa-fw fa-code-fork"></span> All volumes
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <div class="sidebar-nav-divider"></div>

                <!-- Sidebar links -->
                <div class="sidebar-text">
                    {{-- <a class="sidebar-info sidebar-link" href="/help" id="help">Help</a> --}}
                    <a class="sidebar-info sidebar-link" href="http://www.purestorage.com/legal/productenduserinfo" id="eula" target="_blank">Terms</a>
                    <a class="sidebar-info sidebar-link" href="#" onclick="event.preventDefault(); document.getElementById('refresh-form').submit();">Refresh data</a>
                    <form id="refresh-form" action="{{ route('RefreshData') }}" method="POST" style="display: none;">
                        @csrf
                        <input value="{{ Route::current()->getName() }}" name="route" id="frm1_submit" />
                    </form>
                </div>

                <!-- Sidebar footer -->
                <div id="sidebar-footer">
                    <div class="sidebar-nav-divider"></div>
                    <div class="sidebar-text">
                        <div class="sidebar-info">
                            <span><strong> PSO Analytics GUI </strong></span><br>
                        </div>
                        <div class="sidebar-info">
                            Version
                            <div>
                                <span id="sidebar-array-version">
                                    <strong>{{ config('app.version', '0.1') }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="sidebar-info">
                            <div>Last refresh at:</div>
                            <div>
                                {!!  $portal_info['last_refesh'] ?? '<i>No valid data</i>' !!}
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </sidebar>

    <!-- Main content wrapper -->
    <div id="page-content-wrapper">
        <!-- Top bar title -->
        <topbar>
            <div id="topbar">
                <div id="topbar-title">
                    <div id="toggle-btn">
                        <img id="toggle-icon" src="/images/menu.svg">
                    </div>
                    <h4 class="inline-header page-title">PSO Analytics GUI</h4>
                </div>
            </div>
        </topbar>

        <!-- Top bar warning/error icons -->
        <div id="topbar-right">
            <div class="topbar-item">
                <alert-counts>
                    <div>
                        <div class="topbar-icon-with-count">
                            <a>
                                <ps-count-tooltip class="for-warning">
                                    <div data-placement="bottom" data-toggle="tooltip" data-original-title="0 open warning alert(s)" title="">
                                        <alert-indicator>
                                            <div>
                                                <alert-warning-icon>
                                                    {{-- Future use for warnings --}}
                                                </alert-warning-icon>
                                            </div>
                                        </alert-indicator>
                                    </div>
                                </ps-count-tooltip>
                            </a>
                        </div>

                        <div class="topbar-icon-with-count">
                            <a>
                                <ps-count-tooltip class="for-critical">
                                    <div data-placement="bottom" data-toggle="tooltip" data-original-title="0 open critical alert(s)" title="">
                                        <alert-indicator>
                                            <div>
                                                <alert-critical-icon>
                                                    {{-- Future use for errors --}}
                                                </alert-critical-icon>
                                            </div>
                                        </alert-indicator>
                                    </div>

                                    <div class="empty-relative-container"><span
                                            class="ps-floating-count-text">1</span></div>
                                </ps-count-tooltip>
                            </a></div>
                    </div>
                </alert-counts>
            </div>
            <div class="topbar-item with-padding">
                @IF (Route::has(Route::currentRouteName() . 'Api'))
                    <form action="{{ Route(Route::currentRouteName() . 'Api') }}" method="get">
                        <input type="submit" class="btn btn-w-m btn-pure" value="View as JSON"
                               name="Submit" id="frm1_submit" />
                    </form>
                @ENDIF
            </div>
        </div>

        <!-- Page content -->
        <div class="container-fluid" id="tab-content">

            {{-- Show error messages if set --}}
            @IF (session('message') or $errors->any())
                <div class="row">
                    <div class="col-xs-12 tab-container">
                        <div class="with-padding">
                            <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <span>System messages</span>
                                    </div>
                                    <div class="panel-body list-container">
                                        <div class="row with-padding margin-left">
                                            @IF(session('message') !== null)
                                                <div class="alert {{ Session::get('alert-class', 'alert-info') }}">
                                                    {{ session('message') }}
                                                </div>
                                            @ENDIF
                                            @IF ($errors->any())
                                                @foreach ($errors->all() as $error)
                                                    <div class="ibox-content" style="">
                                                        <div class="alert alert-danger">
                                                            {{ $error }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @ENDIF
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @ENDIF

            {{-- Show error message if K8S is not found --}}
            @IF (session('source') !== null)
                <div class="row">
                    <div class="col-xs-12 tab-container">
                        <div class="with-padding">
                            <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        @IF (session('source') == 'k8s')
                                            <span>Unable to connect to Kubernetes cluster</span>
                                        @ELSE
                                            <span>Pure Service Orchestrator not found</span>
                                        @ENDIF
                                    </div>
                                    <div class="panel-body list-container">
                                        <div class="row with-padding margin-left">


                                            @if (session('source') == 'k8s')
                                                <p><h3>Error while connecting to Kubernetes</h3></p>
                                                <p>We ran into an error while connecting to the Kubernetes API service. To resolve this issue, make sure {{ config('app.name', 'PSO Analytics GUI') }} has access to the Kubernetes API services and that the roles and rolebindings are configured correctly.</p>
                                                <p>For more information on how to install and configure {{ config('app.name', 'PSO Analytics GUI') }} correctly, please visit: <br><a href="https://github.com/PureStorage-OpenConnect/pso-analytics-gui" target="_blank">https://github.com/PureStorage-OpenConnect/pso-analytics-gui</a></p>
                                            @else
                                                <p><strong>The Pure Storage Pure Service Orchestrator was not foundor not correctly configured</strong></p>
                                                <p>Please make sure you have installed the Pure Service Orchstrator (PSO) in your Kubernetes cluster.</p>
                                                <p>
                                                    For installation instruction of PSO, please visit<br>
                                                    <a href="https://github.com/purestorage/helm-charts" target="_blank">https://github.com/purestorage/helm-charts</a>
                                                </p>

                                                <p><strong>Validation of values.yaml syntax:</strong></p>
                                                <p>Also make sure your values.yaml file is formatted as shown below. Please note that YAML is case sensitive</p>

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
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @ENDIF

            @yield('content')
        </div>
    </div>
    <div class="footer fixed">
        <div class="pull-right">
            Currently <strong>{{ $portal_info['total_used'] ?? 'unknown' }}</strong> of capacity is used out of <strong>{{ $portal_info['total_size'] ?? 'unknown' }}</strong> provisioned capacity
        </div>
    </div>
</div>

<script src="{{ asset('js/jquery.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/metisMenu.min.js') }}"></script>

<script>
    $(function () {
        $('#sidebar-menu').metisMenu();
    });

    $("#toggle-btn").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");

        var x = document.getElementsByClassName("pure-logo-text");
        for (var i=0, len=x.length|0; i<len; i=i+1|0) {
            if (x[i].style.display === "none") {
                x[i].style.display = "block";
            } else {
                x[i].style.display = "none";
            }
        }
    });
</script>

<!-- Blade specific scripts -->
@yield('script')

</body>
</html>