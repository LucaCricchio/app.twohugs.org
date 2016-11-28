<html>
    <body>
        <p>Benvenuto {{ $user->first_name }} {{ $user->last_name }}</p>

        <p>Clicca sul seguente link per attivare il tuo account:</p>
        <p>
            <a href="{{ route('auth.activate', ['activate' => $user->activation_code]) }}">
                {{ route('auth.activate', ['activate' => $user->activation_code]) }}
            </a>
        </p>
    </body>
</html>
