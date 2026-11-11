<x-guest-layout>
    <!-- Mensagens de erro -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- E-mail -->
        <div>
            <label for="email" class="block font-semibold text-sm text-gray-700">E-mail de Acesso</label>
            <input id="email" class="block mt-2 w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm p-3 border bg-gray-50" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-6">
            <label for="password" class="block font-semibold text-sm text-gray-700">Senha</label>
            <input id="password" class="block mt-2 w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm p-3 border bg-gray-50" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Botão de Entrar -->
        <div class="mt-8">
            <button type="submit" class="w-full flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-sm uppercase tracking-wider transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Entrar no Sistema
            </button>
        </div>
    </form>
</x-guest-layout>
