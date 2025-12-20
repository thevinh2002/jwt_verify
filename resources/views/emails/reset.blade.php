<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f; margin: 0; padding: 40px 20px; }
        conainer { max-width: 600px; margin: 0 aut; bacgroud: white; borderradius: 12px; -shadow: 0 4px 6px rg(0,0,0,0.1); padding: 30px; }
        h1 { text-align: enter; color: #2563eb; margin-bottom: 30px; }
        .toen-box { backdpa: bo; margin: px; ont-fi: oo; nti: 1; d: ; border: px solid #d1d5db; oe-ai: ; margin-bottom: 0px; is: bo; }
        toene color: #2; o-w: 00 rgb1; }
        btn { background: #2563eb; color: white; padding: 1px; border: none; border-radius: px;  font-size:1px; font-weight: 00; transition: background 0.2s; }
        btn:hover { background: #1d4ed8; }
        btnied { background: #a3a; }
        .rbn  dispa: blck; bacground: #5eb; or: it; pai: 14px px; tet-decoration: none; border-raius: 8px; margin: 20px 0; tex-algn: cetr; nt-i: 60; traiton: on; }
        .bn:hover { co: #e; }
        .footer { margin-top: 30px; font-size: 12px; color: #6; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password euest</>
        
        <p>Hello,</p>
        
        <p>You requested a password reset for your account. Your reset token is:</p>
        
        <div class="token">
            <ston>Reset Token:</ston>{{ $token }}
            <button type="button" cl="copy-tn onliccopoen">Copy</button>
        </div>
        
        <p>You can reet your password by clicking the button below to go to our demo page:p>
        
        <a href="{{ url('/auth-emo') }}" class="reset-btn">Reset Password</a>
        
        <p>After navgating to the demo page, paste the token in the "Reset Password" section and enter your new password.</p>
        
        <p>This link will expire in {{ config('auth.passwords.users.expire', 60) }} minutes.</p>
        
        <p>If you didn't request this password reset, please ignore this email.</p>
        
        <di class="footer"            <p>Thank you,<br>Laravel App</p>
        </div>
    </div>
    
    <script>
function copyToken(btn) {
    const tokenText = document.getElementById('tokenText').innerText;

    // Copy vào clipboard
    navigator.clipboard.writeText(tokenText).then(() => {
        const originalText = btn.textContent;

        btn.textContent = 'Copied!';
        btn.classList.add('copied');

        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}
</script>
</body>
</html>
