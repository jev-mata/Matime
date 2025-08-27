<!DOCTYPE html>
<html>

<head>
    <title>Maintenance Mode</title>
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            text-align: center;
            padding: 50px;
            color: rgba(230, 230, 230, 1);
            background-color: rgba(39, 39, 39, 1);
        }

        h1 {
            font-size: 50px;
        }

        body {
            font: 20px Helvetica, sans-serif;
        }

        article {
            display: block;
            text-align: left;
            max-width: 650px;
            margin: 0 auto;
        }

        a {
            color: #dc8100;
            text-decoration: none;
        }

        a:hover {
            color: #333;
            text-decoration: none;
        }
    </style>

    {{-- @vite(array_filter(\Nwidart\Modules\Module::getAssets(), fn($asset) => $asset !== 'resources/css/filament/admin/theme.css')) --}}
    {{-- @vite(['resources/js/app.ts', 'resources/css/app.css']) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}"></script>

</head>

<body class="bg-[#0C101E]">
    <div class="min-w-[300px] w-[60%] mx-auto">
        <img src="{{ asset('images/maintenance.png') }}" class="min-w-[100px] w-[25%] mx-auto p-3" />
        <h1 class="text-center text-5xl font-bold py-5">We'll be back soon!</h1>
        <div class="text-center px-2">
            <p class="text-2xl text-center px-4 font-light">Sorry for the inconvenience but we're performing some
                maintenance at the moment.
                We'll be back online shortly!</p>
            {{-- <p>&mdash; The Team</p> --}}
        </div>
        <img src="{{ asset('images/logo.png') }}" class="min-w-[100px] w-[45%] mx-auto " />
    </div>
</body>

</html>
