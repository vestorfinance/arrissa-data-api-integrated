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
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background-color: var(--accent);">
                    <svg style="width: 38px; height: 38px; fill: #fff;" viewBox="0 0 7000 7000" xmlns="http://www.w3.org/2000/svg">
                        <g>
                            <path d="M3534.57 2921.26l509.33 278.51 0 600.85 -543.85 297.38 -543.84 -297.38 0 -600.85 543.84 -297.38 34.51 18.87zm166.69 255.62l-201.2 -110.02 -399.01 218.18 0 430.3 399.01 218.19 399.01 -218.19 0 -430.3 -197.81 -108.16z"></path>
                            <path d="M3206.76 1423.91l672.75 0 0 1366.1 -745.17 0 0 -1366.1 72.42 0zm527.92 144.83l-455.5 0 0 1076.43 455.5 0 0 -1076.43z"></path>
                            <polygon points="3436.12,1496.03 3436.12,899.79 3580.96,899.79 3580.96,1496.03"></polygon>
                            <polygon points="3432.32,3004.16 3432.32,2675.89 3577.15,2675.89 3577.15,3004.16"></polygon>
                            <path d="M3203.34 5576.08l672.75 0 0 -1366.09 -745.17 0 0 1366.09 72.42 0zm527.92 -144.83l-455.5 0 0 -1076.43 455.5 0 0 -1076.43z"></path>
                            <polygon points="3432.7,5503.96 3432.7,6100.2 3577.53,6100.2 3577.53,5503.96"></polygon>
                            <polygon points="3428.89,3986.69 3428.89,4314.95 3573.73,4314.95 3573.73,3986.69"></polygon>
                            <path d="M5172.55 4811.44l336.37 -582.62 -1183.07 -683.05 -372.59 645.33 1183.07 683.05 36.21 -62.72zm138.53 -529.61l-227.75 394.48 -932.21 -538.21 227.75 -394.48 932.21 538.21z"></path>
                            <polygon points="5224.77,4576.75 5741.13,4874.87 5813.54,4749.44 5297.19,4451.32"></polygon>
                            <polygon points="3908.87,3821.41 4193.16,3985.54 4265.57,3860.11 3981.29,3695.98"></polygon>
                            <path d="M5479.29 2714.84l-336.37 -582.62 -1183.07 683.05 372.58 645.33 1183.07 -683.05 -36.21 -62.71zm-389.39 -384.77l227.75 394.47 -932.21 538.21 -227.75 -394.47 932.21 -538.21z"></path>
                            <polygon points="5302.15,2552.27 5818.51,2254.15 5746.09,2128.72 5229.73,2426.84"></polygon>
                            <polygon points="3990.05,3314.2 4274.34,3150.07 4201.92,3024.64 3917.63,3188.77"></polygon>
                            <path d="M1829.86 4820.48l-336.38 -582.62 1183.07 -683.05 372.59 645.33 -1183.07 683.05 -36.21 -62.72zm-138.53 -529.61l227.75 394.47 932.21 -538.21 -227.75 -394.47 -932.21 538.21z"></path>
                            <polygon points="1777.64,4585.79 1261.28,4883.91 1188.87,4758.48 1705.22,4460.36"></polygon>
                            <polygon points="3093.54,3830.44 2809.25,3994.57 2736.83,3869.15 3021.12,3705.01"></polygon>
                            <path d="M1520.7 2723.73l336.38 -582.62 1183.07 683.05 -372.58 645.33 -1183.07 -683.05 36.21 -62.72zm389.39 -384.77l-227.75 394.47 932.21 538.22 227.75 -394.48 -932.21 -538.21z"></path>
                            <polygon points="1697.84,2561.16 1181.48,2263.04 1253.9,2137.61 1770.26,2435.73"></polygon>
                            <polygon points="3009.94,3323.09 2725.65,3158.96 2798.07,3033.53 3082.35,3197.66"></polygon>
                        </g>
                    </svg>
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
