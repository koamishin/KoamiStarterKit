<?php

declare(strict_types=1);

namespace App\Filament\Profile;

use Filament\Schemas\Components\Component;
use Ipatco\FilamentProfile\Forms\ProfileInformationForm as BaseProfileInformationForm;
use Ipatco\FilamentProfile\Pages\EditProfile;

class ProfileInformationForm extends BaseProfileInformationForm
{
    /**
     * Configure the fields shown in the Profile information section.
     *
     * Add fields such as phone or avatar here. Keep any custom attributes
     * fillable on your user model so they can be saved.
     *
     * @return array<Component>
     */
    #[\Override]
    public static function configure(EditProfile $page): array
    {
        return [
            $page->getNameFormComponent(),
            $page->getEmailFormComponent(),

            // TextInput::make('phone')->tel()->maxLength(20),
            // FileUpload::make('avatar')->avatar()->image()->directory('avatars'),

            $page->getCurrentPasswordFormComponent(),
        ];
    }
}
