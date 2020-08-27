@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    {{-- @isset($portalInfo) --}}
        {{-- Version information --}}
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO configuration</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <form action="/settings/config" method="post" accept-charset="utf-8">
                                    @csrf
                                    <input name="phase" value="{{ $phase }}" hidden>
                                    @isset($settings['psoEdition'])
                                        <input name="edition" value="{{ $settings['psoEdition'] }}" hidden>
                                    @endisset
                                    <input name="phase" value="{{ $phase }}" hidden>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">PSO Release</label>
                                        <div class="col-sm-9">
                                            <select class="form-control form-control-select" id="edition" name="edition" @if($phase !== 1) disabled @endif>
                                                <option class="dropdown-toggle btn-select" value="FLEX" @if($settings['psoEdition'] == 'FLEX') selected="selected" @endif>Flex driver edition</option>
                                                <option class="dropdown-toggle btn-select" value="PSO5" @if($settings['psoEdition'] == 'PSO5') selected="selected" @endif>CSI edition 5.x</option>
                                                <option class="dropdown-toggle btn-select" value="PSO6" @if($settings['psoEdition'] == 'PSO6') selected="selected" @endif>CSI edition 6.x</option>
                                            </select>
                                        </div>
                                    </div>

                                    @if($phase !== 1)
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">PSO Release</label>
                                        <div class="col-sm-9">
                                            <select class="form-control form-control-select" id="release" name="release" onchange="getDescription(this)">
                                                @foreach($releases['releases'] as $key => $value)
                                                    <option class="dropdown-toggle btn-select" value="{{ $key }}" @if($key == $settings['provisionerTag']) selected="selected" @endif>{{ $key }} ({{ $value }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Release notes</label>
                                        <div class="col-sm-9">
                                            <div id="description" class="list-section"></div>
                                        </div>
                                    </div>
                                    @endif

                                    <button class="btn btn-primary pull-right">Next</button>
                                    @if(($settings['prefix'] ?? null) == null)
                                        <a href="{{ Route::current()->getName() }}" class="btn pull-right">Restart</a>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <p>{{ $settings['provisionerTag'] ?? '' }}</p>
    <p>{{ $settings['isCsiDriver'] ?? '' }}</p>
    <p>{{ $settings['repoUri'] ?? '' }}</p>
    <p>Let's start here!</p>
    {{-- @endisset --}}
@endsection

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/markdown-it/11.0.0/markdown-it.min.js"></script>

    <script>
        function getDescription(source)
        {
            var md = window.markdownit();
            var path = source.options[source.selectedIndex].value;
            var desc = @json($releases);

            document.getElementById("description").innerHTML = md.render(desc['descriptions'][path]);
        };
    </script>

    <script>
        $(function(){
            document.getElementById("release").onchange();
        });
    </script>
@endsection
