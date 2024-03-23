<div class="flex items-center justify-between p-3 shadow-md">
    <div class="flex flex-col items-center">
        
            <img src="{{ asset('assets/images/cpdlogo.png') }}" alt="chicago park district logo" class="w-auto h-12 object-fit">
        
        <p class="text-base md:text-2xl">Black League</p>
    </div>
    
    <ul class="flex gap-4">
        @guest
            @if (Route::has('login'))
                <li>
                    <a class="px-2 py-2 font-medium text-white bg-green-600 border-green-500 rounded hover:bg-green-500 hover:shadow" href="{{ route('login') }}">{{ __('Login') }}</a>
                </li>
            @endif

            @if (Route::has('register'))
                <li>
                    <a class="px-2 py-2 font-medium text-white bg-green-600 border-green-500 rounded hover:bg-green-500 hover:shadow" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endif
        @else
            <div class="flex flex-col items-end">
                
                <li class="flex items-center gap-1 text-xs capitalize md:text-base">
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} - <span class="px-1 text-white bg-green-800 rounded">{{ Auth::user()->role }}</span>
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