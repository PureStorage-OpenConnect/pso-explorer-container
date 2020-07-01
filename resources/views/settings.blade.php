@extends('layouts.portal')

@section('css')
@endsection

@section('content')
    @isset($portal_info)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">

                    {{-- Storage Usage --}}
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO settings</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="col-12 with-padding-top border-bottom">
                                    <p style="margin: 0 0 9px;"><strong>PSO version</strong></p>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">Image version</div>
                                    <div class="settings-value"><span>{{ $settings['image'] }}</span></div>
                                </div>
                                <div class="col-12 with-padding-top border-bottom">
                                    <p style="margin: 0 0 9px;"><strong>Block storage settings</strong></p>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">Block storage SAN protocol</div>
                                    <div class="settings-value"><span>{{ $settings['san_type'] }}</span></div>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">Default block storage File System type</div>
                                    <div class="settings-value"><span>{{ $settings['block_fs_type'] }}</span></div>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">Default block storage File System options</div>
                                    <div class="settings-value"><span>{{ $settings['block_fs_opt'] }}</span></div>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">Default block storage mount options</div>
                                    <div class="settings-value"><span>{{ $settings['block_mnt_opt'] }}</span></div>
                                </div>
                                <div class="col-12 with-padding-top border-bottom">
                                    <p style="margin: 0 0 9px;"><strong>iSCSI settings</strong></p>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">iSCSI login timeout</div>
                                    <div class="settings-value"><span>{{ $settings['iscsi_login_timeout'] }}</span></div>
                                </div>
                                <div class="settings-list">
                                    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">iSCSI allowed CIDRs</div>
                                    @if($settings['iscsi_allowed_cidrs'] !== '')
                                        <div class="settings-value"><span>{{ $settings['iscsi_allowed_cidrs'] }}</span></div>
                                    @else
                                        <div><span><i>Not set</i></i></span></div>
                                    @endif
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
    @isset($portal_info)
        <script src="{{ asset('js/plugins/chartJs/Chart.min.js') }}"></script>

        <script>
            $(function () {
                var doughnutData = {
                    labels: ["Used (Gi)", "Provisioned (Gi)"],
                    datasets: [{
                        data: [{{ $portal_info['total_used_raw']/1024/1024/1024 }}, {{ $portal_info['total_size_raw']/1024/1024/1024 }}],
                        backgroundColor: ["#52c8fd", "#f4f2f3"]
                    }]
                };

                var doughnutOptions = {
                    responsive: true
                };

                var ctx4 = document.getElementById("doughnutChart").getContext("2d");
                new Chart(ctx4, {type: 'doughnut', data: doughnutData, options: doughnutOptions});
            });
        </script>
    @endisset
@endsection
