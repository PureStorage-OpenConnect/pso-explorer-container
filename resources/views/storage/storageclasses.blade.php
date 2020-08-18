@extends('layouts.portal')

@section('css')
    <link href="{{ asset('css/plugins/footable/footable.core.css') }}" rel="stylesheet">
@endsection

@section('content')
    {{-- StorageClasses --}}
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Storage Usage --}}
                <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>PSO StorageClasses ({{ count($psoStorageClasses ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Storageclass</th>
                                        <th>Number of volumes</th>
                                        <th>Provisioned size</th>
                                        <th data-hide="all">Is default class</th>
                                        <th data-hide="all">Parameters</th>
                                        <th data-hide="all">Mount options</th>
                                        <th data-hide="all">Allow Volume Expansion</th>
                                        <th data-hide="all">Volume Binding Mode</th>
                                        <th data-hide="all">Reclaim Policy</th>
                                        <th data-hide="all">Allowed Topologies</th>
                                        <th>Used capacity</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoStorageClasses)
                                        @foreach($psoStorageClasses as $pso_storageclass)
                                            <tr>
                                                <td>{{ $pso_storageclass['name'] ?? '<unknown>' }} @if($pso_storageclass['isDefaultClass'] == 1)(default)@endif</td>
                                                <td>{{ $pso_storageclass['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_storageclass['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>@if($pso_storageclass['isDefaultClass'] == 1)True @else False @endif</td>
                                                <td>@isset($pso_storageclass['parameters']){{ implode(', ', $pso_storageclass['parameters']) }}@endisset </td>
                                                <td>@isset($pso_storageclass['mountOptions']){{ implode(', ', $pso_storageclass['mountOptions']) }}@endisset </td>
                                                <td>@if($pso_storageclass['allowVolumeExpansion'] == 1)True @else False @endif</td>
                                                <td>{{ $pso_storageclass['volumeBindingMode'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_storageclass['reclaimPolicy'] ?? '<unknown>' }}</td>
                                                <td>
                                                    @foreach(($pso_storageclass['allowedTopologies'] ?? []) as $topology)
                                                        {{ $topology }}<br>
                                                    @endforeach
                                                </td>
                                                <td>{{ $pso_storageclass['usedFormatted'] ?? '<unknown>' }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($psoStorageClasses) == 0)
                                            <tr>
                                                <td><i>No StorageClasses found</i></td>
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
                                        <td colspan="4">
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


    {{-- VolumeSnapshotClasses --}}
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Storage Usage --}}
                <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>PSO VolumeSnapshotClasses ({{ count($psoVolumeSnapshotClasses ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">VolumeSnapshotClass</th>
                                        <th>Number of snapshots</th>
                                        <th>Provisioned size</th>
                                        <th data-hide="all">Snapshotter</th>
                                        <th data-hide="all">Reclaim Policy</th>
                                        <th data-hide="all">Is default class</th>
                                        <th>Used capacity</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoVolumeSnapshotClasses)
                                        @foreach($psoVolumeSnapshotClasses as $pso_volumesnapshotclass)
                                            <tr>
                                                <td>{{ $pso_volumesnapshotclass['name'] ?? '<unknown>' }} @if($pso_volumesnapshotclass['isDefaultClass'] == 1)(default)@endif</td>
                                                <td>{{ $pso_volumesnapshotclass['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_volumesnapshotclass['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_volumesnapshotclass['snapshotter'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_volumesnapshotclass['reclaimPolicy'] ?? '<unknown>' }}</td>
                                                <td>@if($pso_volumesnapshotclass['isDefaultClass'] == 1)True @else False @endif</td>
                                                <td>{{ $pso_volumesnapshotclass['usedFormatted'] ?? '<unknown>' }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($psoVolumeSnapshotClasses) == 0)
                                            <tr>
                                                <td><i>No VolumeSnapshotClasses found</i></td>
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
                                        <td colspan="4">
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
