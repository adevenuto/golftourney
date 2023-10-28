@extends('layouts.app')

@section('content')
    <div class="max-w-lg p-8 mx-auto my-10 bg-white rounded shadow shadow-slate-300">
        <h1 class="pb-5 text-4xl font-medium">Register</h1>
        
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="flex flex-col space-y-5">
                <label for="first_name">
                    <p class="pb-2 font-medium text-slate-700">{{ __('First Name') }}</p>
                    <input id="first_name" type="first_name" class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="first_name" autofocus>
                    @error('first_name')
                        <span class="text-red-500" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </label>
                <label for="last_name">
                    <p class="pb-2 font-medium text-slate-700">{{ __('Last Name') }}</p>
                    <input id="last_name" type="last_name" class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="last_name" autofocus>
                    @error('last_name')
                        <span class="text-red-500" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </label>

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
                <label for="password">
                    <p class="pb-2 font-medium text-slate-700">{{ __('Password confirm') }}</p>

                    <input id="password" type="password" class="w-full p-2 border rounded-lg border-slate-200 focus:outline-none focus:border-slate-500 hover:shadow @error('password') is-invalid @enderror" name="password_confirmation" value="{{ old('password_confirmation') }}" required autofocus>

                    @error('password_confirmation')
                        <span class="text-red-500" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </label>
                
                <button class="inline-flex items-center justify-center w-full py-3 space-x-2 font-medium text-white bg-green-600 border-green-500 rounded-lg hover:bg-green-500 hover:shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Register</span>
                </button>
            </div>
        </form>
    </div>
@endsection
