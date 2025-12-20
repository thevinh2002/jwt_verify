<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JWT Auth Demo</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;margin:0;background:#0b1220;color:#e5e7eb}
        .container{max-width:1100px;margin:0 auto;padding:24px}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .card{background:#111a2e;border:1px solid #1f2a44;border-radius:12px;padding:16px}
        label{display:block;font-size:12px;color:#a5b4fc;margin-top:10px}
        input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #273553;background:#0b1220;color:#e5e7eb}
        button{padding:10px 12px;border-radius:10px;border:1px solid #334155;background:#2563eb;color:white;cursor:pointer}
        button.secondary{background:#0b1220;color:#e5e7eb}
        button.danger{background:#dc2626;border-color:#ef4444}
        button:disabled{opacity:.5;cursor:not-allowed}
        .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
        .row>*{flex:1}
        pre{white-space:pre-wrap;word-break:break-word;background:#0b1220;border:1px solid #273553;border-radius:12px;padding:12px;margin:0;color:#d1d5db;min-height:220px}
        .muted{color:#94a3b8;font-size:12px;line-height:1.4}
        .pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#0b1220;border:1px solid #273553;color:#c7d2fe;font-size:12px}
        a{color:#93c5fd}
        @media (max-width: 900px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="container">
    <div class="card" style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-size:18px;font-weight:700">Laravel JWT Auth Demo (tymon/jwt-auth)</div>
                <div class="muted">API endpoints: <span class="pill">/api/auth/register</span> <span class="pill">/api/auth/login</span> <span class="pill">/api/auth/me</span> <span class="pill">/api/auth/logout</span> <span class="pill">/api/auth/refresh</span></div>
            </div>
            <div>
                <span class="pill" id="tokenState">No token</span>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div style="font-weight:700;margin-bottom:6px">Register</div>

            <label>Name</label>
            <input id="reg_name" placeholder="Your name" />

            <label>Email</label>
            <input id="reg_email" placeholder="you@example.com" />

            <label>Password</label>
            <input id="reg_password" type="password" placeholder="min 8 characters" />

            <div class="row">
                <button id="btnRegister">Register</button>
            </div>
        </div>

        <div class="card">
            <div style="font-weight:700;margin-bottom:6px">Login</div>

            <label>Email</label>
            <input id="login_email" placeholder="you@example.com" />

            <label>Password</label>
            <input id="login_password" type="password" placeholder="your password" />

            <div class="row">
                <button id="btnLogin">Login</button>
            </div>

            <div class="row">
                <button class="danger" id="btnLogout" disabled>Logout</button>
                <button class="secondary" id="btnClear">Clear token</button>
            </div>

            <div style="margin-top:12px">
                <button class="secondary" id="btnForgotPassword">Forgot Password</button>
            </div>
        </div>

        <div class="card" style="display:none" id="forgotPasswordCard">
            <div style="font-weight:700;margin-bottom:6px">Forgot Password</div>

            <label>Email</label>
            <input id="forgot_email" placeholder="you@example.com" />

            <div class="row">
                <button id="btnSendReset">Send Reset Link</button>
                <button class="secondary" id="btnBackToLogin">Back to Login</button>
            </div>
        </div>

        <div class="card">
            <div style="font-weight:700;margin-bottom:6px">Actions</div>
            <button id="btnMe" disabled>GET /me</button>
            <button id="btnRefresh" disabled>POST /refresh</button>
            <button id="btnChangePassword" disabled>Change Password</button>
            <button id="btnResend" disabled>POST /email/verification-notification</button>
            <button id="btnOpenVerify" style="display:none" disabled>Open verification link</button>
        </div>

        <div class="card" style="display:none" id="changePasswordCard">
            <div style="font-weight:700;margin-bottom:6px">Change Password</div>

            <label>Current Password</label>
            <input id="current_password" type="password" placeholder="enter current password" />

            <label>New Password</label>
            <input id="new_password" type="password" placeholder="min 8 characters" />

            <label>Confirm New Password</label>
            <input id="new_password_confirmation" type="password" placeholder="confirm new password" />

            <div class="row">
                <button id="btnUpdatePassword">Update Password</button>
                <button class="secondary" id="btnCancelChange">Cancel</button>
            </div>
        </div>

        <div class="card" style="grid-column:1/-1">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
                <div style="font-weight:700">Response</div>
                <div class="muted">Base URL: <span class="pill" id="baseUrl"></span></div>
            </div>
            <pre id="out"></pre>
        </div>
    </div>
</div>

<script>
    const out = document.getElementById('out');
    const tokenState = document.getElementById('tokenState');
    const baseUrlEl = document.getElementById('baseUrl');

    const tokenKey = 'auth_demo_token';
    const verifyKey = 'auth_demo_verification_url';

    const apiBase = window.location.origin;
    baseUrlEl.textContent = apiBase;

    function setOut(data) {
        out.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    function getToken() {
        return localStorage.getItem(tokenKey);
    }

    function setToken(token) {
        if (token) {
            localStorage.setItem(tokenKey, token);
        } else {
            localStorage.removeItem(tokenKey);
        }
        syncButtons();
    }

    function setVerificationUrl(url) {
        if (url) {
            localStorage.setItem(verifyKey, url);
        } else {
            localStorage.removeItem(verifyKey);
        }
        syncButtons();
    }

    function getVerificationUrl() {
        return localStorage.getItem(verifyKey);
    }

    function syncButtons() {
        const token = getToken();
        const vurl = getVerificationUrl();

        tokenState.textContent = token ? 'Token: saved' : 'No token';

        document.getElementById('btnMe').disabled = !token;
        document.getElementById('btnLogout').disabled = !token;
        document.getElementById('btnRefresh').disabled = !token;
        document.getElementById('btnChangePassword').disabled = !token;
        document.getElementById('btnResend').disabled = !token;

        const btnOpenVerify = document.getElementById('btnOpenVerify');
        if (vurl) {
            btnOpenVerify.style.display = 'inline-block';
            btnOpenVerify.disabled = false;
        } else {
            btnOpenVerify.style.display = 'none';
            btnOpenVerify.disabled = true;
        }
    }

    async function apiFetch(path, options = {}) {
        const token = getToken();

        const headers = {
            'Accept': 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
            ...(options.headers || {})
        };

        const res = await fetch(apiBase + path, {
            method: options.method || 'GET',
            headers,
            body: options.body ? JSON.stringify(options.body) : undefined,
        });

        let json;
        const contentType = res.headers.get('content-type');
        const responseText = await res.text();
        
        try {
            if (contentType && contentType.includes('application/json')) {
                json = JSON.parse(responseText);
            } else {
                throw new Error(responseText || 'Non-JSON response');
            }
        } catch (e) {
            throw new Error(responseText || 'Invalid JSON response');
        }

        return { status: res.status, json };
    }

    document.getElementById('btnRegister').addEventListener('click', async () => {
        try {
            const body = {
                name: document.getElementById('reg_name').value,
                email: document.getElementById('reg_email').value,
                password: document.getElementById('reg_password').value,
            };
            const { status, json } = await apiFetch('/api/auth/register', { method: 'POST', body });
            setOut({ status, ...json });

            const token = json?.data?.token;
            const vurl = json?.data?.verification_url;

            if (token) setToken(token);
            if (vurl) setVerificationUrl(vurl);
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnLogin').addEventListener('click', async () => {
        try {
            const body = {
                email: document.getElementById('login_email').value,
                password: document.getElementById('login_password').value,
            };
            const { status, json } = await apiFetch('/api/auth/login', { method: 'POST', body });
            setOut({ status, ...json });

            const token = json?.data?.token;
            if (token) setToken(token);
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnMe').addEventListener('click', async () => {
        try {
            const { status, json } = await apiFetch('/api/auth/me');
            setOut({ status, ...json });
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnLogout').addEventListener('click', async () => {
        try {
            const { status, json } = await apiFetch('/api/auth/logout', { method: 'POST' });
            setOut({ status, ...json });
            setToken(null);
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnRefresh').addEventListener('click', async () => {
        try {
            const { status, json } = await apiFetch('/api/auth/refresh', { method: 'POST' });
            setOut({ status, ...json });
            const token = json?.data?.token;
            if (token) setToken(token);
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnResend').addEventListener('click', async () => {
        try {
            const { status, json } = await apiFetch('/api/auth/email/verification-notification', { method: 'POST' });
            setOut({ status, ...json });
            const vurl = json?.data?.verification_url;
            if (vurl) setVerificationUrl(vurl);
        } catch (e) {
            setOut(String(e));
        }
    });

    document.getElementById('btnOpenVerify').addEventListener('click', () => {
        const vurl = getVerificationUrl();
        if (!vurl) return;
        window.open(vurl, '_blank');
    });

    document.getElementById('btnClear').addEventListener('click', () => {
        setToken(null);
        setVerificationUrl(null);
        setOut('Cleared token + verification URL');
    });

    // Forgot Password functionality
    document.getElementById('btnForgotPassword').addEventListener('click', () => {
        document.getElementById('forgotPasswordCard').style.display = 'block';
    });

    document.getElementById('btnBackToLogin').addEventListener('click', () => {
        document.getElementById('forgotPasswordCard').style.display = 'none';
    });

    document.getElementById('btnSendReset').addEventListener('click', async () => {
        try {
            const email = document.getElementById('forgot_email').value;
            if (!email) {
                setOut('Email is required');
                return;
            }

            const { status, json } = await apiFetch('/api/auth/forgot-password', { 
                method: 'POST', 
                body: { email } 
            });
            setOut({ status, ...json });

            if (json?.success) {
                document.getElementById('forgotPasswordCard').style.display = 'none';
                document.getElementById('forgot_email').value = '';
            }
        } catch (e) {
            setOut(String(e));
        }
    });

    // Change Password functionality
    document.getElementById('btnChangePassword').addEventListener('click', () => {
        document.getElementById('changePasswordCard').style.display = 'block';
    });

    document.getElementById('btnCancelChange').addEventListener('click', () => {
        document.getElementById('changePasswordCard').style.display = 'none';
        // Clear form
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value = '';
        document.getElementById('new_password_confirmation').value = '';
    });

    document.getElementById('btnUpdatePassword').addEventListener('click', async () => {
        try {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const newPasswordConfirmation = document.getElementById('new_password_confirmation').value;

            if (!currentPassword || !newPassword || !newPasswordConfirmation) {
                setOut('All fields are required');
                return;
            }

            if (newPassword !== newPasswordConfirmation) {
                setOut('New passwords do not match');
                return;
            }

            if (newPassword.length < 8) {
                setOut('New password must be at least 8 characters');
                return;
            }

            const { status, json } = await apiFetch('/api/auth/change-password', { 
                method: 'POST', 
                body: { 
                    current_password: currentPassword,
                    password: newPassword,
                    password_confirmation: newPasswordConfirmation
                } 
            });
            setOut({ status, ...json });

            if (json?.success) {
                document.getElementById('changePasswordCard').style.display = 'none';
                // Clear form
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('new_password_confirmation').value = '';
                
                // Handle auto-logout if password was changed
                if (json?.data?.auto_logout) {
                    setToken(null);
                    setVerificationUrl(null);
                    setOut('Password changed successfully! You have been logged out for security. Please login again with your new password.');
                }
            }
        } catch (e) {
            setOut(String(e));
        }
    });

    syncButtons();
</script>
</body>
</html>
