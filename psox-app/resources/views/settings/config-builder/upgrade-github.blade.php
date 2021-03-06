@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    {{-- Version information --}}
    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>PSO information</span>
                        </div>
                        <div class="panel-body table-no-filter">
                            <table class="table pure-table ps-table">
                                <tbody>
                                    <tr>
                                        <td class="col-xs-4 left"><span>PSO version</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['provisionerTag'] ?? '' }} @if($settings['isCsiDriver'])(CSI driver) @else()(Flex driver)@endif</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>PSO ClusterID</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['prefix'] ?? '' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Kubernetes namespace</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['namespace'] ?? '' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 tab-container">
            <div class="with-padding">
                <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span>PSO upgrade helper</span>
                        </div>
                        <div class="panel-body table-no-filter">
                            <table class="table pure-table ps-table">
                                <tbody>
                                    <tr>
                                        <td class="col-xs-12 breaking" colspan="2">
                                            <div>
                                                The upgrade helper allows you to create a <code>values.yaml</code>
                                                file, which you can then use to upgrade your deployment.
                                            </div><div>&nbsp</div><div class="breaking-word">
                                                The section below lists all available releases from our GitHub repo.
                                                Once you select a release, the release notes are displayed.
                                            </div><div>&nbsp</div><div class="breaking-word">
                                                Once you have selecting the new version to use and click  the <i>Create yaml.file</i>
                                                button, we'll download a clean <code>values.yaml</code> file from the
                                                GitHub repo, add the settings from this cluster and provide this as a
                                                new <code>values.yaml</code> file to you. You can then use that file to upgrade
                                                your cluster.
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-6 left" colspan="2">
                                            <form class="form-group form-group-sm" method="post" action="/settings/config-builder/upgrade-final" accept-charset="utf-8">
                                                @csrf
                                                <div class="col-sm-12 no-padding breaking-word">
                                                    <b>Select the PSO release you would like to use to base your <code>values.yaml</code> file on.</b>
                                                </div>
                                                <div class="col-sm-12 no-padding">
                                                    <select class="form-control form-control-select" id="release" name="version" onchange="getDescription(this)" @if($releases == null) disabled @endif>
                                                        @if($releases !== null)
                                                            @foreach($releases['releases'] as $key => $value)
                                                                <option class="dropdown-toggle btn-select" value="{{ $key }}">{{ $key }} ({{ $value }})</option>
                                                            @endforeach
                                                        @else
                                                            <option class="dropdown-toggle btn-select" selected="selected">Unable to connect to GitHub repo</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-12 no-padding">
                                                    <div id="description" class="list-section breaking-word"></div>
                                                </div>
                                                <button class="btn btn-primary pull-right">Create yaml file</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="/js/plugins/markdown/markdown-it.min.js"></script>

    <script>
        function getDescription(source)
        {
            var md = window.markdownit();
            var path = source.options[source.selectedIndex].value;
            var desc = @json($releases ?? null);

            document.getElementById("description").innerHTML = md.render(desc['descriptions'][path]);
        }
    </script>

    <script>
        $(function(){
            if (document.getElementById("release") !== null) {
                document.getElementById("release").onchange();
            }
        })
    </script>
@endsection
