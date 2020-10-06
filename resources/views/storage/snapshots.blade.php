@extends('layouts.portal')

@section('css')
    <link href="/css/plugins/footable/footable.core.css" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Volume Snapshots --}}
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Volume Snapshots ({{ count($psoVolsnaps ?? []) - count($orphanedSnaps ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter1" placeholder="Search in table" value="{{ $volume_keyword ?? '' }}">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter1>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Namespace</th>
                                        <th>Snapshot source</th>
                                        <th>Snapshot name</th>
                                        <th>Provisioned size</th>
                                        <th>Array used space</th>
                                        <th data-hide="all">Creation time</th>
                                        <th data-hide="all">Status message</th>
                                        <th data-hide="all">Volume Snapshot Class</th>
                                        <th data-hide="all">Snapshot Content</th>
                                        <th data-hide="all">Source kind</th>
                                        <th data-hide="all">Storage array</th>
                                        <th data-hide="all">Array snapshot name</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoVolsnaps)
                                        @foreach($psoVolsnaps as $volsnap)
                                            @if($volsnap['orphaned'] == null)
                                            <tr>
                                                <td>{{ $volsnap['namespace'] ?? '' }}</td>

                                                <td><a href="{{ route('Storage-Volumes', ['volume_keyword' => $volsnap['namespace'] . ' ' . $volsnap['sourceName']], false) }}">{{ $volsnap['sourceName'] ?? '' }}</a></td>

                                                <td>{{ $volsnap['name'] ?? '' }}</td>

                                                <td>{{ $volsnap['pureSizeFormatted'] ?? '' }}</td>
                                                <td>{{ $volsnap['pureUsedFormatted'] ?? '' }}</td>

                                                <td>{{ $volsnap['creationTimestamp'] ?? '' }}</td>
                                                <td>
                                                    @if($volsnap['errorMessage'] !== null)
                                                        {{ $volsnap['errorMessage'] }}
                                                    @else
                                                        &nbsp;
                                                    @endif
                                                </td>
                                                <td>{{ $volsnap['snapshotClassName'] ?? '' }}</td>
                                                <td>{{ $volsnap['snapshotContentName'] ?? '' }}</td>
                                                <td>{{ $volsnap['sourceKind'] ?? '' }}</td>

                                                <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}" target="_blank">{{ $volsnap['pureArrayName'] }}</a></td>
                                                @if ($volsnap['pureArrayType'] == 'FA')
                                                    <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $volsnap['pureVolName'] }}" target="_blank">{{ $volsnap['pureName'] }}</a></td>
                                                @else
                                                    <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $volsnap['pureVolName'] }}" target="_blank">{{ $volsnap['pureName'] }}</a></td>
                                                @endif

                                                @if($volsnap['readyToUse'] == 1)
                                                    <td><span class="label label-success">Ready to use</span></td>
                                                @elseif($volsnap['readyToUse'] !== '')
                                                    <td><span class="label label-success">{{ $volsnap['readyToUse'] }}</span></td>
                                                @else
                                                    <td><span class="label label-warning">Pending</span></td>
                                                @endif
                                            </tr>
                                            @endif
                                        @endforeach
                                        @if(count($psoVolsnaps) == 0)
                                            <tr>
                                                <td><i>No Volume Snaphots found</i></td>
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
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Orphaned Volume Snapshots --}}
                <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12" id="orphaned">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Unmanaged Volume Snapshots ({{ count($orphanedSnaps ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter2" placeholder="Search in table" value="{{ $volume_keyword ?? '' }}">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter2>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Volume name</th>
                                        <th>Provisioned size</th>
                                        <th>Array used space</th>
                                        <th data-hide="all">Status message</th>
                                        <th data-hide="all">Storage array</th>
                                        <th data-hide="all">Array snapshot name</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($orphanedSnaps)
                                        @foreach($orphanedSnaps as $volsnap)
                                            @if($volsnap['orphaned'] !== null)
                                                <tr>
                                                    <td><a href="{{ route('Storage-Volumes', ['volume_keyword' => $volsnap['pureVolName']], false) }}">{{ $volsnap['pureVolName'] ?? '' }}</a></td>

                                                    <td>{{ $volsnap['pureSizeFormatted'] ?? '' }}</td>
                                                    <td>{{ $volsnap['pureUsedFormatted'] ?? '' }}</td>

                                                    <td>
                                                        @if($volsnap['errorMessage'] !== null)
                                                            {{ $volsnap['errorMessage'] }}
                                                        @else
                                                            &nbsp;
                                                        @endif
                                                    </td>
                                                    <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}" target="_blank">{{ $volsnap['pureArrayName'] }}</a></td>
                                                    @if ($volsnap['pureArrayType'] == 'FA')
                                                        <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $volsnap['pureVolName'] }}" target="_blank">{{ $volsnap['pureName'] }}</a></td>
                                                    @else
                                                        <td><a href="https://{{ $volsnap['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $volsnap['pureVolName'] }}" target="_blank">{{ $volsnap['pureName'] }}</a></td>
                                                    @endif

                                                    @if($volsnap['readyToUse'] == 1)
                                                        <td><span class="label label-success">Ready to use</span></td>
                                                    @elseif($volsnap['readyToUse'] !== '')
                                                        <td><span class="label label-success">{{ $volsnap['readyToUse'] }}</span></td>
                                                    @else
                                                        <td><span class="label label-warning">Pending</span></td>
                                                    @endif
                                                </tr>
                                            @endif
                                        @endforeach
                                        @if(count($psoVolsnaps) == 0)
                                            <tr>
                                                <td><i>No Volume Snaphots found</i></td>
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

            var element = document.getElementById('tablefilter1');
            var event = new Event('keyup');
            element.dispatchEvent(event);
            var element = document.getElementById('tablefilter2');
            var event = new Event('keyup');
            element.dispatchEvent(event);
        });
    </script>
@endsection
