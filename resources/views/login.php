<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Arrissa Data API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ffffff',
                        dark: {
                            100: '#0a0a0a',
                            200: '#050505',
                            300: '#000000',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --accent: #4f46e5;
            --accent-hover: #6366f1;
            --border: #3a3a3a;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --card-bg: #1f1f1f;
            --input-bg: #262626;
            --input-border: #404040;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            transition: background-color 0.3s, color 0.3s;
            font-size: 16px;
        }
        
        body.light-theme {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border: #e5e7eb;
            --card-bg: #ffffff;
            --input-bg: #f9fafb;
            --input-border: #d1d5db;
        }

        .login-btn {
            background-color: var(--text-primary);
            color: var(--bg-primary);
            transition: all 0.2s;
        }

        .login-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .input-field {
            background-color: var(--input-bg);
            color: var(--text-primary);
            border: 1px solid var(--input-border);
            transition: all 0.2s;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--text-primary);
        }

        .theme-toggle {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
            position: fixed;
            top: 20px;
            right: 20px;
        }

        .theme-toggle:hover {
            background-color: var(--input-bg);
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        /* Global pill styles */
        button, .btn, input[type="submit"] {
            border-radius: 9999px !important;
        }
        input:not([type="checkbox"]):not([type="radio"]) {
            border-radius: 9999px !important;
        }
    </style>
</head>
<body style="background-color: var(--bg-primary);">
    <!-- Theme Toggle -->
    <div class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
        <i data-feather="moon" id="theme-icon" style="width: 20px; height: 20px; color: var(--text-secondary);"></i>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background-color: var(--text-primary);">
                    <span class="font-bold text-2xl" style="color: var(--bg-primary);">A</span>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight mb-2" style="color: var(--text-primary);">Welcome Back</h1>
                <p class="text-sm" style="color: var(--text-secondary);">Sign in to access Arrissa Data API</p>
            </div>

            <!-- Login Form -->
            <div class="rounded-2xl p-8" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message rounded-lg p-3 mb-6 text-sm">
                        <i data-feather="alert-circle" style="width: 16px; height: 16px; display: inline; margin-right: 8px;"></i>
                        <?php 
                            if ($_GET['error'] == 'invalid') {
                                echo 'Invalid username or password';
                            } elseif ($_GET['error'] == 'required') {
                                echo 'Please fill in all fields';
                            } else {
                                echo 'An error occurred. Please try again.';
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <form action="/auth/login" method="POST">
                    <!-- Username -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-primary);">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            class="input-field w-full rounded-lg px-4 py-3 text-sm"
                            placeholder="Enter your username"
                            required
                        >
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2" style="color: var(--text-primary);">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                class="input-field w-full rounded-lg px-4 py-3 text-sm pr-12"
                                placeholder="Enter your password"
                                required
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-3"
                                style="color: var(--text-secondary);"
                            >
                                <i data-feather="eye" id="eye-icon" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="login-btn w-full rounded-lg py-3 text-sm font-semibold"
                    >
                        Sign In
                    </button>
                </form>

                <!-- Info -->
                <div class="mt-6 text-center">
                    <p class="text-xs" style="color: var(--text-secondary);">
                        Default credentials: <span style="color: var(--text-primary); font-medium;">admin / admin</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-feather', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }
        
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.classList.contains('light-theme')) {
                body.classList.remove('light-theme');
                localStorage.setItem('theme', 'dark');
                themeIcon.setAttribute('data-feather', 'moon');
                feather.replace();
            } else {
                body.classList.add('light-theme');
                localStorage.setItem('theme', 'light');
                themeIcon.setAttribute('data-feather', 'sun');
                feather.replace();
            }
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeIcon.setAttribute('data-feather', 'sun');
                feather.replace();
            }
        });
    </script>
</body>
</html>
