<flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700"
    x-data="{
        searchTerm: '',
        normalize(value) { return (value || '').toString().toLowerCase(); },
        matches(label) {
            const term = this.normalize(this.searchTerm);
            if (!term) return true;
            return this.normalize(label).includes(term);
        },
        matchesGroup(labels = []) {
            return labels.some(label => this.matches(label));
        }
    }">
    <flux:sidebar.header>
        <flux:sidebar.brand
            href="#"
            logo="https://fluxui.dev/img/demo/logo.png"
            logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
            name="Acme Inc."
        />
        <flux:sidebar.collapse
            class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2 cursor-pointer"
        />
    </flux:sidebar.header>

    <flux:input icon="magnifying-glass" type="search" inputmode="search" placeholder="{{ __('messages.side_menus.label_search') }}"
        aria-label="{{ __('messages.side_menus.label_search') }}" x-model="searchTerm" x-on:keydown.escape="searchTerm = ''"
        class="w-full text-sm bg-white/80 dark:bg-zinc-800/70 border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-zinc-300 dark:focus:ring-zinc-600" />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" x-show="matches($el.dataset.label)" data-label="{{ __('messages.side_menus.dashboard') }}" wire:navigate>
            {{ __('messages.side_menus.dashboard') }}
        </flux:sidebar.item>

        @if (Gate::allows('view-role'))

            <flux:sidebar.item
                icon='users'
                href='/role'
                data-testid='side_menu_role'
                :current="request()->routeIs('role.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.role') }}"
                wire:navigate>
                {{ __('messages.side_menu.role') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-user'))

            <flux:sidebar.item
                icon='users'
                href='/user'
                data-testid='side_menu_user'
                :current="request()->routeIs('user.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.user') }}"
                wire:navigate>
                {{ __('messages.side_menu.user') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-category'))

            <flux:sidebar.item
                icon='users'
                href='/category'
                data-testid='side_menu_category'
                :current="request()->routeIs('category.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.category') }}"
                wire:navigate>
                {{ __('messages.side_menu.category') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-sub_category'))

            <flux:sidebar.item
                icon='users'
                href='/sub_category'
                data-testid='side_menu_sub_category'
                :current="request()->routeIs('sub_category.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.sub_category') }}"
                wire:navigate>
                {{ __('messages.side_menu.sub_category') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-product'))

            <flux:sidebar.item
                icon='users'
                href='/product'
                data-testid='side_menu_product'
                :current="request()->routeIs('product.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.product') }}"
                wire:navigate>
                {{ __('messages.side_menu.product') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-appointment'))

            <flux:sidebar.item
                icon='users'
                href='/appointment'
                data-testid='side_menu_appointment'
                :current="request()->routeIs('appointment.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.appointment') }}"
                wire:navigate>
                {{ __('messages.side_menu.appointment') }}
            </flux:sidebar.item>
        
@endif
@if (Gate::allows('view-sms-template'))

            <flux:sidebar.item
                icon='users'
                href='/sms-template'
                data-testid='side_menu_sms-template'
                :current="request()->routeIs('sms-template.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.sms_template') }}"
                wire:navigate>
                {{ __('messages.side_menu.sms_template') }}
            </flux:sidebar.item>
        
@endif

        <flux:sidebar.group expandable :expanded="request()->routeIs('email-format') || request()->routeIs('email-template.*')" heading="{{ __('messages.side_menus.templates') }}" class="grid"
            x-show="matchesGroup([
                '{{ __('messages.side_menus.templates') }}',
                '{{ __('messages.side_menus.templates_email_formats') }}',
                '{{ __('messages.side_menus.templates_email_templates') }}'
            ])">
            @if (Gate::allows('view-emailformats'))
                <flux:sidebar.item icon="envelope" :href="route('email-format')" :current="request()->routeIs('email-format')" wire:navigate
                x-show="matches($el.dataset.label)" data-label="{{ __('messages.side_menus.templates_email_formats') }}">
                {{ __('messages.side_menus.templates_email_formats') }}
                </flux:sidebar.item>
            @endif
            @if (Gate::allows('view-emailtemplates'))
                <flux:sidebar.item icon="envelope" :href="route('email-template.index')" :current="request()->routeIs('email-template.*')" wire:navigate
                x-show="matches($el.dataset.label)" data-label="{{ __('messages.side_menus.templates_email_templates') }}">
                {{ __('messages.side_menus.templates_email_templates') }}
                </flux:sidebar.item>
            @endif
        </flux:sidebar.group>

        <flux:sidebar.group expandable heading="{{ __('messages.side_menus.user_import') }}" class="grid">
            @if (Gate::allows('import-role'))

            <flux:sidebar.item
                icon='users'
                href='/role-imports'
                data-testid='side_menu_role-imports'
                :current="request()->routeIs('role-imports.*')"
                x-show="matches($el.dataset.label)"
                data-label="{{ __('messages.side_menu.role') }}"
                wire:navigate>
                {{ __('messages.side_menu.role') }}
            </flux:sidebar.item>
        
@endif


        </flux:sidebar.group>

    </flux:sidebar.nav>
</flux:sidebar>


{{-- ✅ Responsive Header --}}
<flux:header class="block! h-16 bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 px-4!">
    {{-- 📱 Mobile Navbar --}}
    <flux:navbar class="flex items-center justify-between lg:hidden py-3">
        <div class="flex items-center gap-3">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        </div>

        <div class="flex items-center gap-2">
            {{-- Theme Toggle --}}
            <flux:dropdown x-data align="end">
                <flux:button variant="subtle" square aria-label="Appearance">
                    <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">{{ __('messages.appearance.light') }}</flux:menu.item>
                    <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">{{ __('messages.appearance.dark') }}</flux:menu.item>
                    <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">{{ __('messages.appearance.system') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            {{-- Language --}}
            <livewire:language-switcher :user="auth()->user()" />

            {{-- User Dropdown --}}
            <flux:dropdown position="top" align="end">
                <flux:profile class="cursor-pointer" :initials="auth()->user()->initials()" data-testid="user_profile_dropdown"/>
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />
<flux:menu.item :href="route('settings.password')" data-testid="side_menu_change_password" wire:navigate>
    @lang('messages.side_menus.label_change_password')
</flux:menu.item>
                    <flux:menu.separator />

                    @if(Gate::allows('edit-permission') || Auth::user()->role_id == config('constants.roles.admin'))
                        <flux:menu.item :href="route('permission')" data-testid="side_menu_permissions" wire:navigate>
                            @lang('messages.side_menus.label_permissions')
                        </flux:menu.item>
                        <flux:menu.separator />
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" data-testid="side_menu_logout" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('messages.side_menus.label_logout') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:navbar>

    {{-- 💻 Desktop Header --}}
    <div class="hidden lg:flex items-center justify-between w-full mt-3">
        {{-- Left-aligned Breadcrumb (hidden on mobile) --}}
        <div class="flex-1 hidden md:block">
            <livewire:breadcrumb />
        </div>

        {{-- Right Section --}}
        <div class="flex items-center gap-0 pr-0 flex-wrap justify-end">
            {{-- Theme Dropdown --}}
            <flux:dropdown x-data align="end">
                <flux:button variant="subtle" square class="group cursor-pointer" aria-label="Preferred color scheme" data-testid="side_menu_appearance">
                    <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
                    <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="sun" class="cursor-pointer" x-on:click="$flux.appearance = 'light'">
                        {{ __('messages.appearance.light') }}
                    </flux:menu.item>
                    <flux:menu.item icon="moon" class="cursor-pointer" x-on:click="$flux.appearance = 'dark'">
                        {{ __('messages.appearance.dark') }}
                    </flux:menu.item>
                    <flux:menu.item icon="computer-desktop" class="cursor-pointer" x-on:click="$flux.appearance = 'system'">
                        {{ __('messages.appearance.system') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            {{-- Language Switcher --}}
            <livewire:language-switcher :user="auth()->user()" />

            {{-- User Menu --}}
            <flux:dropdown position="top" align="end">
                <flux:profile class="cursor-pointer" :initials="auth()->user()->initials()" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />
<flux:menu.item :href="route('settings.password')" data-testid="side_menu_change_password" wire:navigate>
    @lang('messages.side_menus.label_change_password')
</flux:menu.item>
                    <flux:menu.separator />

                    @if(Gate::allows('edit-permission') || Auth::user()->role_id == config('constants.roles.admin'))
                        <flux:menu.item :href="route('permission')" data-testid="side_menu_permissions" wire:navigate>
                            @lang('messages.side_menus.label_permissions')
                        </flux:menu.item>
                        <flux:menu.separator />
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" data-testid="side_menu_logout" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                            {{ __('messages.side_menus.label_logout') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</flux:header>
