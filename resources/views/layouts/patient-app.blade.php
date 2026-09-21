<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon espace — CLINIQUE FAME</title>
    <link rel="stylesheet" href="/css/patient-portal.css?v=1">
    @livewireStyles
</head>
<body>
    {{ $slot }}

    <div x-data="{ show: false, message: '' }"
         x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3500)"
         x-show="show" x-cloak
         style="position:fixed; bottom:90px; left:50%; transform:translateX(-50%); background:var(--ink); color:#fff; padding:12px 20px; border-radius:10px; font-size:13px; z-index:200; box-shadow:var(--shadow);">
        <span x-text="message"></span>
    </div>

    @livewireScripts
</body>
</html>
