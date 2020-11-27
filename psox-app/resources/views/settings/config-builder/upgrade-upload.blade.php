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
                                            </div>
                                            <div>&nbsp</div>
                                            <div class="block">
                                                To get started, upload your current values.yaml file. You can download
                                                a clean copy at the GitHub repo.
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-6 left" colspan="2">
                                            <form class="form-group form-group-sm" method="post" action="/settings/config-builder/upgrade-final" enctype="multipart/form-data">
                                                @csrf
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
