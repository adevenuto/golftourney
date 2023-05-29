<div class="flex items-center justify-between p-3">
    <a href={{ url('/') }} class="text-lg">Golf Tourney</a>
    <ul class="flex gap-2">
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
            <li>
                {{ Auth::user()->name }}
            </li>
            <li>
                <a class="p-1 text-red-500 border rounded" href="{{ route('logout') }}"
                    onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                    {{ __('Logout') }}
                </a>
            </li>   
               

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
        @endguest
    </ul>
</div>