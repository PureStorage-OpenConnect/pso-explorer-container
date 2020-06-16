@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($portal_info)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">

                    {{-- Volumes summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Volumes</span>
                                @if($dashboard['orphaned_count'] > 0)
                                <a href="{{ route('Volumes') . '#orphaned'}}" >
                                    <span class="label label-warning float-right">{{ $dashboard['orphaned_count'] }} orphaned</span>
                                </a>
                                @endif
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding align-middle">
                                        <h1 class="no-margin"><a href="{{ route('Volumes') }}">{{ $dashboard['volume_count'] ?? 0 }}</a></h1>
                                        <small>Provisioned</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- StorageClasses summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>StorageClasses</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding">
                                        <h1 class="no-margin"><a href="{{ route('StorageClasses') }}">{{ $dashboard['storageclass_count'] ?? 0 }}</a></h1>
                                        <small>Used by PSO</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- StatefulSets summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>StatefulSets</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding">
                                        <h1 class="no-margin"><a href="{{ route('StatefulSets') }}">{{ $dashboard['statefulset_count'] ?? 0 }}</a></h1>
                                        <small>Provisioned using PSO</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrays summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Arrays</span>
                                @if ($dashboard['offline_array_count'] > 0)
                                <a href="{{ route('StorageArrays')}}" >
                                    <span class="label label-warning float-right">{{ $dashboard['offline_array_count'] }} offline</span>
                                </a>
                                @endif
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding">
                                        <h1 class="no-margin"><a href="{{ route('StorageArrays') }}">{{ $dashboard['array_count'] ?? 0 }}</a></h1>
                                        <small>Configured in PSO</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">

                    {{-- Storage Usage --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Storage Usage</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="col-xs-4 col-md-4 col-lg-4 no-gutter">
                                    <iframe class="chartjs-hidden-iframe" style="width: 100%; display: block; border: 0px; height: 0px; margin: 0px; position: absolute; left: 0px; right: 0px; top: 0px; bottom: 0px;"></iframe>
                                    <canvas id="doughnutChart" height="200" width="200" style="display: block; width: 330px; height: 200px;"></canvas>
                                </div>
                                <div class="col-xs-4 col-md-4 col-lg-4 no-gutter">
                                    <table class="table table-hover">
                                        <thead>
                                        <tr>
                                            <th>PVC name</th>
                                            <th>Size</th>
                                            <th>Usage</th>
                                            <th>24h growth</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($portal_info['top10_growth_vols'] as $vol)
                                            <tr>
                                                <td><a href="{{ route('Volumes') }}">{{ $vol['name'] }}</a></td>
                                                <td>{{ $vol['sizeFormatted']}}</td>

                                                @if(($vol['used'] !== null) and ($vol['size'] !== null))
                                                    <td>{{ number_format($vol['used']/$vol['size'] * 100, 1) }}%</td>
                                                @else
                                                    <td> </td>
                                                @endif
                                                <td class="text-navy">
                                                    @if ($vol['growthPercentage'] > 0)
                                                        <i class="fa fa-level-up"></i>
                                                    @elseif (($vol['growthPercentage'] < 0))
                                                        <i class="fa fa-level-down"></i>
                                                    @endif
                                                    {{ number_format($vol['growthPercentage'], 4) }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-xs-4 col-md-4 col-lg-4">
                                    <div class="row border-bottom">
                                        <div class="col-12 with-padding-top border-bottom">
                                            <p><strong>Storage Perfromance</strong></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom">
                                        <div class="col-12">
                                            <br><p><strong>IOPS</strong></p>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Total</small>
                                            <h4>{{ number_format($portal_info['total_iops_read'] + $portal_info['total_iops_write'], 0) }}</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Read</small>
                                            <h4>{{ number_format($portal_info['total_iops_read'], 0) }}</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Write</small>
                                            <h4>{{ number_format($portal_info['total_iops_write'], 0) }}</h4>
                                        </div>
                                    </div>
                                    <div class="row border-bottom">
                                        <div class="col-12">
                                            <br><p><strong>Bandwidth</strong></p>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Total</small>
                                            <h4>{{ $portal_info['total_bw'] }}/s</h4>
                                        </div>

                                        <div class="col-4">
                                            <small class="stats-label">Read</small>
                                            <h4>{{ $portal_info['total_bw_read'] }}/s</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Write</small>
                                            <h4>{{ $portal_info['total_bw_write'] }}/s</h4>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <br><p><strong>Latency</strong></p>
                                        </div>
                                        <div class="col-6">
                                            <small class="stats-label">Range for reads</small>
                                            <h4>{{ $portal_info['low_msec_read'] }} ms - {{ $portal_info['high_msec_read'] }} ms</h4>
                                        </div>
                                        <div class="col-6">
                                            <small class="stats-label">Range for writes</small>
                                            <h4>{{ $portal_info['low_msec_write'] }} ms - {{ $portal_info['high_msec_write'] }} ms</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset
@endsection

@section('script')
    @isset($portal_info)
    <script src="{{ asset('js/plugins/chartJs/Chart.min.js') }}"></script>

    <script>
        $(function() {
            var doughnutData = {
                labels: ["Used (Gi)","Provisioned (Gi)" ],
                datasets: [{
                    data: [{{ $portal_info['total_used_raw']/1024/1024/1024 }}, {{ $portal_info['total_size_raw']/1024/1024/1024 }}],
                    backgroundColor: ["#52c8fd","#f4f2f3"]
                }]
            } ;

            var doughnutOptions = {
                responsive: true
            };

            var ctx4 = document.getElementById("doughnutChart").getContext("2d");
            new Chart(ctx4, {type: 'doughnut', data: doughnutData, options:doughnutOptions});
        });
    </script>
    @endisset
@endsection
