@extends('layouts.portal')

@section('css')
    <link href="{{ asset('css/plugins/footable/footable.core.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Storage Usage --}}
                <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Managed Persistent Volume Claims</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter2" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter2>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Namespace</th>
                                        <th>Persistent volume claim</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>IOPS (R/W)</th>
                                        <th>Bandwidth (R/W)</th>
                                        <th data-hide="all">Storageclass</th>
                                        <th data-hide="all">Persistent volume</th>
                                        <th data-hide="all">Labels</th>
                                        <th data-hide="all">Storage array</th>
                                        <th data-hide="all">Array volume name</th>
                                        <th data-hide="all">Data reduction</th>
                                        <th data-hide="all">IOPS (read/write)</th>
                                        <th data-hide="all">Bandwidth (read/write)</th>
                                        <th data-hide="all">Latency (read/write)</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($pso_vols)
                                        @foreach($pso_vols as $vol)
                                            <tr>
                                                <td>{{ $vol['namespace'] ?? '' }}</td>
                                                <td>{{ $vol['name'] ?? '' }}</td>
                                                <td>{{ $vol['size'] ?? '' }}</td>
                                                <td>{{ $vol['pure_usedFormatted'] ?? ''}}</td>
                                                <td>{{ number_format($vol['pure_reads_per_sec'], 0) }} / {{ number_format($vol['pure_writes_per_sec'], 0) }}</td>
                                                <td>{{ $vol['pure_output_per_sec_formatted'] }} / {{ $vol['pure_input_per_sec_formatted'] }}</td>

                                                <td>{{ $vol['storageClass'] ?? '' }}</td>
                                                <td>{{ $vol['pv_name'] ?? '' }}</td>
                                                <td>@isset($vol['labels']){{ implode(', ', $vol['labels']) }}@endisset </td>
                                                <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}" target="_blank">{{ $vol['pure_arrayName'] }}</a></td>
                                                <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $vol['pure_name'] }}" target="_blank">{{ $vol['pure_name'] }}</a></td>
                                                <td>{{ number_format($vol['pure_drr'] ?? 1 , 1) }}:1</td>
                                                <td>{{ number_format($vol['pure_reads_per_sec'], 0) }} / {{ number_format($vol['pure_writes_per_sec'], 0) }}</td>
                                                <td>{{ $vol['pure_output_per_sec_formatted'] }} / {{ $vol['pure_input_per_sec_formatted'] }}</td>
                                                <td>{{ $vol['pure_usec_per_read_op'] }} / {{ $vol['pure_usec_per_write_op'] }}</td>

                                                @if($vol['status'] == 'Bound')
                                                    <td><span class="label label-success">{{ $vol['status'] }}</span></td>
                                                @else
                                                    <td><span class="label label-warning">{{ $vol['status'] }}</span></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(count($pso_vols) == 0)
                                            <tr>
                                                <td><i>No Volumes found</i></td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                            </tr>
                                        @endif
                                    @endisset
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <ul class="pagination float-right"></ul>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
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
                <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12" id="orphaned">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Unclaimed or orphaned volumes</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter1" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter1>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Storage array</th>
                                        <th>Array volume name</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>Data reduction</th>
                                        <th data-hide="all">Original claim name</th>
                                        <th data-hide="all">Original claim namespace</th>
                                        <th>State</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($orphaned_vols)
                                        @foreach($orphaned_vols as $vol)
                                            <tr>
                                                <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}" target="_blank">{{ $vol['pure_arrayName'] }}</a></td>
                                                <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $vol['pure_name'] }}" target="_blank">{{ $vol['pure_name'] }}</a></td>
                                                <td>{{ $vol['pure_sizeFormatted'] }}</td>
                                                <td>{{ $vol['pure_usedFormatted'] }}</td>
                                                <td>{{ number_format($vol['pure_drr'], 1) }}:1</td>
                                                <td>{{ $vol['pure_orphaned_pvc_name'] ?? '' }}</td>
                                                <td>{{ $vol['pure_orphaned_pvc_namespace'] ?? '' }}</td>

                                                @if($vol['pure_orphaned_state'] == 'Released PV')
                                                    <td><span class="label label-success">{{ $vol['pure_orphaned_state'] }}</span></td>
                                                @else
                                                    <td><span class="label label-warning">{{ $vol['pure_orphaned_state'] }}</span></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(count($orphaned_vols) == 0)
                                            <tr>
                                                <td><i>No orphaned or released volumes found</i></td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                            </tr>
                                        @endif
                                    @endisset
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <ul class="pagination float-right"></ul>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/plugins/footable/footable.all.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            $('.footable').footable();

        });
    </script>
@endsection
