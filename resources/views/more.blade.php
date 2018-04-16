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

                    <div class="form-group row">

                        <label for="exampleInputFile" class="col-md-4 col-form-label text-md-right">Password Images</label>

                        <div class="col-md-6">
                            <input id="images" name="images" type="file" class="image-upload form-control form-control-file{{ $errors->has('images') ? ' is-invalid' : '' }}" id="exampleInputFile" aria-describedby="fileHelp">
                            
                            @if ($errors->has('images'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('images') }}</strong>
                                </span>
                            @endif
                            <small id="fileHelp" class="form-text text-muted">Upload your password images one by one. Your images and their order will be your login credential.</small>

                            <div id="uploaded">
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="more/login">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">Name</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
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
        var id=$(this).attr('data-id');
        var uploader = new ImageUploader({
            'inputElement': $(this).get(0), 
            'onProgress': function (info) {
                /* Updating the progress bar */
                if (info['currentItemTotal']<=0)
                    return; 
                var progress=info['currentItemDone']*100.0/info['currentItemTotal'];
                $('#upload-progress-'+id+' div').css('width',progress+'%');
            }, 
            'onComplete': function () {
                /* Enable upload button */
                $('#upload-button-'+id).removeProp('disabled');
                /* Hide progress bar */
                $("#upload-container-"+id).addClass("uk-hidden");
            },
            'csrfToken': "{{ csrf_token() }}",
            'workspace': $('#uploaded')[0],
            /* Add rand parameter to prevent accidental caching of the image by the server */
            'uploadUrl': 'more/add?id=' + id + '&action=register&rand=' + new Date().getTime(),
            'debug': true
        });
    });
    
    /* The function below is triggered every time the user selects a file */
    $("input.image-upload").change(function(index) {
        /* We will check additionally the extension of the image if it's correct and we support it */
        var extension = $(this).val();
        if (extension.length>0){
            extension = extension.match(/[^.]+$/).pop().toLowerCase();
            extension = ~$.inArray(extension, ['jpg', 'jpeg', 'png']);
        }
        else{
            event.preventDefault();
            return;
        }
        
        if (!extension)
        {
            event.preventDefault();
            console.error('Unsupported image format');
            return;
        }
        var id=$(this).attr('data-id');
        /* Disable upload button until current upload completes */
        $('#upload-button-'+id).prop('disabled',true);
        /* Show progress bar */
        $("#upload-container-"+id).removeClass("uk-hidden");
        /* If you want, you can show a preview of the selected image to the user, but to keep the code simple, we will skip this step */
    });
});
</script>
@endsection
