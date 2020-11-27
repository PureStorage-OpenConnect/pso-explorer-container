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
                            <span>PSO installation helper</span>
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
                                                To get started, you can choose to download a clean values.yaml
                                                file directly from our GitHub or you can upload your own file.
                                                To be able to download the values.yaml file from GitHub, the
                                                {{ config('app.name', 'PSO eXplorer') }} application requires direct
                                                (https) access to the public GitHub repo.
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-6 left">
                                            <button onclick="location.href='/settings/config-builder/install-source?mode=github';" class="btn btn-primary btn-block">Download values.yaml from <b>GitHub.com</b></button>
                                        </td>

                                        <td class="col-xs-6 left">
                                            <button onclick="location.href='/settings/config-builder/install-source?mode=upload';" class="btn btn-primary btn-block"><b>Upload</b> your own values.yaml file</button>
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

@endsection
