<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Nama dan email hanya bisa diubah oleh admin lewat menu Kelola User.
        </p>
    </header>

    {{-- Tampil saja: nama & email (username login) dikelola admin. --}}
    <div class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" class="mt-1 block w-full bg-gray-100 text-gray-600" :value="$user->getRawOriginal('name')" disabled />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" class="mt-1 block w-full bg-gray-100 text-gray-600" :value="$user->email" disabled />
        </div>
    </div>
</section>
