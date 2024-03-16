<div class="flex items-center justify-between p-3 shadow-md">
    <a href={{ url('/') }} class="text-lg md:text-2xl">Black League</a>
    <ul class="flex md:gap-4">
        @guest
            @if (Route::has('login'))
                <li>
                    <a class="p-1 border rounded bg-slate-100 border-slate-300" href="{{ route('login') }}">{{ __('Login') }}</a>
                </li>
            @endif

            @if (Route::has('register'))
                <li>
                    <a class="p-1 border rounded bg-slate-100 border-slate-300" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endif
        @else
            <div class="flex flex-col items-end">
                
                <li class="flex items-center gap-1 text-sm capitalize md:text-lg">
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} - <span class="px-1.5 bg-green-800 text-white rounded text-xs">{{ Auth::user()->role }}</span>
                </li>
                <li>
                    <a class="text-xs text-red-600 hover:text-red-700 hover:underline" href="{{ route('logout') }}"
                        onclick="event.preventDefault();
                        document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>
                </li>  
            </div>
             
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        @endguest
    </ul>
</div>