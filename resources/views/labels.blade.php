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
                            <span>Managed persistent volume claims per label</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Label</th>
                                        <th>Volumes</th>
                                        <th>Provisioned size</th>
                                        <th>Used capacity</th>
                                        <th>StorageClasses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($pso_labels)
                                        @foreach($pso_labels as $pso_label)
                                            <tr>
                                                <td>@if($pso_label['label'] == '') - no label - @else{{ $pso_label['label'] ?? '<unknown>' }}@endif</td>
                                                <td>{{ $pso_label['volumeCount'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_label['sizeFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_label['usedFormatted'] ?? '<unknown>' }}</td>
                                                <td>{{ $pso_label['storageClasses'] ?? '<unknown>' }}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($pso_labels) == 0)
                                            <tr>
                                                <td><i>No persistent volume claims found with labels</i></td>
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
                                        <td colspan="5">
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
