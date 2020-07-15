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
                            <span>Jobs with managed persistent volume claims ({{ count($pso_jobs ?? []) }})</span>
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
                                        <th data-hide="all">Job status</th>
                                        <th data-hide="all">Volumes</th>
                                        <th data-hide="all">Labels</th>
                                        <th>StorageClasses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($pso_jobs)
                                        @foreach($pso_jobs as $pso_job)
                                            <tr>
                                                <td>{{ $pso_job['namespace'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['name'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['usedFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['creationTimestamp'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_job['status'] ?? '<unknown>' }}</td>
                                                <td>
                                                    @isset($pso_job['pvc_link'])
                                                    @foreach($pso_job['pvc_link'] as $pvc_link)
                                                        {!! $pvc_link !!}<br>
                                                    @endforeach
                                                    @endisset
                                                </td>
                                                <td>{{ implode(', ', $pso_job['labels'] ?? []) }}</td>
                                                <td>{{ implode(', ', $pso_job['storageClasses'] ?? []) }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($pso_jobs) == 0)
                                            <tr>
                                                <td><i>No jobs with PVC's managed by PSO were found</i></td>
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
