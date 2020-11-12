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
                            <span>Nodes used in this cluster ({{ count($nodes ?? []) }})</span>
                        </div>
                        <div class="panel-body list-container">
                            <div class="row with-padding">
                                <input type="text" class="form-control form-control-sm margin-left" id="tablefilter" placeholder="Search in table">
                            </div>
                            <div class="row with-padding">
                                <table class="footable table table-stripped toggle-arrow-tiny margin-left" data-filter=#tablefilter>
                                    <thead>
                                    <tr>
                                        <th data-toggle="true">Node name</th>
                                        <th>Version</th>
                                        <th>IP address</th>
                                        <th>OS image</th>
                                        <th>Container runtime</th>
                                        <th>Contition(s)</th>
                                        <th data-hide="all">Creation time</th>
                                        <th data-hide="all">Roles</th>
                                        <th data-hide="all">Hostname</th>
                                        <th data-hide="all">OS</th>
                                        <th data-hide="all">Architecture</th>
                                        <th data-hide="all">Kernel</th>
                                        <th data-hide="all">podCIDR</th>
                                        <th data-hide="all">podCIDRs</th>
                                        <th data-hide="all">Labels</th>
                                        <th data-hide="all">Taints</th>
                                        <th data-hide="all">Messages</th>
                                        <th data-hide="all">Array connectivity</th>
                                        <th>Schedulable</th>
                                        <th>Array connectivity</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($nodes)
                                        @foreach($nodes as $node)
                                            <tr>
                                                <td>{{ $node['name'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['kubeletVersion'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['internalIP'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['osImage'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['containerRuntimeVersion'] ?? 'Unknown' }}</td>
                                                <td>
                                                    @foreach(($node['conditions'] ?? []) as $item)
                                                        @if($item == "Ready")
                                                            <span class="label label-success">{{ $item }}</span><br>
                                                        @else
                                                            <span class="label label-warning">{{ $item }}</span><br>
                                                        @endif
                                                    @endforeach
                                                    @if(count($node['conditions'] ?? []) == 0)
                                                        <span class="label label-warning">Not Ready</span><br>
                                                    @endif
                                                </td>

                                                <td>{{ $node['creationTimestamp'] ?? 'Unknown' }}</td>
                                                <td>
                                                    @foreach(($node['labels'] ?? []) as $item)
                                                        @if(strpos($item, 'node-role.kubernetes.io/') !== false)
                                                            {{ substr($item, strlen('node-role.kubernetes.io/'), -1) }}<br>
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>{{ $node['hostname'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['operatingSystem'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['architecture'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['kernelVersion'] ?? 'Unknown' }}</td>
                                                <td>{{ $node['podCIDR'] ?? 'Unknown' }}</td>
                                                <td>
                                                    @isset($node['podCIDRs'])
                                                        @foreach($node['podCIDRs'] as $item)
                                                            {{ $item }}<br>
                                                        @endforeach
                                                    @endisset
                                                </td>
                                                <td>@isset($node['labels']){{ implode(', ', $node['labels']) }}@endisset </td>
                                                <td>@isset($node['taints']){{ implode(', ', $node['taints']) }}@endisset </td>
                                                <td>
                                                    @foreach(($node['conditionMessages'] ?? []) as $item)
                                                        {{ $item }}<br>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @isset($node['pingStatus'])
                                                        @foreach($node['pingStatus'] as $key => $value)
                                                            @if($value)
                                                                <span class="label label-success">{{ $key }}</span>
                                                            @else
                                                                <span class="label label-warning">{{ $key }}</span>
                                                            @endif
                                                        @endforeach
                                                    @endisset
                                                </td>

                                                @if($node['unschedulable'] == 1)
                                                    <td><span class="label label-warning">Unschedulable</span></td>
                                                @else
                                                    <td><span class="label label-success">Ready</span></td>
                                                @endif

                                                <td>
                                                    @if($node['pingErrors'])
                                                        <span class="label label-warning">Ping errors</span>
                                                    @elseif(count($node['pingStatus'] ?? []) > 0)
                                                        <span class="label label-success">Healthy</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if(count($nodes) == 0)
                                            <tr>
                                                <td><i>No nodes found</i></td>
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
                                                <td> </td>
                                                <td> </td>
                                            </tr>
                                        @endif
                                    @endisset
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="20">
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

        });

    </script>
@endsection
