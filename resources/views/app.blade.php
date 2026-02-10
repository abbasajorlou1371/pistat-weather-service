<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>سرویس آب و هوا - {{ config('app.name', 'Laravel') }}</title>
        
        <!-- Persian Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        
        <!-- Persian Datepicker CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css" />
        
        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] min-h-screen">
        <div id="app"></div>
        
        <script>
            // Initialize theme on page load - must run before Vue mounts
            (function() {
                try {
                    const savedTheme = localStorage.getItem('theme');
                    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
                    
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                        document.body.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        document.body.classList.remove('dark');
                    }
                } catch (e) {
                    console.error('Theme initialization error:', e);
                }
            })();
        </script>
    </body>
</html>
