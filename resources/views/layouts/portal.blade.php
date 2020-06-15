<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Metadata -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>{{ config('app.name', 'Laravel') . ' - ' . config('app.version', 'Laravel')}}</title>

    <!-- CSS Files -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('font-awesome/css/font-awesome.css') }}" rel="stylesheet">

    <link href="{{ asset('css/animate.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <!-- Blade specific css -->
    @yield('css')

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
</head>

<body>

<div id="wrapper">

    {{-- NAVBAR LEFT --}}
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">
                <li class="nav-header">
                    <div class="dropdown profile-element">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <span class="block m-t-xs font-bold">PSO Analytics</span>
                            <span class="text-muted text-xs block">menu <b class="caret"></b></span>
                        </a>
                        <ul class="dropdown-menu animated fadeInRight m-t-xs">
                            <li><a class="dropdown-item" href="#"
                               onclick="event.preventDefault();
                                                     document.getElementById('refresh-form').submit();">
                                Reload data
                            </a></li>
                            <form id="refresh-form" action="{{ route('RefreshData') }}" method="POST" style="display: none;">
                                @csrf
                                <input value="{{ Route::current()->getName() }}" name="route" id="frm1_submit" />
                            </form>

                        </ul>
                    </div>
                    <div class="logo-element">
                        PSO
                    </div>
                </li>

                <!-- Dashboard screen -->
                <li @IF(Request::is('/'))class="active"@ENDIF>
                    <a href="/"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard</span></a>
                </li>

                @ISSET($portal_info['last_refesh'])
                <!-- Overview menu -->
                <li @IF(Request::is('view/*'))class="active"@ENDIF>
                    <a href="#"><i class="fa fa-sliders"></i> <span class="nav-label">Overviews</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li @IF(Request::is('view/storagearrays'))class="active"@ENDIF><a href="{{ route('StorageArrays') }}">Storage arrays</a></li>
                        <li @IF(Request::is('view/namespaces'))class="active"@ENDIF><a href="{{ route('Namespaces') }}">Namespaces</a></li>
                        <li @IF(Request::is('view/storageclasses'))class="active"@ENDIF><a href="{{ route('StorageClasses') }}">StorageClasses</a></li>
                        <li @IF(Request::is('view/labels'))class="active"@ENDIF><a href="{{ route('Labels') }}">Labels</a></li>
                        <li @IF(Request::is('view/deployments'))class="active"@ENDIF><a href="{{ route('Deployments') }}">Deployments</a></li>
                        <li @IF(Request::is('view/statefulsets'))class="active"@ENDIF><a href="{{ route('StatefulSets') }}">StatefullSets</a></li>
                        <li @IF(Request::is('view/volumes'))class="active"@ENDIF><a href="{{ route('Volumes') }}">All volumes</a></li>
                    </ul>
                </li>
                @ENDISSET
            </ul>
        </div>
    </nav>

    {{-- MAIN SECTION --}}
    <div id="page-wrapper" class="gray-bg">

        {{-- MAIN TITLE --}}
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top white-bg" role="navigation" style="margin-bottom: 0">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                </div>

                @ISSET($portal_info['last_refesh'])
                <ul class="nav navbar-top-links navbar-right">
                    <li style="padding: 20px">
                        <span class="m-r-sm text-muted welcome-message">
                            Last refresh {{ $portal_info['last_refesh'] }}
                            <a href="#" onclick="event.preventDefault(); document.getElementById('refresh-form').submit();"><i class="fa fa-refresh"></i></a>
                        </span>
                    </li>
                </ul>
                @ENDISSET
            </nav>
        </div>

        <div class="row wrapper border-bottom white-bg page-heading">
            <div class="col-lg-12">
                @IF (Route::has(Route::currentRouteName() . 'Api'))
                <div class="ibox-tools">
                    <form action="{{ Route(Route::currentRouteName() . 'Api') }}" method="get">
                        <input type="submit" class="btn btn-w-m btn-success" value="View as JSON"
                               name="Submit" id="frm1_submit" />
                    </form>
                </div>
                @ENDIF
                <h2>
                    {{ Route::currentRouteName() }}
                </h2>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="wrapper wrapper-content animated fadeInRight">
            {{-- MESSAGES SECTION --}}
            @IF (session('message') or $errors->any())
                <div class="ibox">
                    <div class="ibox-title">
                        <h5>System messages</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    @IF(session('message') !== null)
                        <div class="ibox-content" style="">
                            <div class="alert {{ Session::get('alert-class', 'alert-info') }}">
                                {{ session('message') }}
                            </div>
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
            @ENDIF

            {{-- Show error message if K8S is not found --}}
            @IF (session('source') !== null)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox ">
                            <div class="ibox-title">
                                @IF (session('source') == 'k8s')
                                    <h5>Unable to connect to Kubernetes cluster</h5>
                                @ELSE
                                    <h5>Pure Service Orchestrator not found</h5>
                                @ENDIF

                                <div class="ibox-tools">
                                    <a class="collapse-link">
                                        <i class="fa fa-chevron-up"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="ibox-content">
                                @if (session('source') == 'k8s')
                                    <p><strong>We received an error while connecting to the Kubernetes cluster</strong></p>
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
            @ENDIF

            {{-- CONTENT SECTION --}}
            @yield('content')
        </div>

        {{-- MAIN FOOTER --}}
        <div class="footer fixed">
            @isset($portal_info)
            <div class="pull-right">
                <strong>{{ $portal_info['total_used'] ?? 'unknown' }}</strong> used of <strong>{{ $portal_info['total_size'] ?? 'unknown' }}</strong> capacity provisioned.
            </div>
            @endisset
            <div>
                <strong>Copyright</strong> Remko Deenik &copy; 2020
            </div>
        </div>

    </div>
</div>

<!-- Mainly scripts -->
<script src="{{ asset('js/jquery-3.1.1.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
<script src="{{ asset('js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>

<!-- Custom and plugin javascript -->
<script src="{{ asset('js/inspinia.js') }}"></script>
<script src="{{ asset('js/plugins/pace/pace.min.js') }}"></script>

<!-- Blade specific scripts -->
@yield('script')

</body>
</html>
