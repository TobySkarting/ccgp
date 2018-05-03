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
                        <div id="uploaded">
                            <div class="row">
                                <div class="col-md-4 text-center">Original</div>
                                <div class="col-md-4 text-center">Cropped</div>
                                <div class="col-md-4 text-center">Hided</div>
                            </div>
                            @foreach (session('pics') as $pic)
                                <div class="row">
                                    <div class="col-md-4 text-center">$pic[0]</div>
                                    <div class="col-md-4 text-center">$pic[1]</div>
                                    <div class="col-md-4 text-center">$pic[2]</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
