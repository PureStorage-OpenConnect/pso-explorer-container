@extends('layouts.portal')

@section('css')
    <link href="{{ asset('css/plugins/footable/footable.core.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">
                    <h5>All volumes</h5>

                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <input type="text" class="form-control form-control-sm m-b-xs" id="dnsfilter" placeholder="Search in table">
                    <table class="footable table table-stripped toggle-arrow-tiny" data-filter=#dnsfilter>
                        <thead>
                        <tr>
                            <th data-toggle="true">Storageclass</th>
                            <th>Number of volumes</th>
                            <th>Provisioned size</th>
                            <th>Used capacity</th>
                        </tr>
                        </thead>
                        <tbody>
                        @isset($pso_storageclasses)
                            @foreach($pso_storageclasses as $pso_storageclass)
                                <tr>
                                    <td>{{ $pso_storageclass['name'] ?? '<unknown>' }}</td>
                                    <td>{{ $pso_storageclass['volumeCount'] ?? '<unknown>' }}</td>
                                    <td>{{ $pso_storageclass['sizeFormatted'] ?? '<unknown>' }}</td>
                                    <td>{{ $pso_storageclass['usedFormatted'] ?? '<unknown>' }}</td>
                                </tr>
                            @endforeach
                            @if(count($pso_storageclasses) == 0)
                                <tr>
                                    <td><i>No StorageClasses found</i></td>
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
