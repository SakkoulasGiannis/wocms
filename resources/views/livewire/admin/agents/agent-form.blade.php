<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $agentId ? 'Edit Agent' : 'Add Agent' }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $agentId ? 'Update staff member details' : 'Create a new staff member' }}</p>
        </div>
        <a href="{{ route('admin.agents.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-arrow-left mr-2"></i>Back to Staff
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left column: Photo -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fa fa-image mr-2 text-gray-400"></i>Photo
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">Auto-saves on upload.</p>
                    </div>
                    <div class="p-6">
                        <div class="aspect-[4/5] w-full rounded-lg overflow-hidden bg-gray-100 mb-4 flex items-center justify-center">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Agent photo preview" class="w-full h-full object-cover">
                            @else
                                <div class="text-gray-400 text-center">
                                    <i class="fa fa-user text-6xl mb-2"></i>
                                    <p class="text-sm">No photo yet</p>
                                </div>
                            @endif
                        </div>

                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Photo</label>
                        <input type="file" wire:model="newPhoto" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div wire:loading wire:target="newPhoto" class="text-xs text-blue-600 mt-2">
                            <i class="fa fa-spinner fa-spin mr-1"></i>Uploading...
                        </div>
                        @error('newPhoto') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror

                        @if($photoUrl)
                        <button type="button" wire:click="removePhoto"
                                wire:confirm="Remove this photo?"
                                class="mt-3 text-xs text-red-600 hover:text-red-800">
                            <i class="fa fa-trash mr-1"></i>Remove photo
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fa fa-cog mr-2 text-gray-400"></i>Settings
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                            <input type="number" wire:model="order" min="0"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Lower numbers appear first.</p>
                            @error('order') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" wire:model="isActive" id="is_active"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-gray-700">Active (visible on website)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Fields -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fa fa-user mr-2 text-gray-400"></i>Basic Information
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input type="text" wire:model.live.debounce.300ms="name"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="e.g. Jane Doe">
                                @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                                <input type="text" wire:model="slug"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="jane-doe">
                                @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role / Title</label>
                            <input type="text" wire:model="role"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="e.g. Senior Real Estate Agent">
                            @error('role') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                            <textarea wire:model="bio" rows="5"
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="Short biography / description"></textarea>
                            @error('bio') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fa fa-address-card mr-2 text-gray-400"></i>Contact
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" wire:model="email"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="name@example.com">
                                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" wire:model="phone"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="+30 1234 567890">
                                @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fa fa-share-alt mr-2 text-gray-400"></i>Social Profiles
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">Full URLs including https://</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-facebook text-blue-600 mr-1"></i>Facebook
                                </label>
                                <input type="url" wire:model="facebook"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="https://facebook.com/username">
                                @error('facebook') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-instagram text-pink-600 mr-1"></i>Instagram
                                </label>
                                <input type="url" wire:model="instagram"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="https://instagram.com/username">
                                @error('instagram') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-linkedin text-blue-700 mr-1"></i>LinkedIn
                                </label>
                                <input type="url" wire:model="linkedin"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="https://linkedin.com/in/username">
                                @error('linkedin') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fa-brands fa-x-twitter mr-1"></i>Twitter / X
                                </label>
                                <input type="url" wire:model="twitter"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="https://twitter.com/username">
                                @error('twitter') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.agents.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition">
                Cancel
            </a>
            <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition disabled:opacity-50">
                <span wire:loading.remove wire:target="save">
                    <i class="fa fa-save mr-2"></i>{{ $agentId ? 'Update Agent' : 'Create Agent' }}
                </span>
                <span wire:loading wire:target="save">
                    <i class="fa fa-spinner fa-spin mr-2"></i>Saving...
                </span>
            </button>
        </div>
    </form>
</div>
