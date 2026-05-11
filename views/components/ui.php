<?php

if (!function_exists('ui_e')) {
    function ui_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ui_asset')) {
    // Reusable asset helper keeps CSS/JS paths consistent with the XAMPP base URL.
    function ui_asset($path)
    {
        return BASE_URL . '/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('ui_icon')) {
    // Reusable Bootstrap Icon helper avoids hand-written SVGs in view files.
    function ui_icon($name, $class = '')
    {
        $classes = trim('bi bi-' . $name . ' ' . $class);
        return '<i class="' . ui_e($classes) . '" aria-hidden="true"></i>';
    }
}

if (!function_exists('ui_status_label')) {
    function ui_status_label($state)
    {
        $labels = [
            'DRAFT' => 'Draft',
            'AWAITING_CLIENT' => 'Awaiting Client',
            'CLIENT_SIGNED' => 'Client Signed',
            'AWAITING_COMPANY' => 'Company Action',
            'FULLY_SIGNED' => 'Fully Signed',
        ];

        return $labels[strtoupper((string) $state)] ?? 'Draft';
    }
}

if (!function_exists('ui_status_class')) {
    function ui_status_class($state)
    {
        $classes = [
            'DRAFT' => 'draft',
            'AWAITING_CLIENT' => 'client',
            'CLIENT_SIGNED' => 'client',
            'AWAITING_COMPANY' => 'company',
            'FULLY_SIGNED' => 'final',
        ];

        return $classes[strtoupper((string) $state)] ?? 'draft';
    }
}
