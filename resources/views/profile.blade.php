<x-layouts.shop title="Mon profil">
    <x-account.shell title="Mon profil">
        <div class="max-w-xl space-y-6">
            <div class="border border-line bg-white p-6 sm:p-8">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="border border-line bg-white p-6 sm:p-8">
                <livewire:profile.update-password-form />
            </div>

            <div class="border border-line bg-white p-6 sm:p-8">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </x-account.shell>
</x-layouts.shop>
