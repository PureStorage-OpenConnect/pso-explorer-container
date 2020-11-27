@extends('layouts.portal')

@section('css')

@endsection

@section('content')
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
                                                The installation helper allows you to create a <code>values.yaml</code>
                                                file, that you can use for your PSO deployment.
                                            </div>
                                            <div>&nbsp</div>
                                            <div class="block">
                                                TO DO... some text here...
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-6 left" colspan="2">
                                            <form class="form-group form-group-sm" method="post" action="/settings/config-builder/install-builder" accept-charset="utf-8">
                                                @csrf
                                                <div class="col-sm-12 form-group no-padding">
                                                    <label class="col-sm-4 form-control-static control-label">PSO Edition</label>
                                                    <select class="col-sm-8 form-control form-control-select" disabled>
                                                        <option class="dropdown-toggle btn-select" @if($settings['psoEdition'] == 'PSO6') selected="selected" @endif>CSI edition 6.x</option>
                                                        <option class="dropdown-toggle btn-select" @if($settings['psoEdition'] == 'PSO5') selected="selected" @endif>CSI edition 5.x</option>
                                                        <option class="dropdown-toggle btn-select" @if($settings['psoEdition'] == 'FLEX') selected="selected" @endif>Flex driver edition</option>
                                                    </select>
                                                    <input hidden name="edition" value="{{ $settings['psoEdition'] }}">
                                                </div>
                                                <div class="col-sm-12 form-group no-padding">
                                                    <label class="col-sm-4 form-control-static control-label">PSO Release</label>
                                                    <select class="col-sm-8 form-control form-control-select" id="version" name="version" onchange="getDescription(this)" @if($releases == null) disabled @endif>
                                                        @if($releases !== null)
                                                            @foreach($releases['releases'] as $key => $value)
                                                                <option class="dropdown-toggle btn-select" value="{{ $key }}">{{ $key }} ({{ $value }})</option>
                                                            @endforeach
                                                        @else
                                                            <option class="dropdown-toggle btn-select" selected="selected">Unable to connect to GitHub repo</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-12 form-group no-padding">
                                                    <label class="col-sm-4 form-control-static control-label">Release notes</label>
                                                    <div class="col-sm-8 breaking-word no-padding" id="description"></div>
                                                </div>

                                                <div class="form-group no-margin">
                                                    <button class="btn btn-primary pull-right">Select version</button>
                                                    <a href="/settings/config-builder/install-helper" class="btn pull-right">Restart</a>
                                                </div>
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
            if (document.getElementById("version") !== null) {
                document.getElementById("version").onchange();
            }
        })
    </script>
@endsection