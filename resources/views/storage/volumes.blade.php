@extends('layouts.portal')

@section('css')
    <link href="/css/plugins/footable/footable.core.css" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Storage Usage --}}
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Managed Persistent Volume Claims ({{ count($psoVols ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter2" placeholder="Search in table" value="{{ $volume_keyword ?? ' ' }}">
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
                                        <th data-hide="all">Creation time</th>
                                        <th data-hide="all">Storageclass</th>
                                        <th data-hide="all">Persistent volume</th>
                                        <th data-hide="all">Labels</th>
                                        <th data-hide="all">Storage array</th>
                                        <th data-hide="all">Array volume name</th>
                                        <th data-hide="all">Data reduction</th>
                                        <th data-hide="all">IOPS (read/write)</th>
                                        <th data-hide="all">Bandwidth (read/write)</th>
                                        <th data-hide="all">Latency (read/write)</th>
                                        <th data-hide="all">Volume snapshots</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoVols)
                                        @foreach($psoVols as $vol)
                                            <tr @if (($vol['pv']['pureName'] == $volume_keyword) and ($volume_keyword !== '')) class="footable-detail-show"@endif>
                                                <td>{{ $vol['namespace'] ?? ' ' }}</td>
                                                <td>{{ $vol['name'] ?? ' ' }}</td>
                                                <td>{{ $vol['pv']['pureSizeFormatted'] ?? ' ' }}</td>
                                                <td>{{ $vol['pv']['pureUsedFormatted'] ?? ' '}}</td>
                                                <td>@if($vol['pv']['pureReadsPerSec'] !== null){{ number_format($vol['pv']['pureReadsPerSec'], 0) }} / {{ number_format($vol['pv']['pureWritesPerSec'], 0) }}@endif </td>
                                                <td>@if($vol['pv']['pureOutputPerSecFormatted'] !== null){{ $vol['pv']['pureOutputPerSecFormatted'] }} / {{ $vol['pv']['pureInputPerSecFormatted'] }}@endif </td>

                                                <td>{{ $vol['creationTimestamp'] ?? ' ' }}</td>
                                                <td>{{ $vol['storageClassName'] ?? ' ' }}</td>
                                                <td>{{ $vol['volumeName'] ?? ' ' }}</td>
                                                <td>@isset($vol['labels']){{ implode(', ', $vol['labels']) }}@endisset </td>
                                                <td><a href="https://{{ $vol['pv']['pureArrayMgmtEndPoint'] }}" target="_blank">{{ $vol['pv']['pureArrayName'] }}</a> </td>
                                                @if ($vol['pv']['pureArrayType'] == 'FA')
                                                    <td><a href="https://{{ $vol['pv']['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $vol['pv']['pureName'] }}" target="_blank">{{ $vol['pv']['pureName'] }}</a> </td>
                                                @else
                                                    <td><a href="https://{{ $vol['pv']['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $vol['pv']['pureName'] }}" target="_blank">{{ $vol['pv']['pureName'] }}</a> </td>
                                                @endif
                                                <td>{{ number_format($vol['pv']['pureDrr'] ?? 1 , 1) }}:1 </td>
                                                <td>{{ number_format($vol['pv']['pureReadsPerSec' ?? 0], 0) }} / {{ number_format($vol['pv']['pureWritesPerSec'] ?? 0, 0) }}</td>
                                                <td>{{ $vol['pv']['pureOutputPerSecFormatted'] ?? 0 }} / {{ $vol['pv']['pureInputPerSecFormatted'] ?? 0 }}</td>
                                                <td>{{ $vol['pv']['pureUsecPerReadOp'] ?? 0 }} / {{ $vol['pv']['pureUsecPerWriteOp'] ?? 0 }} ms </td>

                                                @if($vol['hasSnaps'])
                                                    <td><a href="{{ route('Storage-Snapshots', ['volume_keyword' => $vol['namespace'] . ' ' . $vol['name']], false) }}">View snapshots</a> </td>
                                                @else
                                                    <td><i>No Volume Snapshots</i> </td>
                                                @endif
                                                @if($vol['status_phase'] == 'Bound')
                                                    <td><span class="label label-success">{{ $vol['status_phase'] }}</span> </td>
                                                @else
                                                    <td><span class="label label-warning">{{ $vol['status_phase'] }}</span> </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(count($psoVols) == 0)
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
                                        <td colspan="7">
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
                            <span>Unclaimed or Orphaned Volumes ({{ count($orphanedVols ?? []) }})</span>
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
                                        <th data-hide="all">Claim reference name</th>
                                        <th data-hide="all">Claim reference namespace</th>
                                        <th>State</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($orphanedVols)
                                        @foreach($orphanedVols as $vol)
                                            <tr>
                                                <td><a href="https://{{ $vol['pureArrayMgmtEndPoint'] }}" target="_blank">{{ $vol['pureArrayName'] }}</a></td>
                                                @if ($vol['pureArrayType'] == 'FA')
                                                    <td><a href="https://{{ $vol['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $vol['pureName'] }}" target="_blank">{{ $vol['pureName'] }}</a></td>
                                                @else
                                                    <td><a href="https://{{ $vol['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $vol['pureName'] }}" target="_blank">{{ $vol['pureName'] }}</a></td>
                                                @endif
                                                <td>{{ $vol['pureSizeFormatted'] }}</td>
                                                <td>{{ $vol['pureUsedFormatted'] }}</td>
                                                <td>{{ number_format($vol['pureDrr'], 1) }}:1</td>
                                                <td>{{ $vol['claimRef_name'] ?? ' ' }}</td>
                                                <td>{{ $vol['claimRef_namespace'] ?? ' ' }}</td>

                                                @if($vol['isReleased'])
                                                    <td><span class="label label-success">{{ $vol['status_phase'] }}</span></td>
                                                @else
                                                    <td><span class="label label-warning">Unmanaged by PSO</span></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(count($orphanedVols) == 0)
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
                                        <td colspan="6">
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
    <script src="/js/plugins/footable/footable.all.min.js"></script>

    <script>
        $(document).ready(function() {

            $('.footable').footable();

            var element = document.getElementById('tablefilter2');
            var event = new Event('keyup');
            element.dispatchEvent(event);
        });
    </script>
@endsection
