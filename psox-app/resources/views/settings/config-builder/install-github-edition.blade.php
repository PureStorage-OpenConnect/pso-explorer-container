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
                            <span>PSO install helper</span>
                        </div>
                        <div class="panel-body table-no-filter">
                            <table class="table pure-table ps-table">
                                <tbody>
                                    <tr>
                                        <td class="col-xs-12 breaking" colspan="2">
                                            <div>
                                                The installation helper allows you to create a <code>values.yaml</code>
                                                file, that you can use for your PSO deployment.
                                            </div><div>&nbsp</div><div class="breaking-word">
                                                The section below lists all available editions and releases from our
                                                GitHub repo's. Once you select a release, the release notes are displayed.
                                            </div><div>&nbsp</div><div class="breaking-word">
                                                Once you have selecting the new version to use, we'll download a clean
                                                <code>values.yaml</code> file from the GitHub repo. In the next
                                                step we'll allow you to customize the settings for the deployment.
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-6 left" colspan="2">
                                            <form class="form-group form-group-sm" method="post" action="/settings/config-builder/install-github-version" accept-charset="utf-8">
                                                @csrf
                                                <div class="col-sm-12 form-group no-padding">
                                                    <label class="col-sm-4 form-control-static control-label">PSO Edition</label>
                                                    <select class="col-sm-8 form-control form-control-select" id="edition" name="edition">
                                                        <option class="dropdown-toggle btn-select" value="PSO6" selected="selected">CSI edition 6.x</option>
                                                        <option class="dropdown-toggle btn-select" value="PSO5">CSI edition 5.x</option>
                                                        <option class="dropdown-toggle btn-select" value="FLEX">Flex driver edition</option>
                                                    </select>
                                                </div>

                                                <div class="form-group no-margin">
                                                    <button class="btn btn-primary pull-right">Select edition</button>
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
            if (document.getElementById("release") !== null) {
                document.getElementById("release").onchange();
            }
        })
    </script>
@endsection
