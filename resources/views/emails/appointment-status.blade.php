<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', Arial, sans-serif; background: #F3F5F2; margin: 0; padding: 0; color: #241A15; }
    .wrap { max-width: 480px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #E7E3DD; }
    .head { padding: 26px 28px; }
    .head.confirmed { background: #2F6F62; }
    .head.cancelled { background: #B3261E; }
    .head.rescheduled { background: #C98A1A; }
    .head h1 { color: #fff; font-size: 18px; margin: 0; }
    .body { padding: 26px 28px; }
    .body p { font-size: 14px; line-height: 1.6; }
    .box { background: #F3F5F2; border-radius: 8px; padding: 16px 18px; margin: 16px 0; font-size: 13.5px; }
    .box div { margin-bottom: 5px; }
    .foot { padding: 16px 28px; font-size: 11px; color: #A79A92; text-align: center; }
</style>
</head>
<body>
    <div class="wrap">
        <div class="head {{ $kind }}">
            <h1>
                @if($kind === 'confirmed') ✓ Rendez-vous confirmé
                @elseif($kind === 'cancelled') ✕ Rendez-vous annulé
                @else ↻ Rendez-vous reporté
                @endif
            </h1>
        </div>
        <div class="body">
            <p>Bonjour {{ $appointment->patient->first_name }},</p>
            <p>
                @if($kind === 'confirmed')
                    Ton rendez-vous est confirmé :
                @elseif($kind === 'cancelled')
                    Ton rendez-vous a été annulé{{ $appointment->cancellation_reason ? ' (' . $appointment->cancellation_reason . ')' : '' }}.
                @else
                    Ton rendez-vous a été reporté au nouveau créneau suivant :
                @endif
            </p>
            @if($kind !== 'cancelled')
                <div class="box">
                    <div><b>{{ $appointment->doctor->user->name ?? '' }}</b></div>
                    <div>{{ $appointment->scheduled_at->translatedFormat('l j F Y') }} à {{ $appointment->scheduled_at->format('H:i') }}</div>
                    <div>{{ $appointment->reason }}</div>
                </div>
            @endif
            <p>Tu peux consulter le détail depuis ton espace en ligne.</p>
        </div>
        <div class="foot">CLINIQUE FAME</div>
    </div>
</body>
</html>
