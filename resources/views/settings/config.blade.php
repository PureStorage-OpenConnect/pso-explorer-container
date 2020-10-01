@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($psoEdition)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO {{ $isUpgrade ? 'upgrade' : 'installation' }} helper</span>
                            </div>
                            <div class="panel-body">
                                <div class="with-padding margin-left">
                                    <form class="form-group form-group-sm" action="/settings/config" method="post" accept-charset="utf-8">

                                        @csrf
                                        <input type="hidden" name="_phase" value="{{ $phase }}">
                                        <input type="hidden" name="_isUpgrade" value="{{ $isUpgrade }}">
                                        @isset($psoEdition)
                                            <input type="hidden" name="_edition" value="{{ $psoEdition }}">
                                        @endisset
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">PSO Edition</label>
                                            <div class="col-sm-9 no-padding">
                                                <select class="form-control form-control-select" id="edition" name="_edition" @if($phase !== 1) disabled @endif>
                                                    <option class="dropdown-toggle btn-select" value="PSO6" @if(($psoEdition ?? '') == 'PSO6') selected="selected" @endif>CSI edition 6.x</option>
                                                    <option class="dropdown-toggle btn-select" value="PSO5" @if(($psoEdition ?? '') == 'PSO5') selected="selected" @endif>CSI edition 5.x</option>
                                                    <option class="dropdown-toggle btn-select" value="FLEX" @if(($psoEdition ?? '') == 'FLEX') selected="selected" @endif>Flex driver edition</option>
                                                </select>
                                            </div>
                                        </div>

                                        @if($phase !== 1)
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">PSO Release</label>
                                            <div class="col-sm-9 no-padding">
                                                <select class="form-control form-control-select" id="release" name="_release" onchange="getDescription(this)" @if($releases == null) disabled @endif>
                                                    @if($releases !== null)
                                                        @foreach($releases['releases'] as $key => $value)
                                                            <option class="dropdown-toggle btn-select" value="{{ $key }}" @if($key == $psoRelease) selected="selected" @endif>{{ $key }} ({{ $value }})</option>
                                                        @endforeach
                                                    @else
                                                        <option class="dropdown-toggle btn-select" selected="selected">Unable to connect to GitHub repo</option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Release notes</label>
                                            <div class="col-sm-9 no-padding">
                                                <div id="description" class="list-section"></div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="form-group">
                                            <button class="btn btn-primary pull-right">Next</button>
                                            {{-- TODO: Need to do better for settings/config, detect current url --}}
                                            <a href="/settings/config" class="btn pull-right">Restart</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset
@endsection

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/markdown-it/11.0.0/markdown-it.min.js"></script>

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
