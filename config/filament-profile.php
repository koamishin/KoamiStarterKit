<?php

use App\Filament\Profile\ProfileInformationForm;
use Ipatco\FilamentProfile\Pages\EditProfile;

return [

    /*
    |--------------------------------------------------------------------------
    | Profile page
    |--------------------------------------------------------------------------
    |
    | The Filament page class used for editing the authenticated user's profile.
    | Override this via FilamentProfilePlugin::make()->profilePage(...), or here.
    |
    */

    'profile_page' => EditProfile::class,

    /*
    |--------------------------------------------------------------------------
    | Profile information form
    |--------------------------------------------------------------------------
    |
    | Class that builds the fields in the Profile information section.
    | Run `php artisan filament-profile:install` to publish an editable copy
    | to app/Filament/Profile/ProfileInformationForm.php, then point this
    | config at that class (the install command does this for you).
    |
    */

    'profile_information_form' => ProfileInformationForm::class,

    /*
    |--------------------------------------------------------------------------
    | Simple profile layout
    |--------------------------------------------------------------------------
    |
    | When false (default), the profile page uses the full panel layout with
    | the sidebar. Set true for Filament's compact auth-style layout.
    |
    */

    'simple_profile' => false,

];
