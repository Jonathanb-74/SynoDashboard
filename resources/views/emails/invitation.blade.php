<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; margin: 0; padding: 40px 0; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 40px; }
        .brand { font-size: 1.2rem; font-weight: 700; color: #0d6efd; margin-bottom: 24px; }
        h2 { font-size: 1.1rem; margin-top: 0; }
        p { color: #444; line-height: 1.6; }
        .role { display: inline-block; background: #e9ecef; border-radius: 4px; padding: 2px 8px; font-size: .85rem; }
        .btn { display: inline-block; margin: 24px 0; background: #0d6efd; color: #fff; text-decoration: none;
               padding: 12px 28px; border-radius: 6px; font-weight: 600; }
        .expiry { color: #888; font-size: .85rem; }
        .footer { margin-top: 32px; color: #aaa; font-size: .8rem; border-top: 1px solid #eee; padding-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <div class="brand">SynoManager</div>
    <h2>Vous avez été invité(e)</h2>
    <p>
        @if($invitation->invitedBy)
            <strong>{{ $invitation->invitedBy->name }}</strong> vous invite à rejoindre SynoManager
        @else
            Vous avez été invité(e) à rejoindre SynoManager
        @endif
        en tant que <span class="role">{{ $invitation->role === 'admin' ? 'Administrateur' : 'Utilisateur' }}</span>.
    </p>
    <p>Cliquez sur le bouton ci-dessous pour créer votre compte :</p>
    <a href="{{ $acceptUrl }}" class="btn">Créer mon compte</a>
    <p class="expiry">Ce lien expire le {{ $invitation->expires_at->format('d/m/Y à H:i') }}.</p>
    <div class="footer">
        Si vous n'attendiez pas cette invitation, vous pouvez ignorer cet email.<br>
        Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
        <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
    </div>
</div>
</body>
</html>
