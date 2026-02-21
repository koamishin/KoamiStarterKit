<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;

class AuthLayoutRadio extends Radio
{
    protected string $view = 'filament.components.auth-layout-radio';

    protected function setUp(): void
    {
        parent::setUp();

        $this->options([
            'simple' => 'Simple Layout',
            'card' => 'Card Layout',
            'split' => 'Split Layout',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getLayoutOptions(): array
    {
        return [
            'simple' => [
                'label' => 'Simple Layout',
                'description' => 'Clean, centered layout with minimal styling',
                'icon' => Heroicon::OutlinedRectangleStack,
                'preview' => [
                    'type' => 'simple',
                    'classes' => 'flex flex-col items-center justify-center gap-2',
                ],
            ],
            'card' => [
                'label' => 'Card Layout',
                'description' => 'Form wrapped in a card component',
                'icon' => Heroicon::OutlinedCreditCard,
                'preview' => [
                    'type' => 'card',
                    'classes' => 'flex flex-col items-center justify-center',
                ],
            ],
            'split' => [
                'label' => 'Split Layout',
                'description' => 'Side-by-side layout with branding panel',
                'icon' => Heroicon::OutlinedViewColumns,
                'preview' => [
                    'type' => 'split',
                    'classes' => 'grid grid-cols-2 gap-0',
                ],
            ],
        ];
    }
}
