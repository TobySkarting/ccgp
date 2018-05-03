@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Login</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">Username</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="customFile" class="col-md-4 col-form-label text-md-right">Password Images</label>
    
                            <div class="col-md-6">
                                <div class="custom-file">
                                    <input id="images" name="images" type="file" class="image-upload custom-file-input{{ $errors->has('images') ? ' is-invalid' : '' }}" id="customFile" aria-describedby="fileHelp">
                                    <label class="custom-file-label" for="customFile">Choose file</label>
                                    <div class="progress" style="display: none">
                                        <div id="upload-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                                    </div>
                                    @if ($errors->has('images'))
                                        <span class="invalid-feedback">
                                            <strong>{{ $errors->first('images') }}</strong>
                                        </span>
                                    @endif
                                    <span id="upload-error" class="invalid-feedback">
                                        <strong></strong>
                                    </span>
                                </div>
                                <small id="fileHelp" class="form-text text-muted">Upload your password images one by one. Your images and their order will be your login credential.</small>
                            </div>
                        </div>

                        @if (session('pics'))
                            <div class="form-group">
                                <div id="uploaded" class="offset-md-1 col-md-10">
                                        <div class="row">
                                            <div class="col-md-4 text-center">Received</div>
                                            <div class="col-md-4 text-center">Decrypted</div>
                                            <div class="col-md-4 text-center">Matched</div>
                                        </div>
                                        @foreach (session('pics') as $pic)
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <img class="img-fluid img-thumbnail" src="{{ isset($pic[0]) ? $pic[0] : '/cross.png' }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <img class="img-fluid img-thumbnail" src="{{ isset($pic[1]) ? $pic[1] : '/cross.png' }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <img class="img-fluid img-thumbnail" src="{{ isset($pic[2]) ? $pic[2] : '/cross.png' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <div id="uploaded" class="offset-md-1 col-md-10">
                                <div class="row">
                                    <div class="col-md-3 text-center">Original</div>
                                    <div class="col-md-3 text-center">Cropped</div>
                                    <div class="col-md-3 text-center">Encrypted</div>
                                    <div class="col-md-3 text-center">Hidden</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button id="submit" type="submit" class="btn btn-primary">
                                    Login
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript" src="/js/ImageUploader.js"></script>
<script>
$(function ($) {
    console.log("ready");
    /* Initialization of input elements and ImageUploader.js */
    $("input.image-upload").each(function(index) {
        var uploader = new ImageUploader({
            'inputElement': $(this).get(0), 
            'onProgress': function (info) {
                /* Updating the progress bar */
                if (info['currentItemTotal'] <= 0) {
                    console.log(info);
                    return;
                }
                var progress = info['currentItemDone'] * 100.0 / info['currentItemTotal'];
                $('#upload-progress').attr('aria-valuenow', progress).css('width', progress + '%');
            },
            'onComplete': function () {
                /* Enable upload button */
                $('#submit').removeAttr('disabled');
                /* Hide progress bar */
                $("div.progress").hide();
            },
            'key': localStorage.getItem('key'),
            'login': true,
            'csrfToken': "{{ csrf_token() }}",
            'workspace': $('#uploaded')[0],
            /* Add rand parameter to prevent accidental caching of the image by the server */
            'uploadUrl': 'login/add?id=' + index + '&rand=' + new Date().getTime(),
            'debug': true
        });
    });
    
    /* The function below is triggered every time the user selects a file */
    $("input.image-upload").change(function(index) {
        /* We will check additionally the extension of the image if it's correct and we support it */
        var extension = $(this).val();
        if (extension.length > 0) {
            extension = extension.match(/[^.]+$/).pop().toLowerCase();
            extension = ~$.inArray(extension, ['jpg', 'jpeg', 'png']);
        } else {
            event.preventDefault();
            return;
        }
        
        if (!extension) {
            event.preventDefault();
            $("input.image-upload").addClass("is-invalid");
            $('#upload-error strong').text("Unsupported image format");
            return;
        }
        $("input.image-upload").removeClass("is-invalid");
        /* Disable upload button until current upload completes */
        $('#submit').prop('disabled', true);
        /* Show progress bar */
        $("div.progress").show();
        /* If you want, you can show a preview of the selected image to the user, but to keep the code simple, we will skip this step */
    });
});
</script>
@endsection
