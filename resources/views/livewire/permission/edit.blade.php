<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="bg-white dark:bg-gray-800 rounded-xl lg:border border-gray-200 dark:border-gray-700 p-2 lg:p-6">
        <flux:field>
            <flux:label for="country_id" required>{{ __('messages.permission.edit.label_role') }}</flux:label>
            <flux:select id="role" data-testid="role" wire:model.live="role">
                <option value="">{{ __('messages.permission.edit.placeholder_role') }}</option>
                @if (!empty($roles))
                @foreach ($roles as $i => $role)
                <option value="{{ $i }}">
                    {{ ucwords(strtolower($role)) }}
                </option>
                @endforeach
                @endif
            </flux:select>
            <flux:error name="role" />
        </flux:field>

        <hr class="my-5 border-gray-300" />

        <!-- Permissions Section -->
        @if (!empty($getAllPermissions) && $showAllPermissions)
        <div x-data="{ openModule: null }" class="space-y-4">
            @foreach ($getAllPermissions as $permission)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                <!-- Header -->
                <button type="button" @click="openModule = (openModule === {{ $permission['id'] }} ? null : {{ $permission['id'] }})" class="w-full flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $permission['label'] }}</span>
                    <svg x-show="openModule !== {{ $permission['id'] }}" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg x-show="openModule === {{ $permission['id'] }}" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>

                <!-- Sub Permissions -->
                <div x-show="openModule === {{ $permission['id'] }}" x-transition class="p-4 border-t border-gray-200 dark:border-gray-700">
                    @if (!empty($permission['sub_permissions']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($permission['sub_permissions'] as $subPermission)
                        <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <input id="perm-{{ $permission['id'] }}-{{ $subPermission['id'] }}" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="{{ $subPermission['id'] }}" @checked($subPermission['is_permission']) wire:change="setUnsetPermission({{ $subPermission['id'] }}, $event.target.checked, {{ $permission['id'] }})">
                            <label for="perm-{{ $permission['id'] }}-{{ $subPermission['id'] }}" class="text-sm text-gray-900 dark:text-gray-200">
                                {{ $subPermission['label'] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-500">No sub-permissions available</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
