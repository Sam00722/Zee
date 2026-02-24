<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>

    @if($redemptionUrlToShow)
        <div
            class="fi-modal-ctn fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
            x-data="{ copied: false }"
            x-init="$nextTick(() => $el.querySelector('[x-ref=link]')?.focus())"
        >
            <div
                class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity"
                wire:click="clearRedemptionUrl"
            ></div>
            <div
                class="fi-modal-window relative w-full bg-white dark:bg-gray-800 shadow-xl rounded-xl max-w-lg mx-4 overflow-hidden"
                role="dialog"
                aria-modal="true"
                aria-labelledby="redemption-url-heading"
            >
                <div class="p-6">
                    <h2 id="redemption-url-heading" class="text-lg font-semibold text-gray-950 dark:text-white mb-2">
                        Claim link
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        Copy this link to share with the customer so they can claim their withdrawal.
                    </p>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            x-ref="link"
                            readonly
                            value="{{ e($redemptionUrlToShow) }}"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/20 dark:bg-white/5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:text-white"
                        />
                        <button
                            type="button"
                            @click="
                                navigator.clipboard.writeText($refs.link.value);
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            "
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-primary fi-btn-size-sm inline-grid shadow-sm bg-primary-600 hover:bg-primary-500 text-white focus:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-primary-500/50 px-3 py-2 text-sm"
                        >
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="clearRedemptionUrl"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-gray fi-btn-size-sm inline-grid shadow-sm bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 text-gray-950 dark:text-white focus:ring-gray-500/50 dark:focus:ring-white/20 border border-gray-200 dark:border-white/10 px-3 py-2 text-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
