@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($yaml)
        <div class="row">
            <div class="col-xs-12 tab-container">
                <div class="with-padding">
                    <div class="with-padding col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <span>PSO upgrade helper</span>
                            </div>
                            <div class="panel-body table-no-filter">
                                <button class="btn btn-pure btn-block" type="button" value="save" id="save">Download <b>values.yaml</b></button>
                                <pre id="values_yaml">{!! $yaml !!}</pre>
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
        function saveTextAsFile()
        {
            var textToWrite = document.getElementById('values_yaml').innerText;
            var textFileAsBlob = new Blob([textToWrite], {type:'text/plain'});
            var fileNameToSaveAs = "values.yaml";

            var downloadLink = document.createElement("a");
            downloadLink.download = fileNameToSaveAs;
            downloadLink.innerHTML = "Download File";
            if (window.webkitURL != null)
            {
                // Chrome allows the link to be clicked
                // without actually adding it to the DOM.
                downloadLink.href = window.webkitURL.createObjectURL(textFileAsBlob);
            }
            else
            {
                // Firefox requires the link to be added to the DOM
                // before it can be clicked.
                downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
                downloadLink.onclick = function(){
                    document.body.removeChild(downloadLink);
                };
                downloadLink.style.display = "none";
                document.body.appendChild(downloadLink);
            }

            downloadLink.click();
        }

        var button = document.getElementById('save');
        button.addEventListener('click', saveTextAsFile);
    </script>
@endsection
