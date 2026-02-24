@php
    $current = \Filament\Facades\Filament::getCurrentPanel();
    $otherLoginUrl = null;
    $otherLabel = null;
    if ($current) {
        $isAdmin = $current->getId() === 'admin';
        $otherPanel = $isAdmin ? \Filament\Facades\Filament::getPanel('brand') : \Filament\Facades\Filament::getPanel('admin');
        $otherLoginUrl = $otherPanel->getLoginUrl();
        $otherLabel = $isAdmin ? 'Company' : 'Admin';
    }
@endphp
@if($otherLoginUrl && $otherLabel)
    <div class="mt-4 text-center">
        <a href="{{ $otherLoginUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
            Log in to {{ $otherLabel }} instead
        </a>
    </div>
@endif
