@extends('layouts.portal')

@section('css')
@endsection

@section('content')
    @isset($portalInfo)
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
                                    <thead>
                                    <tr class="ps-table-heading"><!---->
                                        <th class="col-xs-4 left" title="Parameter">
                                            <span class="ps-table-header-text" title="">Parameter</span>
                                        </th>
                                        <th class="col-xs-8 left" title="Value">
                                            <span class="ps-table-header-text" title="">Value</span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
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
                                    <tr>
                                        <td class="col-xs-4 left"><span>PSO container versions</span></td>
                                        <td class="col-xs-8 left">
                                            <span>
                                                @foreach($settings['images'] ?? [] as $item)
                                                    <span>{{ $item }}</span><br>
                                                @endforeach
                                            </span>
                                        </td>
                                    </tr>

                                    @if(isset($settings['dbvols']))
                                    <tr>
                                        <td class="col-xs-4 left"><span>CockroachDB</span></td>
                                        <td class="col-xs-8 left">
                                            <table class="full-width table pure-table ps-table td-top">
                                                <tr>
                                                    <td>
                                                        <b>Healthy volumes</b><br>
                                                        @foreach($settings['dbvols'] ?? [] as $item)
                                                            @if($item['unhealthy'] == null)
                                                                <span>
                                                                    @if ($item['pureArrayType'] == 'FA')
                                                                        <a href="https://{{ $item['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $item['pureName'] }}" target="_blank" data-toggle="tooltip" data-placement="top" title="Array: {{ $item['pureArrayName'] }}, Size: {{ $item['pureSizeFormatted'] }}, Used: {{ $item['pureUsedFormatted'] }}">
                                                                            {{ $item['pureName'] }}
                                                                        </a>
                                                                    @else
                                                                        <a href="https://{{ $item['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $item['pureName'] }}" target="_blank" data-toggle="tooltip" data-placement="top" title="Array: {{ $item['pureArrayName'] }}, Size: {{ $item['pureSizeFormatted'] }}, Used: {{ $item['pureUsedFormatted'] }}">
                                                                            {{ $item['pureName'] }}
                                                                        </a>
                                                                    @endif
                                                                </span>
                                                                <br>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        <b>Stale volumes</b><br>
                                                    @foreach($settings['dbvols'] ?? [] as $item)
                                                            @if($item['unhealthy'])
                                                                @if($item['pureName'] !== null)
                                                                    <span>
                                                                        <img src="/images/warning.svg" style="height: 13px; vertical-align: text-top;" data-toggle="tooltip" data-placement="top" title="This volume is parked by PSO since the replica was marked unhealthy.">

                                                                        @if ($item['pureArrayType'] == 'FA')
                                                                            <a href="https://{{ $item['pureArrayMgmtEndPoint'] }}/storage/volumes/volume/{{ $item['pureName'] }}" target="_blank" data-toggle="tooltip" data-placement="top" title="Array: {{ $item['pureArrayName'] }}, Size: {{ $item['pureSizeFormatted'] }}, Used: {{ $item['pureUsedFormatted'] }}">
                                                                                {{ $item['pureName'] }}
                                                                            </a>
                                                                        @else
                                                                            <a href="https://{{ $item['pureArrayMgmtEndPoint'] }}/storage/filesystems/{{ $item['pureName'] }}" target="_blank" data-toggle="tooltip" data-placement="top" title="Array: {{ $item['pureArrayName'] }}, Size: {{ $item['pureSizeFormatted'] }}, Used: {{ $item['pureUsedFormatted'] }}">
                                                                                {{ $item['pureName'] }}
                                                                            </a>
                                                                        @endif
                                                                </span><br>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endif

                                    <tr>
                                        <td class="col-xs-4 left"><span>PSO Arguments</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{!! implode('<br>', ($settings['psoArgs'] ?? []))  !!} </span>
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

        {{-- Block storage settings --}}
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Default block storage settings</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <table class="table pure-table ps-table">
                                    <thead>
                                    <tr class="ps-table-heading"><!---->
                                        <th class="col-xs-4 left" title="Parameter">
                                            <span class="ps-table-header-text" title="">
                                                Parameter
                                            </span>
                                        </th>
                                        <th class="col-xs-8 left" title="Value">
                                            <span class="ps-table-header-text" title="">
                                                Value
                                            </span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Block storage SAN protocol</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['sanType'] ?? '' }}</span><br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Default block storage File System type</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['blockFsType'] ?? '' }}</span><br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Default block storage File System options</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['blockFsOpt'] ?? '' }}</span><br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Default block storage mount options</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['blockMntOpt'] ?? '' }}</span><br>
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

        {{-- iSCSI protocol settings --}}
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>iSCSI protocol settings</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <table class="table pure-table ps-table">
                                    <thead>
                                    <tr class="ps-table-heading"><!---->
                                        <th class="col-xs-4 left" title="Parameter">
                                            <span class="ps-table-header-text" title="">
                                                Parameter
                                            </span>
                                        </th>
                                        <th class="col-xs-8 left" title="Value">
                                            <span class="ps-table-header-text" title="">
                                                Value
                                            </span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td class="col-xs-4 left"><span>iSCSI login timeout</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['iscsiLoginTimeout'] ?? '' }}</span><br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>iSCSI allowed CIDRs</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['iscsiAllowedCidrs'] ?? '' }}</span><br>
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

        {{-- File storage settings --}}
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Default file storage settings</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <table class="table pure-table ps-table">
                                    <thead>
                                    <tr class="ps-table-heading"><!---->
                                        <th class="col-xs-4 left" title="Parameter">
                                            <span class="ps-table-header-text" title="">
                                                Parameter
                                            </span>
                                        </th>
                                        <th class="col-xs-8 left" title="Value">
                                            <span class="ps-table-header-text" title="">
                                                Value
                                            </span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Snapshot directory enabled</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['enableFbNfsSnapshot'] ?? '' }}</span><br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="col-xs-4 left"><span>Export rules</span></td>
                                        <td class="col-xs-8 left">
                                            <span>{{ $settings['nfsExportRules'] ?? '' }}</span><br>
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

        {{-- PSO provisioner log --}}
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO provisioner log</span>
                            </div>
                            <div class="panel-body">
                                <div class="col-xs-12">Search through the most recent log lines of the PSO provisioner</div>
                                <div class="col-xs-12">
                                    <form autocomplete="off" class="form-inline">
                                        <input class="col-xs-12" type="text" id="myInput" placeholder="Type to filter log" data-filter=".filter-log-lines">
                                    </form>
                                </div>
                                <div class="col-xs-12">
                                    <pre class="pre-scrollable filter-log-lines">{{ $log ?? 'no logs found' }}</pre>
                                </div>
                                <br><br>&nbsp;
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset
    @isset($ansible_yaml)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="no-left-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>Ansible playbook to remove unhealthy PSO DB volumes</span>
                            </div>
                            <div class="panel-body list-container">
                                <div class="row no-padding no-margin">
                                    You can use the following Ansible Playbook to remove any unhealthy CochraochDB volumes from your Pure Storage FlashArray / FlashBlade systems.<br><br>
                                </div>
                                <div class="row no-padding no-margin">To use the playbook make sure that you:</div>
                                <div class="row no-padding no-margin">- Set the API tokens in the <code>vars:</code> section for each array listed</div>
                                <div class="row no-padding no-margin">- Copy the playbook below and save it as <code>delete-volumes.yaml</code></div>
                                <div class="row no-padding no-margin">- Have ansible version 2.9+ installed (<a href="https://docs.ansible.com/" target="_blank">https://docs.ansible.com/</a>)</div>
                                <div class="row no-padding no-margin">- Have the FlashArray and FlashBlade Ansible collections installed, the following will make sure you have the latest relerase installed:</div>
                                <pre>ansible-galaxy collection install purestorage.flasharray --force
ansible-galaxy collection install purestorage.flashblade --force</pre>

                                <div class="row no-padding no-margin">To run the playbook execute</div>
                                <pre>ansible-playbook delete-volumes.yaml</pre>

                                <div class="row no-padding no-margin">The playbook (save as <code>delete-volumes.yaml</code></div>
                                <pre style="width: 100%;">{!! $ansible_yaml !!}</pre>
                                <br><br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset
@endsection

@section('script')
    <script>
        function myFunction(e) {
            e.preventDefault();
            var thisInput = $(this);
            var thisInputValue = $.trim(thisInput.val().toLowerCase());
            $(thisInput.data('filter')).each(function(j) {
                var toFilter = $(this);
                if (toFilter.find('span').length < 1) {
                    /* Split lines using spans, but include ending new line char */
                    var oldText = toFilter.text();
                    var oldTextSplit = oldText.split('\n');
                    var newText = '<span class="pre-span">' + oldTextSplit.join('\n</span><span class="pre-span">') + '\n</span>';
                    toFilter.html(newText);
                };
                if (thisInputValue) {
                    /* Filter (hide) rows which contain no filter */
                    toFilter.find('span').each(function(i) {
                        var thisRow = $(this);
                        var thisRowText = thisRow.text().toLowerCase();
                        if (thisRowText.indexOf(thisInputValue) < 0) {
                            thisRow.addClass('invisible-row');
                        } else {
                            thisRow.removeClass('invisible-row');
                        };
                    });
                } else {
                    /* Nothing to filter, show all rows */
                    toFilter.find('span').removeClass('invisible-row');
                };
            });
        };
        $('[data-filter]').on('input', myFunction);
    </script>
@endsection
