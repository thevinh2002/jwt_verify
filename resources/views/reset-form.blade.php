<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 40px 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 30px; }
        h1 { text-align: center; color: #2563eb; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
        input { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; transition: border-color 0.2s; box-sizing: border-box; }
        input:focus { outline: none; border-color: #2563eb; }
        .token-display { background: #f0f9ff; border: 2px solid #0ea5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .token-label { font-weight: 600; color: #0c4a6e; margin-bottom: 8px; display: block; }
        .token-value { font-family: monospace; font-size: 14px; color: #0c4a6e; word-break: break-all; background: white; padding: 10px; border-radius: 4px; border: 1px solid #e0f2fe; }
        .copy-btn { background: #0ea5e9; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-top: 8px; transition: background 0.2s; }
        .copy-btn:hover { background: #0284c7; }
        .copy-btn.copied { background: #16a34a; }
        .submit-btn { width: 100%; background: #2563eb; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .submit-btn:hover { background: #1d4ed8; }
        .submit-btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .error { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; }
        .success { background: #f0fdf4; color: #16a34a; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #bbf7d0; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #2563eb; text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        
        @if($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif
        
        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif
        
        <form action="{{ route('password.reset') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ request('email') }}" required>
            </div>
            
            <div class="token-display">
                <span class="token-label">Reset Token:</span>
                <div id="tokenValue" class="token-value">{{ request('token') }}</div>
                <button type="button" class="copy-btn" onclick="copyToken(this)">Copy Token</button>
            </div>
            
            <input type="hidden" name="token" value="{{ request('token') }}">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
            </div>
            
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>
        
        <div class="back-link">
            <a href="{{ url('/auth-demo') }}">← Back to Auth Demo</a>
        </div>
    </div>
    
    <script>
        function copyToken(btn) {
            const tokenText = document.getElementById('tokenValue').innerText;
            
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
                alert('Failed to copy token. Please copy manually.');
            });
        }
        
        // Auto-focus first empty field
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            if (!emailField.value) {
                emailField.focus();
            } else {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>
