<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Laravel JWT Auth</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;margin:0;background:#0b1220;color:#e5e7eb}
        .container{max-width:500px;margin:50px auto;padding:24px}
        .card{background:#111a2e;border:1px solid #1f2a44;border-radius:12px;padding:24px}
        label{display:block;font-size:12px;color:#a5b4fc;margin-top:10px}
        input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #273553;background:#0b1220;color:#e5e7eb}
        button{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #334155;background:#2563eb;color:white;cursor:pointer}
        button:disabled{opacity:.5;cursor:not-allowed}
        .error{color:#ef4444;font-size:12px;margin-top:5px}
        .success{color:#10b981;font-size:12px;margin-top:5px}
        pre{white-space:pre-wrap;word-break:break-word;background:#0b1220;border:1px solid #273553;border-radius:12px;padding:12px;margin:10px 0;color:#d1d5db}
        .muted{color:#94a3b8;font-size:12px;text-align:center;margin-top:20px}
        a{color:#93c5fd}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2 style="margin-top:0;margin-bottom:20px">Reset Password</h2>
        
        <form id="resetForm">
            <input type="hidden" id="token" value="{{ $token }}">
            
            <label>Email</label>
            <input type="email" id="email" placeholder="your@example.com" required>
            
            <label>New Password</label>
            <input type="password" id="password" placeholder="min 8 characters" required>
            
            <label>Confirm Password</label>
            <input type="password" id="password_confirmation" placeholder="confirm new password" required>
            
            <div id="message"></div>
            
            <button type="submit" id="btnReset">Reset Password</button>
        </form>
        
        <div class="muted">
            <a href="/auth-demo">← Back to Auth Demo</a>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('resetForm');
    const message = document.getElementById('message');
    const token = document.getElementById('token').value;
    
    // Pre-fill email from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const email = urlParams.get('email');
    if (email) {
        document.getElementById('email').value = email;
    }
    
    function showMessage(text, isError = false) {
        message.innerHTML = `<div class="${isError ? 'error' : 'success'}">${text}</div>`;
    }
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;
        
        if (password !== passwordConfirmation) {
            showMessage('Passwords do not match', true);
            return;
        }
        
        if (password.length < 8) {
            showMessage('Password must be at least 8 characters', true);
            return;
        }
        
        document.getElementById('btnReset').disabled = true;
        showMessage('Resetting password...');
        
        try {
            const response = await fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    email: email,
                    password: password,
                    password_confirmation: passwordConfirmation
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('Password reset successfully! Redirecting to login...');
                setTimeout(() => {
                    window.location.href = '/auth-demo';
                }, 2000);
            } else {
                showMessage(data.message || 'Failed to reset password', true);
            }
        } catch (error) {
            showMessage('Network error. Please try again.', true);
        } finally {
            document.getElementById('btnReset').disabled = false;
        }
    });
</script>
</body>
</html>
