@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    You are logged in!

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
                                                <img class="img-fluid img-thumbnail" src="{{ $pic[0] }}">
                                            </div>
                                            <div class="col-md-4">
                                                <img class="img-fluid img-thumbnail" src="{{ $pic[1] }}">
                                            </div>
                                            <div class="col-md-4">
                                                <img class="img-fluid img-thumbnail" src="{{ $pic[2] }}">
                                            </div>
                                        </div>
                                    @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
