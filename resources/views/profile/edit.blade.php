<x-app-layout title="Mi cuenta" eyebrow="Cuenta" heading="Mi cuenta">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="a-card p-6 lg:col-span-2">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="a-card p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="a-card p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
