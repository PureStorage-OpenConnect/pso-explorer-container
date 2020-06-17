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
                            <span>Deployments with managed persistent volume claims</span>
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
                                        <th>StatefulSet</th>
                                        <th>Number of volumes</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>StorageClasses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($pso_deployments)
                                        @foreach($pso_deployments as $item)
                                            <tr>
                                                <td>{{ $item['namespace'] ?? '<unknown>' }}</td>
                                                <td>{{ $item['name'] ?? '<unknown>' }}</td>
                                                <td>{{ $item['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $item['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $item['usedFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $item['storageClasses'] ?? '<unknown>' }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($pso_deployments) == 0)
                                            <tr>
                                                <td><i>No deployments found using persistent volume claims</i></td>
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
