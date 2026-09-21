<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', Arial, sans-serif; background: #F3F5F2; margin: 0; padding: 0; color: #241A15; }
    .wrap { max-width: 480px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #E7E3DD; }
    .head { background: #241A15; padding: 26px 28px; }
    .head h1 { color: #fff; font-size: 19px; margin: 0; }
    .head p { color: #C7BEB4; font-size: 12px; margin: 4px 0 0; }
    .body { padding: 26px 28px; }
    .body p { font-size: 14px; line-height: 1.6; color: #241A15; }
    .cred-box { background: #F3F5F2; border-radius: 8px; padding: 16px 18px; margin: 18px 0; }
    .cred-row { font-size: 13.5px; margin-bottom: 6px; }
    .cred-label { color: #8C7C73; }
    .cred-value { font-weight: 700; color: #241A15; }
    .btn { display: inline-block; background: #C0410C; color: #fff !important; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-size: 13.5px; font-weight: 600; margin-top: 8px; }
    .foot { padding: 16px 28px; font-size: 11px; color: #A79A92; text-align: center; }
</style>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <h1>CLINIQUE FAME</h1>
            <p>Accès à l'application de gestion</p>
        </div>
        <div class="body">
            <p>Bonjour {{ $user->name }},</p>
            <p>Un compte vient d'être créé pour toi sur l'application de la clinique. Voici tes identifiants de connexion :</p>
            <div class="cred-box">
                <div class="cred-row"><span class="cred-label">E-mail :</span> <span class="cred-value">{{ $user->email }}</span></div>
                <div class="cred-row"><span class="cred-label">Mot de passe :</span> <span class="cred-value">{{ $plainPassword }}</span></div>
                <div class="cred-row"><span class="cred-label">Rôle :</span> <span class="cred-value">{{ ucfirst($user->role) }}</span></div>
            </div>
            <p>Pense à changer ton mot de passe dès ta première connexion, depuis "Mon profil".</p>
            <a href="{{ url('/login') }}" class="btn">Se connecter</a>
        </div>
        <div class="foot">CLINIQUE FAME · Cet e-mail contient des informations confidentielles, ne le transfère pas.</div>
    </div>
</body>
</html>
