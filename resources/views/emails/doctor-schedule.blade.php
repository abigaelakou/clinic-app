<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', Arial, sans-serif; background: #F3F5F2; margin: 0; padding: 0; color: #241A15; }
    .wrap { max-width: 480px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #E7E3DD; }
    .head { background: #8B6EC7; padding: 26px 28px; }
    .head h1 { color: #fff; font-size: 18px; margin: 0; }
    .head p { color: #EEE7F9; font-size: 12px; margin: 4px 0 0; }
    .body { padding: 22px 28px; }
    .body p { font-size: 14px; line-height: 1.6; }
    .row { display: table; width: 100%; border-top: 1px solid #E7E3DD; padding: 10px 0; }
    .row .time { display: table-cell; width: 60px; font-weight: bold; font-size: 13px; vertical-align: top; }
    .row .info { display: table-cell; font-size: 13px; vertical-align: top; }
    .row .info small { color: #8C7C73; display: block; margin-top: 2px; }
    .foot { padding: 16px 28px; font-size: 11px; color: #A79A92; text-align: center; }
</style>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <h1>Ton planning — {{ $day->translatedFormat('l j F Y') }}</h1>
            <p>CLINIQUE FAME</p>
        </div>
        <div class="body">
            <p>Bonjour {{ $doctor->user->name ?? '' }},</p>
            <p>{{ $appointments->count() }} rendez-vous prévu(s) :</p>

            @foreach($appointments as $appt)
                <div class="row">
                    <div class="time">{{ $appt->scheduled_at->format('H:i') }}</div>
                    <div class="info">
                        <b>{{ $appt->patient->first_name ?? '' }} {{ $appt->patient->last_name ?? '' }}</b>
                        <small>{{ $appt->reason }} @if($appt->status === 'pending') · en attente de confirmation @endif</small>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="foot">CLINIQUE FAME</div>
    </div>
</body>
</html>
