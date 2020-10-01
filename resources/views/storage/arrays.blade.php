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
                            <span>Pure Storage® systems configured for this cluster ({{ count($psoArrays ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table" value="{{ $array_keyword ?? ' ' }}">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Storage array</th>
                                        <th>Number of volumes</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>Storageclasses</th>
                                        <th data-hide="all">Model</th>
                                        <th data-hide="all">Purity version</th>
                                        <th data-hide="all">Protocols</th>
                                        <th data-hide="all">IP Address</th>
                                        <th data-hide="all">Labels</th>
                                        <th data-hide="all">Notices</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoArrays)
                                        @foreach($psoArrays as $pso_array)
                                            <tr>
                                                <td>
                                                    @isset($pso_array['message'])
                                                        <img src="/images/warning.svg" style="height: 13px; vertical-align: text-top;">
                                                    @endisset
                                                    {{ $pso_array['name'] ?? '' }}
                                                </td>
                                                <td>{{ $pso_array['volumeCount'] ?? '' }}</td>
                                                <td>{{ $pso_array['sizeFormatted'] ?? '' }}</td>
                                                <td>{{ $pso_array['usedFormatted'] ?? '' }}</td>
                                                <td>@isset($pso_array['storageClasses']){{ implode(', ', $pso_array['storageClasses']) }}@endisset</td>
                                                <td>{{ $pso_array['model'] ?? 'Unknown' }}</td>
                                                <td>{{ $pso_array['version'] ?? 'Unknown' }}</td>
                                                <td>@isset($pso_array['protocols']){{ implode(', ', $pso_array['protocols']) }}@endisset </td>
                                                <td><a href="https://{{ $pso_array['mgmtEndPoint'] ?? '#' }}" target="_blank">{{ $pso_array['mgmtEndPoint'] ?? '' }} </a></td>
                                                <td>@isset($pso_array['labels']){{ implode(', ', $pso_array['labels']) }}@endisset </td>
                                                <td>{{ $pso_array['message'] ?? '' }}</td>
                                                @if($pso_array['offline'] !== null)
                                                    <td><span class="label label-danger">Offline</span></td>
                                                @else
                                                    <td><span class="label label-success">Online</span></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(count($psoArrays) == 0)
                                            <tr>
                                                <td><i>No arrays found</i></td>
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

            var element = document.getElementById('tablefilter');
            var event = new Event('keyup');
            element.dispatchEvent(event);
        });

    </script>
@endsection