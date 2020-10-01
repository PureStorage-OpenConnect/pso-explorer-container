@extends('layouts.portal')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="mt-5">
            <p>To access this section of {{ config('app.name', 'PSO eXplorer') }} you'll need to login</p>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="with-banner col-md-4" style="width: 250px;">
            <form class="login-center ng-pristine ng-invalid ng-touched" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="login-inputs">
                    <input autofocus class="form-control login-input ng-pristine ng-invalid ng-touched @error('name') is-invalid @enderror" id="name" name="name" placeholder="Username" value="{{ old('name') }}" required type="text" maxlength="100">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                    <input class="form-control login-input no-top ng-untouched ng-pristine ng-invalid @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required type="password" maxlength="100" autocomplete="current-password">
                    @error('password')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-pure btn-block"> Log In </button>
           </form>
        </div>
    </div>
</div>
@endsection
