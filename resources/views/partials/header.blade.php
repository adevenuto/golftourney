<div class="flex items-center justify-between p-3">
    <a href={{ url('/') }} class="text-lg">Golf Tourney</a>
    <ul class="flex gap-4">
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
                {{ Auth::user()->name }} <span class="pb-0.5 px-2 shadow-inner bg-slate-100 text-gray-600 border rounded-full">{{ Auth::user()->role }}</span>
            </li>
            <li>
                <a class="text-xs text-red-500 hover:text-red-600 hover:underline" href="{{ route('logout') }}"
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