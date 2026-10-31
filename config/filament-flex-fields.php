<?php

declare(strict_types=1);
use Bjanczak\FilamentFlexFields\Support\Geocoding\MapboxGeocodingProvider;

// Public application settings only. FLEX content and promotion transport are publisher-owned.
// Per-field methods override these defaults; see docs/configuration.md for contracts.
return [

    // Application and appearance.
    'enabled' => env('FLEX_FIELDS_ENABLED', true),

    'navigation' => ['sort' => 91],

    // Deployment publishes assets explicitly; local auto-publication is opt-in.
    'assets' => [
        'publish_on_boot' => env('FLEX_FIELDS_PUBLISH_ON_BOOT', false),
        // Explicit local/testing escape hatch. Publication and doctor always remain strict.
        'development_fallback' => false,
        'delivery' => [
            // null preserves existing CDN use; [] restricts transport to this document's origin.
            'allowed_origins' => null,
            'crossorigin' => null,
            'referrer_policy' => null,
            // Resolve hashes from sealed build metadata; does not hash files per request.
            'integrity_from_manifest' => false,
            // Requires import-map integrity support in your supported browser matrix.
            'importmap_integrity' => false,
            // Reject even same-origin resources without an approved exact-URL digest.
            'require_integrity' => false,
            // Exact public URL (including query) => sha256-base64 digest.
            'integrity' => [],
        ],
    ],

    'ui' => [
        'density' => 'comfortable',
        'theme' => [],
        'field_rounding' => 'default',
        'number_stepper_size' => 'md',
        'number_stepper_decrement_icon' => 'hugeicons-minus-sign',
        'number_stepper_increment_icon' => 'hugeicons-add-01',
        'segment_size' => 'md',
        'slider_size' => 'md',
        'switch_size' => 'md',
        'rating_size' => 'md',
        'select_size' => 'md',
        'select_variant' => 'bordered',
        'segment_variant' => 'default',
        'slider_variant' => 'default',
        'switch_variant' => 'default',
        'credit_card_size' => 'md',
        'credit_card_variant' => 'midnight',
        'flex_text_input_size' => 'md',
        'flex_text_input_variant' => 'primary',
        'slug_size' => 'md',
        'slug_variant' => 'primary',
        'price_range_size' => 'md',
        'price_range_variant' => 'primary',
        'flex_text_input_copy_icon' => 'hugeicons-copy-01',
        'flex_text_input_show_password_icon' => 'hugeicons-view',
        'flex_text_input_hide_password_icon' => 'hugeicons-view-off',
        'flex_text_input_emoji_icon' => 'hugeicons-look-top',
        'flex_text_input_microphone_icon' => 'flexhugeicons-mic-01',
        'flex_textarea_emoji_icon' => 'hugeicons-look-top',
        'flex_textarea_microphone_icon' => 'flexhugeicons-mic-01',
        'flex_rich_editor_bold_icon' => 'hugeicons-text-bold',
        'flex_rich_editor_italic_icon' => 'hugeicons-text-italic',
        'flex_rich_editor_link_icon' => 'hugeicons-link-01',
        'flex_rich_editor_clear_formatting_icon' => 'hugeicons-eraser',
        'flex_rich_editor_clear_content_icon' => 'hugeicons-delete-02',
        'phone_size' => 'md',
        'phone_variant' => 'primary',
        'phone_default_country' => 'PL',
        'currency_size' => 'md',
        'currency_variant' => 'primary',
        'phone_suffix_icon' => 'hugeicons-smart-phone-01',
        'country_size' => 'md',
        'country_variant' => 'primary',
        'country_default_country' => 'PL',
        'select_chevron_icon' => 'hugeicons-circle-arrow-down-01',
        'select_clear_icon' => 'hugeicons-cancel-circle',
        'select_selected_option_check_icon' => 'hugeicons-tick-02',
        'address_autocomplete_size' => 'md',
        'address_autocomplete_variant' => 'primary',
        'address_autocomplete_prefix_icon' => 'hugeicons-location-01',
        'address_autocomplete_clear_icon' => 'hugeicons-cancel-circle',
        'dual_listbox_search_icon' => 'hugeicons-search-01',
        'dual_listbox_move_all_right_icon' => 'hugeicons-arrow-right-01',
        'dual_listbox_move_right_icon' => 'hugeicons-arrow-right-02',
        'dual_listbox_swap_icon' => 'hugeicons-arrow-data-transfer-horizontal',
        'dual_listbox_move_left_icon' => 'hugeicons-arrow-left-02',
        'dual_listbox_move_all_left_icon' => 'hugeicons-arrow-left-01',
        'dual_listbox_move_up_icon' => 'hugeicons-arrow-up-01',
        'dual_listbox_move_down_icon' => 'hugeicons-arrow-down-01',
        'color_swatch_section_icon' => 'hugeicons-paint-board',
        'video_play_icon' => 'flexvideojs-play',
        'video_pause_icon' => 'flexvideojs-pause',
        'video_volume_icon' => 'flexvideojs-volume-high',
        'video_mute_icon' => 'flexvideojs-volume-off',
        'video_fullscreen_icon' => 'flexvideojs-fullscreen-enter',
        'video_exit_fullscreen_icon' => 'flexvideojs-fullscreen-exit',
        'video_picture_in_picture_icon' => 'flexvideojs-pip-enter',
        'video_exit_picture_in_picture_icon' => 'flexvideojs-pip-exit',
        'video_placeholder_icon' => 'hugeicons-video-01',
        'video_auto_hide_controls' => true,
        'video_controls_layout' => 'default',
        'flex_checklist_lock_icon' => 'hugeicons-lock-password',
        'flex_radiolist_lock_icon' => 'hugeicons-lock-password',
        'item_card_chevron_icon' => 'hugeicons-arrow-right-01',
        'audio_play_icon' => 'hugeicons-play',
        'audio_pause_icon' => 'hugeicons-pause',
        'signature_undo_icon' => 'hugeicons-undo-02',
        'signature_clear_icon' => 'hugeicons-reload',
        'signature_download_icon' => 'hugeicons-download-01',
        'signature_fullscreen_icon' => 'hugeicons-arrow-expand-diagonal-01',
        'signature_pdf_preview_icon' => 'hugeicons-file-01',
        'signature_close_icon' => 'hugeicons-cancel-01',
        'video_size' => 'md',
        'audio_size' => 'md',
        'icon_picker_sets' => ['hugeicons'],
        'icon_picker_size' => 'md',
        'icon_picker_variant' => 'bordered',
        'icon_picker_index_cache_days' => 7,
        'icon_picker_svg_cache_days' => 30,
        'icon_picker_catalog_cache_days' => 7,
        'icon_picker_cache_namespace' => 'v4',
        'icon_picker_search_cache_minutes' => 60,
        'icon_picker_use_bundled_manifest' => true,
        // null icon defaults inherit the related icon or semantic toolbar default.
        'schedule_size' => 'md',
        'schedule_variant' => 'primary',
        'date_time_size' => 'md',
        'date_time_variant' => 'primary',
        'timezone_size' => 'md',
        'timezone_variant' => 'primary',
        'flex_checklist_size' => 'md',
        'choice_cards_size' => 'md',
        'choice_cards_variant' => 'default',
        'traffic_split_size' => 'md',
        'traffic_split_variant' => 'default',
        'dual_listbox_size' => 'md',
        'dual_listbox_variant' => 'bordered',
        'flex_radiolist_size' => 'md',
        'matrix_choice_size' => 'md',
        'tags_size' => 'md',
        'flex_textarea_size' => 'md',
        'flex_textarea_variant' => 'primary',
        'bubble_choice_size' => 'md',
        'bubble_choice_variant' => 'soft',
        'barcode_scanner_size' => 'md',
        'barcode_scanner_variant' => 'primary',
        'credit_card_input_variant' => 'primary',
        'video_settings_icon' => 'flexvideojs-gear',
        'video_speed_icon' => 'flexvideojs-speed',
        'video_quality_icon' => 'flexvideojs-switches',
        'video_cast_icon' => 'flexvideojs-cast-enter',
        'video_airplay_icon' => 'flexvideojs-airplay-enter',
        'video_volume_low_icon' => null,
        'video_exit_cast_icon' => null,
        'video_exit_airplay_icon' => null,
        'flex_text_input_copy_success_icon' => 'hugeicons-tick-02',
        'microphone_icon' => 'flexhugeicons-mic-01',
        'stop_icon' => 'hugeicons-stop',
        'trash_icon' => 'hugeicons-delete-02',
        'checkmark_icon' => 'hugeicons-tick-02',
        'flex_rich_editor_undo_icon' => null,
        'flex_rich_editor_redo_icon' => null,
        'flex_rich_editor_underline_icon' => null,
        'flex_rich_editor_strike_icon' => null,
        'flex_rich_editor_text_color_icon' => null,
        'flex_rich_editor_h1_icon' => null,
        'flex_rich_editor_h2_icon' => null,
        'flex_rich_editor_h3_icon' => null,
        'flex_rich_editor_blockquote_icon' => null,
        'flex_rich_editor_code_icon' => null,
        'flex_rich_editor_code_block_icon' => null,
        'flex_rich_editor_bullet_list_icon' => null,
        'flex_rich_editor_ordered_list_icon' => null,
        'flex_rich_editor_attach_files_icon' => null,
        'flex_rich_editor_fullscreen_icon' => null,
        'flex_rich_editor_youtube_icon' => null,

    ],

    // Storage, schemas and permissions.
    'values_column' => env('FLEX_FIELDS_VALUES_COLUMN', 'flex_field_values'),

    'schemas' => [
    ],

    'schema' => [
        // Stored/imported schemas may select only references approved here by application PHP.
        // Per field type: ['models' => [User::class], 'views' => ['forms.user-option'], 'methods' => ['loadOptions']].
        // This does not grant record access: policies and tenant-scoped queries remain required.
        'configuration_references' => [],
        'resource_enabled' => env('FLEX_FIELDS_SCHEMA_RESOURCE_ENABLED', false),
        'navigation_group' => env('FLEX_FIELDS_SCHEMA_NAV_GROUP', 'Flex Fields'),
        'navigation_sort' => (int) env('FLEX_FIELDS_SCHEMA_NAV_SORT', 90),
        'registry_persistence' => env('FLEX_FIELDS_SCHEMA_REGISTRY_DB', true),
        'sync_from_database' => env('FLEX_FIELDS_SCHEMA_SYNC_FROM_DB', true),
        'sync_on_save' => env('FLEX_FIELDS_SCHEMA_SYNC_ON_SAVE', true),
        'default_target_type' => env('FLEX_FIELDS_SCHEMA_DEFAULT_TARGET', 'App\\Models\\Model'),
        'policy_ability' => env('FLEX_FIELDS_SCHEMA_POLICY_ABILITY', 'manageFlexFieldSchemas'),
        'tenant_resolver' => null,
        'rbac_user_key_resolver' => null,
        'scope_resource_by_tenant' => env('FLEX_FIELDS_SCHEMA_SCOPE_BY_TENANT', false),
        'management_page_enabled' => env('FLEX_FIELDS_SCHEMA_MANAGEMENT_PAGE', true),
        'management_navigation_sort' => (int) env('FLEX_FIELDS_SCHEMA_MANAGEMENT_NAV_SORT', 89),
        'entity_discovery' => [
            'from_filament_resources' => env('FLEX_FIELDS_ENTITY_DISCOVERY_RESOURCES', true),
            'paths' => [],
            'namespace' => null,
        ],
        'entities' => [
        ],
    ],

    'audit' => [
        'enabled' => env('FLEX_FIELDS_AUDIT_ENABLED', true),
        'column' => env('FLEX_FIELDS_AUDIT_COLUMN', 'flex_field_audit'),
        'max_entries' => (int) env('FLEX_FIELDS_AUDIT_MAX_ENTRIES', 500),
    ],

    'validation_presets' => [
        'required',
        'nullable',
        'email',
        'url',
        'numeric',
        'integer',
        'min',
        'max',
        'regex',
        'unique',
    ],

    // Field behavior.
    'select' => [
        'max_page_size' => (int) env('FLEX_FIELDS_SELECT_MAX_PAGE_SIZE', 1000),
        'max_page_bytes' => (int) env('FLEX_FIELDS_SELECT_MAX_PAGE_BYTES', 1048576),
        'max_search_bytes' => 4096,
        'max_search_characters' => 512,
        'search_cache_entries' => 64,
        'search_cache_bytes' => 4194304,
        'search_rate_limit_per_minute' => (int) env('FLEX_FIELDS_SELECT_SEARCH_RATE_LIMIT', 60),
    ],

    'currencies' => [
    ],

    'slug' => [
        'field_title' => 'title',
        'field_slug' => 'slug',
        'url_host' => env('APP_URL'),
        'action_button_labels' => true,
        'translatable_locales' => null,
        'slug_source_locale' => null,
        'spatie_translatable' => false,
        'required_title_locales' => null,
    ],

    'translatable' => [
        'locales' => null,
        'locale_labels' => null,
        'empty_badge_label' => null,
        'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
    ],

    'rich_editor' => [
        'reading_time_words_per_minute' => 200,
        'toolbar_roles' => [
            'author' => [
                ['bold', 'italic', 'underline'],
                ['link', 'attachFiles'],
            ],
            'editor' => [
                ['undo', 'redo'],
                ['bold', 'italic', 'underline', 'strike'],
                ['link', 'attachFiles'],
                ['bulletList', 'orderedList'],
            ],
            'admin' => [
                ['undo', 'redo'],
                ['bold', 'italic', 'underline', 'strike', 'code'],
                ['h1', 'h2', 'h3'],
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['blockquote', 'codeBlock'],
                ['bulletList', 'orderedList'],
                ['link', 'attachFiles'],
                ['clearFormatting', 'clearContent'],
            ],
        ],
    ],

    // Maps and external services.
    'location' => [
        'map_provider' => env('FLEX_FIELDS_MAP_PROVIDER', 'mapbox'),
        'mapbox_assets' => [
            'script' => 'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.js',
            'stylesheet' => 'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.css',
            'module' => false,
        ],
        'maplibre_assets' => [
            'script' => 'https://unpkg.com/maplibre-gl@6.11.2/dist/maplibre-gl.mjs',
            'stylesheet' => 'https://unpkg.com/maplibre-gl@6.11.2/dist/maplibre-gl.css',
            'module' => true,
        ],
    ],

    'mapbox' => [
        'access_token' => env('MAPBOX_ACCESS_TOKEN'),
        'use_server_proxy' => env('FLEX_FIELDS_MAPBOX_SERVER_PROXY', true),
        'default_language' => env('FLEX_FIELDS_MAPBOX_LANGUAGE', null),
        'cache_ttl_seconds' => (int) env('FLEX_FIELDS_MAPBOX_CACHE_TTL', 3600),
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_MAPBOX_RATE_LIMIT', 60),
        'proxy_prefix' => 'flex-fields',
        'proxy_middleware' => ['web', 'auth'],
    ],

    'geocoding' => [
        'driver' => env('FLEX_FIELDS_GEOCODING_DRIVER', 'mapbox'),
        'mapbox_provider' => MapboxGeocodingProvider::class,
        'circuit_breaker' => [
            'enabled' => env('FLEX_FIELDS_GEOCODING_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('FLEX_FIELDS_GEOCODING_CB_THRESHOLD', 5),
            'open_seconds' => (int) env('FLEX_FIELDS_GEOCODING_CB_OPEN_SECONDS', 60),
            'cache_scope' => null,
            'probe_seconds' => 60,
        ],
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_GEOCODING_RATE_LIMIT', env('FLEX_FIELDS_MAPBOX_RATE_LIMIT', 60)),
    ],

    'routes' => ['prefix' => 'flex-fields'],

    'file_upload' => ['url_fetch_timeout_seconds' => 10],

    'link_preview' => [
        // macOS FPM uses a bounded fresh PHP process; other platforms retain native DNS.
        'dns_strategy' => 'auto',
        'route_prefix' => 'flex-fields',
        'middleware' => ['web', 'auth'],
        'cache_ttl_seconds' => (int) env('FLEX_FIELDS_LINK_PREVIEW_CACHE_TTL', 86_400),
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_LINK_PREVIEW_RATE_LIMIT', 30),
        'timeout_seconds' => (int) env('FLEX_FIELDS_LINK_PREVIEW_TIMEOUT', 8),
    ],

    'audio' => [
        'transcription' => [
            'default_model' => env('FLEX_FIELDS_WHISPER_MODEL', 'Xenova/whisper-tiny'),
            'default_quantized' => env('FLEX_FIELDS_WHISPER_QUANTIZED', true),
            'default_multilingual' => env('FLEX_FIELDS_WHISPER_MULTILINGUAL', true),
            'default_language' => env('FLEX_FIELDS_WHISPER_LANGUAGE'),
            'default_task' => env('FLEX_FIELDS_WHISPER_TASK', 'transcribe'),
            'languages' => [],
        ],
    ],

    // Media and operational controls.
    'security' => [
        'allow_http_media' => env('FLEX_FIELDS_ALLOW_HTTP_MEDIA', false),
    ],

    'media_capture' => [
        'max_bytes' => 268435456,
        'min_bytes' => 0,
        'mime_types' => [],
        'visibility' => 'private',
        'verify_integrity' => true,
        'uploads' => [
            'staging' => true,
            'auto_recovery' => true,
            'manifest_disk' => env('FLEX_FIELDS_UPLOAD_MANIFEST_DISK', 'local'),
            'manifest_directory' => 'media-os-upload-receipts',
            'lease_directory' => 'media-os-upload-leases',
        ],
        'images' => [
            'source_pixels' => 16000000,
            'output_pixels' => 16000000,
            'variants' => 16,
            'working_bytes' => 268435456,
            'queue' => [
                // Private shared storage readable by both web and queue workers.
                'journal_disk' => env('FLEX_FIELDS_CONVERSION_JOURNAL_DISK', 'local'),
                'journal_directory' => 'media-os-conversions',
                'prune_schedule_enabled' => true,
            ],
        ],
        'staging' => [
            'disk' => env('FLEX_FIELDS_MEDIA_STAGING_DISK', 'local'),
            'directory' => 'media-os-staging',
            'ttl_seconds' => 86400,
            'receipt_ttl_seconds' => 604800,
            'lock_seconds' => 300,
            'lock_wait_seconds' => 10,
            'prune_batch_size' => 1000,
            'schedule_enabled' => true,
        ],
        'disk' => env('FLEX_FIELDS_MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),
        'require_virus_scan' => env('FLEX_FIELDS_REQUIRE_VIRUS_SCAN', false),
        'scan_before_persist' => env('FLEX_FIELDS_SCAN_BEFORE_PERSIST', true),
        'scan_after_persist' => env('FLEX_FIELDS_SCAN_AFTER_PERSIST', true),
        'quarantine_disk' => env('FLEX_FIELDS_QUARANTINE_DISK'),
        'auto_signed_urls' => env('FLEX_FIELDS_MEDIA_AUTO_SIGNED_URLS', true),
        'signed_url_minutes' => (int) env('FLEX_FIELDS_MEDIA_SIGNED_URL_MINUTES', 15),
        'transcription' => env('FLEX_FIELDS_MEDIA_TRANSCRIPTION'),
        'transcription_circuit_breaker' => [
            'enabled' => env('FLEX_FIELDS_TRANSCRIPTION_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('FLEX_FIELDS_TRANSCRIPTION_CB_THRESHOLD', 3),
            'open_seconds' => (int) env('FLEX_FIELDS_TRANSCRIPTION_CB_OPEN_SECONDS', 30),
            'cache_scope' => null,
            'probe_seconds' => 60,
        ],
        'pci' => [
            'never_store_pan' => env('FLEX_FIELDS_PCI_NEVER_STORE_PAN', true),
            'require_tokenization' => env('FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION', false),
        ],
        'tenant' => [
            'disk' => env('FLEX_FIELDS_MEDIA_TENANT_DISK'),
            'directory_prefix' => env('FLEX_FIELDS_MEDIA_TENANT_DIRECTORY_PREFIX'),
            'auto_disk' => env('FLEX_FIELDS_MEDIA_TENANT_AUTO_DISK', false),
        ],
        'spatie' => [
            'prune_collections' => [],
        ],
        'directories' => [
            'temp_captures' => ['livewire-tmp'],
            'voice_notes' => ['voice-notes'],
            'uploads' => ['uploads'],
            'signatures' => ['signatures'],
        ],
        'retention' => [
            'schedule_enabled' => env('FLEX_FIELDS_RETENTION_SCHEDULE', true),
            'schedule' => env('FLEX_FIELDS_RETENTION_SCHEDULE_EXPRESSION', 'daily'),
            'uploads' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_UPLOADS', false),
                'days' => env('FLEX_FIELDS_RETENTION_UPLOADS_DAYS'),
            ],
            'voice_notes' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_VOICE_NOTES', false),
                'days' => (int) env('FLEX_FIELDS_RETENTION_VOICE_NOTES_DAYS', 365),
            ],
            'signatures' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_SIGNATURES', false),
                'days' => env('FLEX_FIELDS_RETENTION_SIGNATURES_DAYS'),
            ],
            'temp_captures' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_TEMP_CAPTURES', true),
                'days' => (int) env('FLEX_FIELDS_RETENTION_TEMP_CAPTURES_DAYS', 7),
            ],
        ],
    ],

];
