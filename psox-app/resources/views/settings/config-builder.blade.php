@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($psoValues)
        <form action="/settings/config" method="post" accept-charset="utf-8">
            @csrf
            <input type="hidden" name="_phase" value="3">
            <input type="hidden" name="_isUpgrade" value="{{ $isUpgrade }}">
            <input type="hidden" name="_edition" value="{{ $psoEdition }}">
            <input type="hidden" name="_release" value="{{ $psoRelease }}">
            <div class="row">
                <div class="col-xs-12 tab-container">
                    <div class="with-padding">
                        <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <span>PSO array configuration</span>
                                </div>
                                <p id="addnew">
                                    <a class="btn btn-default" href="javascript:add_array('FlashArray')">Add new FlashArray</a>
                                    <a class="btn btn-default" href="javascript:add_array('FlashBlade')">Add new FlashBlade</a>
                                </p>
                                <div class="panel-body table-no-filter">
                                    <div class="row with-padding margin-left">
                                        <div id="array-list" class="form-horizontal" style="width: 100%">
                                        </div>
                                    </div>
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
                                    <span>PSO configuration settings</span>
                                </div>

                                <div class="panel-body table-no-filter">
                                    <div class="row with-padding margin-left">
                                        <div class="form-horizontal" style="width: 100%">
                                            @foreach($psoValues as $l1Key => $l1Value)
                                                @if(is_array($l1Value) and ($l1Key !== 'arrays'))
                                                    <div class="form-group no-margin">
                                                        <h5 style="background: #e6e6e6;">{{ ucfirst($l1Key) }}</h5>
                                                    </div>
                                                    @foreach($l1Value as $l2Key => $l2Value)
                                                        @if(is_array($l2Value))
                                                            @foreach($l2Value as $l3Key => $l3Value)
                                                                @if(is_array($l3Value))
                                                                    @foreach($l3Value as $l4Key => $l4Value)
                                                                        <div class="form-group no-margin">
                                                                            <label class="col-sm-4 form-control-label with-padding margin-bottom" for="prefix">{{ ucfirst($l2Key) }}.{{ ucfirst($l3Key) }}.{{ ucfirst($l4Key) }}</label>
                                                                            <div class="col-sm-8">
                                                                                @if(gettype($l4Value) == 'string')
                                                                                    <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:{{ $l2Key }}:{{ $l3Key }}:{{ $l4Key }}:string" type="text" value="{{ $l4Value }}">
                                                                                @elseif(gettype($l4Value) == 'boolean')
                                                                                @elseif(gettype($l4Value) == 'integer')
                                                                                    <input class="form-control" name="{{ $l1Key }}:{{ $l2Key }}:{{ $l3Key }}:{{ $l4Key }}:boolean" type="checkbox" @if($l4Value) checked @endif style="width: 20px;">
                                                                                <!-- TODO -->
                                                                                    <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:{{ $l2Key }}:{{ $l3Key }}:{{ $l4Key }}:integer" type="number" value="{{ $l4Value }}">
                                                                                @else
                                                                                    <label>ERROR - Type unknown: {{ gettype($l4Value) }}</label>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="form-group no-margin">
                                                                        <label class="col-sm-4 form-control-label with-padding margin-bottom" for="prefix">{{ ucfirst($l2Key) }}:{{ ucfirst($l3Key) }}</label>
                                                                        <div class="col-sm-8">
                                                                            @if(gettype($l3Value) == 'string')
                                                                                <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:{{ $l2Key }}:{{ $l3Key }}:string" type="text" value="{{ $l3Value }}">
                                                                            @elseif(gettype($l3Value) == 'boolean')
                                                                                <input class="form-control" name="{{ $l1Key }}+{{ $l2Key }}+{{ $l3Key }}:boolean" type="checkbox" @if($l3Value) checked @endif  style="width: 20px;">
                                                                            @elseif(gettype($l3Value) == 'integer')
                                                                            <!-- TODO -->
                                                                                <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}+{{ $l2Key }}+{{ $l3Key }}:integer" type="number" value="{{ $l3Value }}">
                                                                            @else
                                                                                <label>ERROR - Type unknown: {{ gettype($l3Value) }}</label>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <div class="form-group no-margin">
                                                                <label class="col-sm-4 form-control-label with-padding margin-bottom" for="prefix">{{ ucfirst($l2Key) }}</label>
                                                                <div class="col-sm-8">
                                                                    @if(gettype($l2Value) == 'string')
                                                                        <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:{{ $l2Key }}:string" type="text" value="{{ $l2Value }}">
                                                                    @elseif(gettype($l2Value) == 'boolean')
                                                                        <input class="form-control" name="{{ $l1Key }}:{{ $l2Key }}:boolean" type="checkbox" @if($l2Value) checked @endif  style="width: 20px;">
                                                                    @elseif(gettype($l2Value) == 'integer')
                                                                        <!-- TODO -->
                                                                        <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:{{ $l2Key }}:integer" type="text" value="{{ $l2Value }}">
                                                                    @else
                                                                        <label>ERROR - Type unknown: {{ gettype($l2Value) }}</label>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @elseif ($l1Key !== 'arrays')
                                                    <div class="form-group no-margin">
                                                        <label class="col-sm-4 form-control-label with-padding margin-bottom" for="prefix">{{ ucfirst($l1Key) }}</label>

                                                        {{-- TODO: Add integer and boolean options --}}
                                                        <div class="col-sm-8">
                                                            <input class="form-control ng-untouched ng-pristine ng-valid" name="{{ $l1Key }}:string" type="text" value="{{ $l1Value }}">
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                            <div class="form-group no-margin">
                                                <button class="btn btn-primary pull-right">Next</button>
                                                <a href="{{ Route::current()->getName() }}" class="btn pull-right">Restart</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div style="display:none">

            <!-- Template. This whole data will be added directly to working form above -->
            <div id="divheader" style="display:none">
                <div class="form-group">
                    <div class="pull-right btn btn-default"><a href="javascript:remove_array(myArrayId)">Remove this array</a></div>
                    <h5 style="background: #e6e6e6;">Array myArrayId: arrayType</h5>
                </div>
            </div>

            <div id="new_array" style="display:none">
                <div class="form-group no-margin">
                    <label class="col-sm-4 form-control-label with-padding margin-bottom" for="MgmtEndPoint">MgmtEndPoint</label>
                    <div class="col-sm-8">
                        <input class="form-control ng-untouched ng-pristine ng-valid" name="arrays[arrayType][myArrayId][MgmtEndPoint]" type="text" value="value-mgmt">
                    </div>
                </div>
                <div class="form-group no-margin">
                    <label class="col-sm-4 form-control-label with-padding margin-bottom" for="APIToken">APIToken</label>
                    <div class="col-sm-8">
                        <input class="form-control ng-untouched ng-pristine ng-valid" name="arrays[arrayType][myArrayId][APIToken]" type="text" value="value-api">
                    </div>
                </div>
                <div class="form-group no-margin" id="nfs-myArrayId">
                    <label class="col-sm-4 form-control-label with-padding margin-bottom" for="NFSEndPoint">NFSEndPoint</label>
                    <div class="col-sm-8">
                        <input class="form-control ng-untouched ng-pristine ng-valid" name="arrays[arrayType][myArrayId][NFSEndPoint]" type="text" value="value-nfs">
                    </div>
                </div>
                <div class="form-group no-margin">
                    <label class="col-sm-4 form-control-label with-padding margin-bottom" for="Labels">Labels</label>
                    <div class="col-sm-8">
                        <input class="form-control ng-untouched ng-pristine ng-valid" name="arrays[arrayType][myArrayId][Labels]" type="text" value="value-label">
                    </div>
                </div>
            </div>

        </div>
        <br><br>
    @endisset
@endsection

@section('script')

    <script type="text/javascript">
        var arrayId = 0;
        function add_array(arrayType, mgmt = '', api = '', label = '', nfs = '')
        {
            // Get template header
            var divHeader = document.createElement('div');
            divHeader.innerHTML = document.getElementById('divheader').innerHTML;

            // Get template fields and add header
            var newDiv = document.createElement('div');
            newDiv.id = arrayId.toString();
            newDiv.classList.add('margin-bottom');
            newDiv.innerHTML = divHeader.innerHTML + document.getElementById('new_array').innerHTML


            newDiv.innerHTML = newDiv.innerHTML.replace(/arrayType/g, arrayType.toString());
            newDiv.innerHTML = newDiv.innerHTML.replace(/myArrayId/g, arrayId.toString());
            newDiv.innerHTML = newDiv.innerHTML.replace(/value-mgmt/g, mgmt);
            newDiv.innerHTML = newDiv.innerHTML.replace(/value-api/g, api);
            newDiv.innerHTML = newDiv.innerHTML.replace(/value-label/g, label);
            newDiv.innerHTML = newDiv.innerHTML.replace(/value-nfs/g, nfs);

            // Append new div to form
            document.getElementById('array-list').appendChild(newDiv);

            // Hide NFSEndPoint for FlashArray
            var element = document.getElementById('nfs-' + arrayId.toString());
            if (arrayType === 'FlashArray') {
                element.parentElement.removeChild(element);
            }
            arrayId++;
        }
        function remove_array(arrayId)
        {
            d = document;
            var myElement = d.getElementById(arrayId);
            var parentElement = d.getElementById('array-list');
            parentElement.removeChild(myElement);
        }
    </script>

    <script>
        $(document).ready(function() {
            var arrays = @json($psoValues['arrays'] ?? []);
            console.log(arrays)
            var count = 0;

            for (const [key1, value1] of Object.entries(arrays)) {
                for (const [key2, value2] of Object.entries(value1)) {
                    for (const [key3, value3] of Object.entries(value2)) {
                        if (key3 === 'MgmtEndPoint') {
                            var mgmt = value3;
                        } else if (key3 === 'APIToken') {
                            var api = value3;
                        } else if (key3 === 'Labels') {
                            var label = value3;
                        } else if (key3 === 'NFSEndPoint') {
                            var nfs = value3;
                        }
                    }
                    add_array(key1.slice(0, -1), mgmt, api, label, nfs)
                }
                count++;
            }

        });
    </script>
@endsection