@extends('layouts.portal')

@section('css')
    <link href="{{ asset('css/plugins/footable/footable.core.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="row" id="volumes">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">
                    <h5>Persistent volumes under management of Pure Service Orchestrators</h5>

                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <input type="text" class="form-control form-control-sm m-b-xs" id="volfilter" placeholder="Search in table">
                    <table class="footable table table-stripped toggle-arrow-tiny" data-filter=#volfilter>
                        <thead>
                        <tr>
                            <th data-toggle="true">Namespace</th>
                            <th>Persistent volume claim</th>
                            <th>Provisioned size</th>
                            <th>Used capacity</th>
                            <th>IOPS (R/W)</th>
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

                                    <td>{{ $vol['storageClass'] ?? '' }}</td>
                                    <td>{{ $vol['pv_name'] ?? '' }}</td>
                                    <td>@isset($vol['labels']){{ implode(',', $vol['labels']) }}@endisset </td>
                                    <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}" target="_blank">{{ $vol['pure_arrayName'] }}</a></td>
                                    <td><a href="https://{{ $vol['pure_arrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $vol['pure_name'] }}" target="_blank">{{ $vol['pure_name'] }}</a></td>
                                    <td>{{ number_format($vol['pure_drr'] ?? 1 , 1) }}:1</td>
                                    <td>{{ number_format($vol['pure_reads_per_sec'], 0) }} / {{ number_format($vol['pure_writes_per_sec'], 0) }}</td>
                                    <td>{{ $vol['pure_input_per_sec_formatted'] }} / {{ $vol['pure_output_per_sec_formatted'] }}</td>
                                    <td>{{ $vol['pure_usec_per_read_op'] }} / {{ $vol['pure_usec_per_write_op'] }}</td>

                                    @if($vol['status'] == 'Bound')
                                        <td><span class="badge badge-primary">{{ $vol['status'] }}</span></td>
                                    @else
                                        <td><span class="badge badge-warning">{{ $vol['status'] }}</span></td>
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
    <div class="row" id="orphaned">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">
                    <h5>Orphaned volumes, no longer under management of PSO</h5>

                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <input type="text" class="form-control form-control-sm m-b-xs" id="orphanedfilter" placeholder="Search in table">
                    <table class="footable table table-stripped toggle-arrow-tiny" data-filter=#orphanedfilter>
                        <thead>
                        <tr>
                            <th data-toggle="true">Storage array</th>
                            <th>Array volume name</th>
                            <th>Provisioned size</th>
                            <th>Used capacity</th>
                            <th>Data reduction</th>
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
                                </tr>
                            @endforeach
                            @if(count($orphaned_vols) == 0)
                                <tr>
                                    <td><i>No orphaned Volumes found</i></td>
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
@endsection

@section('script')
    <script src="{{ asset('js/plugins/footable/footable.all.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            $('.footable').footable();

        });
    </script>
@endsection
