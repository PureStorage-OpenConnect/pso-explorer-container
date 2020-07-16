@extends('layouts.portal')

@section('css')
    <meta http-equiv="refresh" content="300">
@endsection

@section('content')
    @isset($portalInfo)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">

                    {{-- Volumes summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Volumes</span>
                                @if($dashboard['orphaned_count'] > 0)
                                <a href="{{ route('Storage-Volumes') . '#orphaned'}}" >
                                    <span class="label label-warning float-right">{{ $dashboard['orphaned_count'] }} unclaimed</span>
                                </a>
                                @endif
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding align-middle">
                                        <h1 class="no-margin"><a href="{{ route('Storage-Volumes') }}">{{ ($dashboard['volume_count'] - $dashboard['orphaned_count']) ?? 0 }}</a></h1>
                                        <small>Persistent Volume Claims</small>
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
                                        <h1 class="no-margin"><a href="{{ route('Storage-StorageClasses') }}">{{ $dashboard['storageclass_count'] ?? 0 }}</a></h1>
                                        <small>Used for provisioning</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- StatefulSets summary --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-6 col-lg-3">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Snapshots</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding">
                                        <h1 class="no-margin"><a href="{{ route('Storage-Snapshots') }}">{{ $dashboard['snapshot_count'] ?? 0 }}</a></h1>
                                        <small>Volume Snapshots</small>
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
                                <a href="{{ route('Storage-StorageArrays')}}" >
                                    <span class="label label-warning float-right">{{ $dashboard['offline_array_count'] }} offline</span>
                                </a>
                                @endif
                            </div>
                            <div class="panel-body list-container">
                                <div class="list-section">
                                    <div class="no-padding">
                                        <h1 class="no-margin"><a href="{{ route('Storage-StorageArrays') }}">{{ $dashboard['array_count'] ?? 0 }}</a></h1>
                                        <small>Configured in cluster</small>
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
                                            <th>Top 10 block volumes</th>
                                            <th>Size</th>
                                            <th>Usage</th>
                                            <th>24h growth</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @ISSET($dashboard['top10_growth_vols'])
                                        @FOREACH($dashboard['top10_growth_vols'] as $vol)
                                            <tr>
                                                <td><a href="{{ route('Storage-Volumes', ['volume_keyword' => $vol['pure_name']]) }}">{{ $vol['name'] }}</a></td>
                                                @IF($vol['status'] == 'Bound')
                                                <td>{{ $vol['sizeFormatted']}}</td>
                                                @ELSE
                                                    <td><i>{{ $vol['status']}}</i></td>
                                                @ENDIF

                                                @if(($vol['used'] !== null) and ($vol['size'] !== null))
                                                    <td>{{ number_format($vol['used']/$vol['size'] * 100, 1) }}%</td>
                                                @else
                                                    <td> </td>
                                                @endif
                                                @if ($vol['growthPercentage'] > 0)
                                                    <td class="text-navy text-danger">
                                                        ↑
                                                        @IF($vol['status'] == 'Bound')
                                                            {{ number_format($vol['growthPercentage'], 2) }}%
                                                        @ENDIF
                                                    </td>
                                                @elseif (($vol['growthPercentage'] < 0))
                                                    <td class="text-navy text-success">
                                                        ↓
                                                        @IF($vol['status'] == 'Bound')
                                                            {{ number_format($vol['growthPercentage'], 2) }}%
                                                        @ENDIF
                                                    </td>
                                                    @endif
                                                </td>
                                            </tr>
                                        @ENDFOREACH
                                        @ELSE
                                        <tr>
                                            <td><i>No historic volume data</i></td>
                                            <td> </td>
                                            <td> </td>
                                            <td> </td>
                                        </tr>
                                        @ENDISSET
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-xs-4 col-md-4 col-lg-4">
                                    <div class="row border-bottom">
                                        <div class="col-12 with-padding-top border-bottom">
                                            <p style="margin: 0 0 9px;"><strong>Storage Performance</strong></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom">
                                        <div class="col-12">
                                            <br><p><strong>IOPS</strong></p>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Total</small>
                                            <h4>{{ number_format($portalInfo['total_iops_read'] + $portalInfo['total_iops_write'], 0) }}</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Read</small>
                                            <h4>{{ number_format($portalInfo['total_iops_read'], 0) }}</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Write</small>
                                            <h4>{{ number_format($portalInfo['total_iops_write'], 0) }}</h4>
                                        </div>
                                    </div>
                                    <div class="row border-bottom">
                                        <div class="col-12">
                                            <br><p><strong>Bandwidth</strong></p>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Total</small>
                                            <h4>{{ $portalInfo['total_bw'] }}/s</h4>
                                        </div>

                                        <div class="col-4">
                                            <small class="stats-label">Read</small>
                                            <h4>{{ $portalInfo['total_bw_read'] }}/s</h4>
                                        </div>
                                        <div class="col-4">
                                            <small class="stats-label">Write</small>
                                            <h4>{{ $portalInfo['total_bw_write'] }}/s</h4>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <br><p><strong>Latency (block only)</strong></p>
                                        </div>
                                        <div class="col-6">
                                            <small class="stats-label">Range for reads</small>
                                            <h4>{{ $portalInfo['low_msec_read'] }} ms - {{ $portalInfo['high_msec_read'] }} ms</h4>
                                        </div>
                                        <div class="col-6">
                                            <small class="stats-label">Range for writes</small>
                                            <h4>{{ $portalInfo['low_msec_write'] }} ms - {{ $portalInfo['high_msec_write'] }} ms</h4>
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
    @isset($portalInfo)
    <script src="{{ asset('js/plugins/chartJs/Chart.min.js') }}"></script>

    <script>
        $(function() {
            var doughnutData = {
                labels: ["Used (Gi)", "Snapshots (Gi)", "Provisioned (Gi)", "Unclaimed (Gi)" ],
                datasets: [{
                    data: [{{ $portalInfo['total_used_raw']/1024/1024/1024 }}, {{ $portalInfo['total_snapshot_raw']/1024/1024/1024 }}, {{ ($portalInfo['total_size_raw'] - $portalInfo['total_used_raw'])/1024/1024/1024 }}, {{ $portalInfo['total_orphaned_raw']/1024/1024/1024 }}],
                    backgroundColor: ["#52c8fd", "#b5a1dd", "#f4f2f3","#b8bebe"]
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
