<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Metadata -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>{{ config('app.name', 'PSO eXplorer') . ' - ' . config('app.version', 'v0.0.1')}}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="/images/favicon.ico">

    <!-- CSS Files -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/metisMenu.min.css" rel="stylesheet">
    <link href="/css/common.css" rel="stylesheet">
    <link href="/css/page.css" rel="stylesheet">
    <link href="/css/pso.css" rel="stylesheet">

    <!-- Blade specific css -->
    @yield('css')

    @if (session('source') == 'refresh')
        <meta http-equiv="refresh" content="3">
    @endif
</head>

<body onload="settime()">
@if((getenv('PSOX_ANONYMOUS_ACCESS') == "true") or Auth::check())
    <div id="wrapper">

        <!-- Navigation sidebar -->
        <sidebar>
        <div id="sidebar-wrapper">
            <!-- Pure Storage® logo -->
            <div id="sidebar-branding">
                <pure-logo>
                    <svg viewBox="0 0 356.9 62.6" x="0px" y="0px">
                        <style type="text/css"> .pure-logo-bug {
                                fill: #FE5000;
                            }

                            .pure-logo-text {
                                fill: #FFF;
                            } </style>
                        <path class="pure-logo-bug" d="M37.2,62.6H21.8c-3.2,0-6.1-1.7-7.7-4.5L1.2,35.8c-1.6-2.7-1.6-6.2,0-8.9L14.1,4.5C15.7,1.7,18.7,0,21.8,0h25.9
            c3.2,0,6.1,1.7,7.7,4.5l12.9,22.4c1.6,2.7,1.6,6.2,0,8.9L65,41.1c-1.6,2.7-4.5,4.4-7.7,4.4H41.9l8.4-14.2l-7.8-13.5H27l-7.8,13.5
            L37.2,62.6L37.2,62.6z"></path>
                        <!---->
                        <g>
                            <g>
                                <path class="pure-logo-text" d="M92.5,45.7h-7.2V18h10.2c6.5,0,10.5,3,10.5,8.8c0,6.5-4.7,9.7-11.4,9.7h-2.1V45.7z M94.1,31
                    c2.8,0,4.5-0.8,4.5-3.7c0-2.8-1.5-3.6-4.1-3.6h-2V31H94.1z"></path>
                                <path class="pure-logo-text" d="M117.4,18v18.1c0,3.2,1.6,4.1,3.9,4.1c2.2,0,3.7-1.3,3.7-3.9V18h7.2v19.2c0,5.6-4.5,9-11,9
                    c-7.2,0-10.9-2.9-10.9-9.7V18H117.4z"></path>
                                <path class="pure-logo-text"
                                      d="M162.8,18H181v6.3h-11v4.2h9.4v6.3H170v4.8h11.3v6.3h-18.4V18z"></path>
                                <path class="pure-logo-text" d="M188.2,41c1.3,0.9,4.3,2.4,7.5,2.4c2.8,0,5.8-0.9,5.8-5.1c0-3.3-2.9-4.4-6.3-5.3c-4-1.1-7.9-2.6-7.9-7.7
                    c0-4.6,3.6-7.7,8.8-7.7c3.8,0,7,1.8,9,3.7l-1.8,2.2c-2.2-1.9-4.6-3.1-7.2-3.1c-2.3,0-5.3,1-5.3,4.5c0,3.3,2.8,4.3,6.4,5.3
                    c3.8,1.2,7.8,2.7,7.8,7.9c0,5.2-3.5,8.1-9.3,8.1c-4,0-7.7-1.6-8.8-2.5L188.2,41z"></path>
                                <path class="pure-logo-text"
                                      d="M228.3,18v2.8h-8.4v24.9h-3.3V20.8h-8.3V18H228.3z"></path>
                                <path class="pure-logo-text" d="M241.6,46.2c-7.1,0-11.4-4.8-11.4-14.3c0-10.7,6.2-14.4,11.8-14.4c6,0,11.2,3.8,11.2,14.4
                    C253.3,41.5,248.8,46.2,241.6,46.2z M242,43.4c6,0,7.9-5.7,7.9-11.4c0-4.9-1.6-11.7-8-11.7c-6.1,0-8.3,5.8-8.3,11.5
                    C233.6,37.3,235.1,43.4,242,43.4z"></path>
                                <path class="pure-logo-text" d="M280.2,45.7l9.8-27.7h3.9l10,27.7h-3.5l-3-8.5h-10.9l-3,8.5H280.2z M287.2,34.5h9.3
                    C291.8,21,291.8,21,291.8,21L287.2,34.5z"></path>
                                <path class="pure-logo-text" d="M326.1,43.7c-2.3,1.5-5.6,2.5-9.2,2.5c-7.3,0-11.8-4.6-11.8-14.3c0-10.8,6.3-14.4,11.8-14.4
                    c2.8,0,6.5,0.9,9.3,4.5l-2.4,1.9c-1.8-2.2-4-3.5-7-3.5c-5.3,0-8.4,4.3-8.4,11.3c0,7.5,2.7,11.9,8.7,11.9c2.2,0,4.6-0.8,5.6-1.5
                    v-8.3h-6.3V31h9.6V43.7z"></path>
                                <path class="pure-logo-text"
                                      d="M331.7,18h15.5v2.8H335v9.3h10.5V33H335v9.9h12.5v2.8h-15.8V18z"></path>
                                <path class="pure-logo-text" d="M273.6,37c-0.6-1.4-1-2.1-1.9-2.5c3.1-1.4,5-4.1,5-8.2c0-5.4-3.5-8.3-9.3-8.3h-9v27.7h3.3V35.5h4.8
                    c0.3,0,0.7,0,1,0l0,0c1.7,0,2.5,1.1,3.2,2.6l3.5,7.6h3.5L273.6,37z M265.7,32.8h-4.2V20.7h3.6c4.7,0,8,0.5,8,6
                    C273.3,31.2,270.2,32.8,265.7,32.8z"></path>
                                <path class="pure-logo-text" d="M155.5,36.9c-0.4-1-1.1-1.8-1.8-2.2c2.6-1.6,4.2-4.2,4.2-8c0-5.8-4-8.8-10.5-8.8h-10.2v27.7h7.2v-9.2h1.8
                    c1,0.1,1.6,0.6,2,1.6l3.4,7.6h7.7L155.5,36.9z M144.4,30.9v-7.3h2c2.6,0,4.1,0.8,4.1,3.6c0,2.9-1.7,3.7-4.5,3.7H144.4z"></path>
                            </g>
                            <g>
                                <path class="pure-logo-text" d="M353.2,24.6c-2.1,0-3.7-1.6-3.7-3.7c0-2.2,1.7-3.7,3.7-3.7c2,0,3.7,1.5,3.7,3.7
                    C356.9,23.2,355.2,24.6,353.2,24.6z M353.2,17.8c-1.6,0-2.9,1.3-2.9,3.1c0,1.7,1.1,3.1,2.9,3.1c1.6,0,2.9-1.3,2.9-3.1
                    C356.1,19.1,354.8,17.8,353.2,17.8z M352.5,23.1h-0.7v-4.1h1.6c1,0,1.5,0.3,1.5,1.2c0,0.7-0.5,1.1-1.1,1.1l1.2,1.8h-0.8l-1.1-1.8
                    h-0.6V23.1z M353.2,20.7c0.5,0,1-0.1,1-0.6c0-0.5-0.5-0.6-0.9-0.6h-0.8v1.2H353.2z"></path>
                            </g>
                        </g>
                    </svg>
                </pure-logo>
            </div>

            <!-- Navivation side bar -->
            <nav class="sidebar-nav">
                <ul id="sidebar-menu">
                    {{-- Dashboard --}}
                    <li @if(Request::is('/'))class="sidebar-item dropdown mm-active"
                        @else()class="sidebar-item dropdown"@endif>
                        <a class="nav-link" href="{{ route('Dashboard', [], false) }}">
                            <!-- Dashboard -->
                            <div class="nav-content">
                                <img class="mm-main" src="/images/dashboard_icon.svg">
                                <span class="sidebar-expanded-only">Dashboard</span>
                            </div>
                        </a>
                    </li>

                    {{-- Analysis --}}
                    <li class="sidebar-item dropdown">
                        <a class="nav-link" href="#" aria-expanded="false">
                            <div class="nav-content">
                                <img class="mm-main" src="/images/analysis_icon.svg">
                                <span class="sidebar-expanded-only">Analysis</span>
                            </div>
                        </a>
                        <ul class="mm-collapse">
                            <li @if(Request::is('analysis/pods'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-Pods', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/pod-pure.svg">
                                    <span class="mm-sub-text">Pods</span>
                                </a>
                            </li>
                            <li @if(Request::is('analysis/jobs'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-Jobs', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/job-pure.svg">
                                    <span class="mm-sub-text">Jobs</span>
                                </a>
                            </li>
                            <li @if(Request::is('analysis/deployments'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-Deployments', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/deploy-pure.svg">
                                    <span class="mm-sub-text">Deployments</span>
                                </a>
                            </li>
                            <li @if(Request::is('analysis/statefulsets'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-StatefulSets', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/sts-pure.svg">
                                    <span class="mm-sub-text">StatefulSets</span>
                                </a>
                            </li>
                            <li @if(Request::is('analysis/labels'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-Labels', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/cm-pure.svg">
                                    <span class="mm-sub-text">Labels</span>
                                </a>
                            </li>
                            <li @if(Request::is('analysis/namespaces'))class="mm-active"@endif>
                                <a href="{{ route('Analysis-Namespaces', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/ns-pure.svg">
                                    <span class="mm-sub-text">Namespaces</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Storage --}}
                    <li class="sidebar-item dropdown">
                        <a class="nav-link" href="#" aria-expanded="false">
                            <div class="nav-content">
                                <img class="mm-main" src="/images/k8s/storage.svg">
                                <span class="sidebar-expanded-only">Storage</span>
                            </div>
                        </a>
                        <ul class="mm-collapse">
                            <li @if(Request::is('storage/storagearrays'))class="mm-active"@endif>
                                <a href="{{ route('Storage-StorageArrays', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/storage.svg">
                                    <span class="mm-sub-text">Arrays</span>
                                </a>
                            </li>
                            <li @if(Request::is('storage/storageclasses'))class="mm-active"@endif>
                                <a href="{{ route('Storage-StorageClasses', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/sc-pure.svg">
                                    <span class="mm-sub-text">StorageClasses</span>
                                </a>
                            </li>
                            <li @if(Request::is('storage/volumes'))class="mm-active"@endif>
                                <a href="{{ route('Storage-Volumes', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/vol-pure.svg">
                                    <span class="mm-sub-text">Volumes</span>
                                </a>
                            </li>
                            <li @if(Request::is('storage/snapshots'))class="mm-active"@endif>
                                <a href="{{ route('Storage-Snapshots', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/snap-pure.svg">
                                    <span class="mm-sub-text">Snapshots</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Settings --}}
                    <li class="sidebar-item dropdown">
                        <a class="nav-link" href="#" aria-expanded="false">
                            <div class="nav-content">
                                <img class="mm-main" src="/images/settings_icon.svg">
                                <span class="sidebar-expanded-only">Settings</span>
                            </div>
                        </a>
                        <ul class="mm-collapse">
                            <li @if(Request::is('settings/pso'))class="mm-active"@endif>
                                <a href="{{ route('Settings-Pso', [], false) }}">
                                    <img class="mm-sub" src="/images/settings_icon.svg">
                                    <span class="mm-sub-text">PSO</span>
                                </a>
                            </li>
                            <li @if(Request::is('settings/nodes'))class="mm-active"@endif>
                                <a href="{{ route('Settings-Nodes', [], false) }}">
                                    <img class="mm-sub" src="/images/k8s/node-pure.svg">
                                    <span class="mm-sub-text">Nodes</span>
                                </a>
                            </li>
                            @if(getenv('PSOX_ALPHA_FEATURES') == "true")
                                <li @if(Request::is('settings/config'))class="mm-active"@endif>
                                    <a href="{{ route('Settings-Builder-Initialize', [], false) }}">
                                        <img class="mm-sub" src="/images/settings_icon.svg">
                                        <span class="mm-sub-text">Configuration</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                </ul>

                <div class="sidebar-nav-divider"></div>

                <!-- Sidebar links -->
                <div class="sidebar-text">
                    {{-- <a class="sidebar-info sidebar-link" href="/help" id="help">Help</a> --}}
                    <a class="sidebar-info sidebar-link" href="#"
                       onclick="event.preventDefault(); document.getElementById('refresh-form').submit();"><span
                                id="sidebar-refresh-link">Refresh data</span></a>
                    <a class="sidebar-info sidebar-link" href="#" onclick="toggleLicenseInfo()">
                        <spanid="sidebar-refresh-link">License info</span></a>
                    <a class="sidebar-info sidebar-link" href="http://www.purestorage.com/legal/productenduserinfo"
                       id="eula" target="_blank">Terms</a>

                    <form id="refresh-form" action="{{ route('RefreshData', [], false) }}" method="POST" style="display: none;">
                        @csrf
                        <input value="{{ Route::current()->getName() }}" name="route" id="frm1_submit"/>
                    </form>

                    @if((getenv('PSOX_ALPHA_FEATURES') == "true") or (getenv('PSOX_ANONYMOUS_ACCESS') !== "true"))
                        @guest
                            <a class="sidebar-info sidebar-link" href="{{ route('login', [], false) }}">Login</a>
                        @else
                            <a class="sidebar-info sidebar-link" href="{{ route('logout', [], false) }}"
                               onclick="event.preventDefault();
                                                             document.getElementById('logout-form').submit();">
                                Logout
                            </a>

                            <form id="logout-form" action="{{ route('logout', [], false) }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        @endguest
                    @endif
                </div>

                <!-- Sidebar footer -->
                <div id="sidebar-footer">
                    <div class="sidebar-nav-divider"></div>
                    <div class="sidebar-text">
                        <div class="sidebar-info">
                            <span><strong> {{ config('app.name', 'PSO eXplorer') }} </strong></span><br>
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
                            <div id="sidebar-info-refresh-time"><i>No data found</i></div>
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
                        <h4 class="inline-header page-title">{{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }}</h4>
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
                                        <div data-placement="bottom" data-toggle="tooltip"
                                             data-original-title="0 open warning alert(s)" title="">
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
                                        <div data-placement="bottom" data-toggle="tooltip"
                                             data-original-title="0 open critical alert(s)" title="">
                                            <alert-indicator>
                                                <div>
                                                    <alert-critical-icon>
                                                        {{-- Future use for errors --}}
                                                    </alert-critical-icon>
                                                </div>
                                            </alert-indicator>
                                        </div>

                                        <div class="empty-relative-container"><span
                                                    class="ps-floating-count-text"></span></div>
                                    </ps-count-tooltip>
                                </a></div>
                        </div>
                    </alert-counts>
                </div>
                <div class="topbar-item with-padding">
                    @if (Route::has(Route::currentRouteName() . '-Api'))
                        <form action="{{ Route(Route::currentRouteName() . '-Api', [], false) }}" method="get">
                            <input type="submit" class="btn btn-w-m btn-pure" value="View as JSON"
                                   name="Submit" id="frm2_submit"/>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Page content -->
            <div class="container-fluid" id="tab-content">
                @include('layouts.error')

                @yield('content')
            </div>
        </div>
        <div class="footer fixed" id="page-footer">
            @ISSET($portalInfo['totalUsed'])
                <div class="pull-right">
                    Currently <strong>{{ $portalInfo['totalUsed'] ?? 'unknown' }}</strong> of capacity is used out of
                    <strong>{{ $portalInfo['totalSize'] ?? 'unknown' }}</strong> provisioned capacity
                </div>
            @ENDISSET
            <div>
                <strong>Copyright</strong> Remko Deenik &copy; 2020
            </div>
        </div>
    </div>

    <modal backdrop="static" class="modal in" id="license-info" role="dialog" tabindex="-1" data-keyboard="true"
           data-backdrop="static" style="display: none;">
        <div class="modal-dialog"><!---->
            <div class="modal-content">
                <modal-header>
                    <div class="modal-header">
                        <h4 class="modal-title">{{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }} license statement</h4>
                    </div>
                </modal-header>
                <modal-body class="tab-container">
                    <div class="modal-body">
                        <div>
                            <h4>About {{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }}</h4>
                            <span class="pager">Pure Service Orchestrator™ eXplorer (or PSO eXplorer) provides a web based user interface for Pure Service Orchestrator™ PSO. It shows details of the persistent volumes and snapshots that have been provisioned using PSO, showing provisioned space, actual used space, performance and growth characteristics.</span><br>
                            <span class="pager">{{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }} is maintainced at:<br><a href="{{ env('PSOX_REPO', 'https://code.purestorage.com/') }}" target="_blank">{{ env('PSOX_REPO', 'https://code.purestorage.com/') }}</a></span><br>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div>
                            <h4>License statement</h4>
                            {{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }} licensed under the <a href="/docs/license.pdf" target="_blank">Apache License version 2.0</a>.<br><br>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div>
                            <h4>3rd Party/Open-Source Code</h4>
                            {{ config('app.fullname', 'Pure Service Orchestrator™ eXplorer') }} uses best of breed open source technologies as part of the solution. The following <a href="/docs/PSO_Explorer_Third_Party_Code.pdf" target="_blank">document</a> provides 3rd Party/Open-Source Code attribution.<br><br>
                        </div>

                    </div>
                </modal-body>
                <modal-footer>
                    <div class="modal-footer">
                        <button class="btn btn-default" onclick="toggleLicenseInfo()">Close</button>
                </modal-footer>
            </div>
        </div>
    </modal>

    <script src="/js/jquery.min.js"></script>
    <script src="/js/popper.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/metisMenu.min.js"></script>
    
    <script>
        $(function () {
            $('#sidebar-menu').metisMenu();
        });
    </script>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
    <script>
        $("#toggle-btn").click(function (e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");

            var x = document.getElementsByClassName("pure-logo-text");
            for (var i = 0, len = x.length | 0; i < len; i = i + 1 | 0) {
                if (x[i].style.display === "none") {
                    x[i].style.display = "block";
                } else {
                    x[i].style.display = "none";
                }
            }

            var y = document.getElementsByClassName("mm-sub-text");
            for (var i = 0, len = y.length | 0; i < len; i = i + 1 | 0) {
                if (y[i].style.display === "none") {
                    y[i].style.display = "inline";
                } else {
                    y[i].style.display = "none";
                }
            }

            settime();
        });
    </script>
    <script>
        function toggleLicenseInfo() {
            var x = document.getElementById("license-info");
            if (x.style.display === "none") {
                x.style.display = "inline";
            } else {
                x.style.display = "none";
            }
        }
    </script>
    {{-- Show localized refresh time --}}
    <script>
        function settime() {
            var timestamp = {{ $portalInfo['lastRefesh'] ?? 0 }}
            var date = new Date(timestamp * 1000);

            if (document.getElementById("wrapper").classList.contains("toggled")) {
                var s = date.getHours() + ":" + String(date.getMinutes()).padStart(2, "0") + ":" + String(date.getSeconds()).padStart(2, "0");
            } else {
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                var s = months[date.getMonth()] + " " + date.getDate() + " " + date.getFullYear() + " " + date.getHours() + ":" + String(date.getMinutes()).padStart(2, "0") + ":" + String(date.getSeconds()).padStart(2, "0");
            }

            if (timestamp !== 0) {
                document.getElementById("sidebar-info-refresh-time").innerHTML = s;
            } else {
                document.getElementById("sidebar-info-refresh-time").innerHTML = '<i>No data found</i>';
            }
        }
    </script>

    <!-- Blade specific scripts -->
    @yield('script')
@else
    <!-- Page content -->
    <div id="tab-content">
        @include('layouts.error')

        @yield('content')
    </div>
@endif

</body>
</html>
