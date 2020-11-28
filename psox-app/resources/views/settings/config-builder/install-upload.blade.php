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
                                            The install helper allows you to create a <code>values.yaml</code>
                                            file, which you can then use to deploy Pure Service Orchestrator.
                                        </div><div>&nbsp</div><div class="breaking-word">
                                            To get started, manually download a fresh copy of the <code>values.yaml</code>
                                            for the release you want to use from our GitHub repo.
                                        </div><div>&nbsp</div><div class="breaking-word">
                                            Use the button below to upload the <code>values.yaml</code> file. In the next
                                            step we'll allow you to customize the settings for the deployment.
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-xs-6 left" colspan="2">
                                        <form class="form-group form-group-sm" method="post" action="/settings/config-builder/install-builder" enctype="multipart/form-data">
                                            @csrf
                                            <input hidden class="btn btn-secondary" name="edition" value="PSO6">
                                            <input hidden class="btn btn-secondary" name="version" value="upload">

                                            <input type="file" class="btn btn-secondary" name="values_file">
                                            <div class="form-group">
                                                <button class="btn btn-primary pull-right">Next</button>
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

@endsection
