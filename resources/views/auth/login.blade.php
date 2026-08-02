<x-guest-layout>
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          placeholder="tu@correo.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="a-check">
            <span class="text-[11px] uppercase tracking-label text-muted">Recordarme</span>
        </label>

        <div class="flex items-center justify-between gap-4 pt-2">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-[11px] uppercase tracking-label text-muted hover:text-ink">¿Olvidaste tu contraseña?</a>
            @else
                <span></span>
            @endif

            <x-primary-button>Entrar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
