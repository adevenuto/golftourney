@extends('layouts.app')

@section('content')
    <div class="max-w-lg p-8 mx-auto my-10 bg-white shadow rounded-xl shadow-slate-300">
        <h1 class="pb-5 text-4xl font-medium">Login</h1>
        
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="flex flex-col space-y-5">
                <label for="email">
                    <p class="pb-2 font-medium text-slate-700">{{ __('Email Address') }}</p>
                    <input id="email" type="email" class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="text-red-500" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </label>
                <label for="password">
                    <p class="pb-2 font-medium text-slate-700">{{ __('Password') }}</p>

                    <input id="password" type="password" class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="password" autofocus>

                    @error('password')
                        <span class="text-red-500" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </label>
                <div class="flex flex-row justify-between">
                    <label for="remember" class="">
                        <input type="checkbox" id="remember" class="w-4 h-4 border-slate-200 focus:bg-green-600" {{ old('remember') ? 'checked' : '' }}>
                        {{ __('Remember Me') }}
                    </label>
                    <a href="{{ route('password.request') }}" class="font-medium text-green-600">Forgot Password?</a>
                </div>
                <button class="inline-flex items-center justify-center w-full py-3 space-x-2 font-medium text-white bg-green-600 border-green-500 rounded-lg hover:bg-green-500 hover:shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Login</span>
                </button>
                <p class="text-center">Not registered yet? <a href="{{ url('register') }}" class="inline-flex items-center space-x-1 font-medium text-green-600"><span>Register now </span><span><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg></span></a></p>
            </div>
        </form>
    </div>
@endsection
