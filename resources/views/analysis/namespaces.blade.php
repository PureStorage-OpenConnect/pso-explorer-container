@extends('layouts.portal')

@section('css')
    <link href="{{ asset('css/plugins/footable/footable.core.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">

                {{-- Storage Usage --}}
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>Namespaces with managed persistent volume claims ({{ count($psoNamespaces ?? []) }})</span>
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
                                        <th>Number of volumes</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>Storageclasses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($psoNamespaces)
                                        @foreach($psoNamespaces as $pso_namespace)
                                            <tr>
                                                <td>{{ $pso_namespace['namespace']  ?? '<unknown>' }}</td>
                                                <td>{{ $pso_namespace['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_namespace['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_namespace['usedFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_namespace['storageClasses'] ?? '<unknown>' }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($psoNamespaces) == 0)
                                            <tr>
                                                <td><i>No persistent volume claims found in any namespace</i></td>
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
