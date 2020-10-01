@extends('layouts.portal')

@section('css')
@endsection

@section('content')
    @isset($ansible_yaml)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
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

@endsection
