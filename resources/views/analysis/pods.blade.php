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
                            <span>Pods with managed persistent volume claims ({{ count($pso_pods ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Namespace</th>
                                        <th>Name</th>
                                        <th>Volumes</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th data-hide="all">Creation time</th>
                                        <th data-hide="all">Containers</th>
                                        <th data-hide="all">Pod status</th>
                                        <th data-hide="all">Volumes</th>
                                        <th data-hide="all">Labels</th>
                                        <th>StorageClasses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($pso_pods)
                                        @foreach($pso_pods as $pso_pod)
                                            <tr>
                                                <td>{{ $pso_pod['namespace'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_pod['name'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_pod['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_pod['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_pod['usedFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_pod['creationTimestamp'] ?? '<unknown>' }}</td>
                                                <td>
                                                    @isset($pso_pod['containers'])
                                                        @foreach($pso_pod['containers'] as $item)
                                                            {{ $item }}<br>
                                                        @endforeach
                                                    @endisset
                                                </td>
                                                <td>{{ $pso_pod['status'] ?? '<unknown>' }}</td>
                                                <td>
                                                    @isset($pso_pod['pvc_link'])
                                                    @foreach($pso_pod['pvc_link'] as $pvc_link)
                                                        {!! $pvc_link !!}<br>
                                                    @endforeach
                                                    @endisset
                                                </td>
                                                <td>{{ implode(', ', $pso_pod['labels'] ?? []) }}</td>
                                                <td>{{ implode(', ', $pso_pod['storageClasses'] ?? []) }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($pso_pods) == 0)
                                            <tr>
                                                <td><i>No pods with PVC's managed by PSO were found</i></td>
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
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <ul class="pagination float-right"></ul>
                                        </td>
                                    </tr>
                                    </tfoot>                                </table>
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
