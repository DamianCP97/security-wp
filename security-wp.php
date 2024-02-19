<?php
/*
Plugin Name: Security WP
Description: Add some options to improve your security on Wordpress.
Version: 1.0.1
Author: Damián Caamaño
Author URI: https://www.linkedin.com/in/dami%C3%A1n-caama%C3%B1o-pazos-a543a71b3/
*/

//Agregar menú al dashboard de WordPress
function agregar_menu() {
    add_menu_page(
        'Configuration', //Título de la pestaña
        'Security', //Nombre que se muestra en la pestaña
        'manage_options', //Capacidad requerida para ver este menú
        'opciones-seguridad', //Slug único para identificarlo
        'renderizar_plugin', //Función a llamar cuando se carga esta página
        'dashicons-lock' // Icono del menú
    );
}

add_action('admin_menu', 'agregar_menu');

//Función que muestra el contenido de la página
function renderizar_plugin() { 
    // Verificar si se ha enviado el formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtener y guardar el valor del campo de texto en una opción de WordPress
        if (isset($_POST["url-acceso"])) {
            $texto = $_POST["url-acceso"];
            update_option('texto_url_acceso', $texto);
        }

        // Obtener y guardar el valor del campo de tiempo de desconexión en una opción de WordPress
        if (isset($_POST["tiempo-desconexion"])) {
            $tiempo_desconexion = $_POST["tiempo-desconexion"];
            update_option('tiempo_desconexion', $tiempo_desconexion);
        }

        // Verificar y guardar el estado del checkbox XML-RPC
        $xmlrpc_blocked = isset($_POST['xmlrpc']) && $_POST['xmlrpc'] === 'on';
        update_option('xmlrpc_blocked', $xmlrpc_blocked);

        // Verificar y guardar el estado del checkbox desconectar usuario
        $desconectar_usuario = isset($_POST['desconexion']) && $_POST['desconexion'] === 'on';
        update_option('desconectar_usuario', $desconectar_usuario);
    }

    // Obtener el valor actual de la opción guardada para el bloqueo de XML-RPC
    $xmlrpc_blocked = get_option('xmlrpc_blocked');

    // Obtener el valor actual de la opción guardada para la desconexión del usuario
    $desconectar_usuario = get_option('desconectar_usuario');
    
    // Obtener el valor actual de la opción guardada
    $texto = get_option('texto_url_acceso');
    $tiempo_desconexion = get_option('tiempo_desconexion'); ?>
    <head>
        <!-- Enlaza tu archivo CSS -->
        <link rel="stylesheet" type="text/css" href="<?php echo esc_url( plugins_url( './assets/styles.css', __FILE__ ) ); ?>">
    </head>

    <h1>Configuration</h1>

    <!-- Formulario para el cambio de URL de acceso a WordPress -->
    <form id="formulario" method="post">
        <h2>Change WordPress login URL</h2>
        <br>
        <label><?php echo esc_html( $_SERVER['HTTP_HOST'] ); ?>/</label>
        <input type="text" id="url-acceso" name="url-acceso" value="<?php echo esc_attr($texto); ?>">
        <br><br>
        <h2>Block XML-RPC</h2>
        <p>This setting will disable access to the WordPress "xmlrpc.php" file, which is responsible for the XML-RPC functionality in WordPress.</p>
        <input type="checkbox" id="xmlrpc" name="xmlrpc" <?php if($xmlrpc_blocked) echo 'checked'; ?>>Select this option to block access to XML-RPC</input>
        <br><br>
        <h2>User disconnection</h2>
        <br>
        <input type="checkbox" id="desconexion" name="desconexion" <?php if($desconectar_usuario) echo 'checked'; ?>>Select this option to activate user disconnection</input>
        <br><br>
        <label>Time (in seconds). The user will have to reconnect once this time has passed</label>
        <input type="number" id="tiempo-desconexion" name="tiempo-desconexion" value="<?php echo esc_attr($tiempo_desconexion); ?>">
        <br><br>
        <button type="submit">Save changes</button>
    </form>

    <script>
        // JavaScript para actualizar dinámicamente el valor del input después de enviar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('formulario').addEventListener('submit', function() {
                let textoActualizado = document.getElementById('url-acceso').value;
                localStorage.setItem('texto_url_acceso', textoActualizado);
            });

            let textoGuardado = localStorage.getItem('texto_url_acceso');
            if (textoGuardado !== null) {
                document.getElementById('url-acceso').value = textoGuardado;
            }
        });
        
        // JavaScript para actualizar dinámicamente el valor del input después de enviar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('formulario').addEventListener('submit', function() {
                let tiempoActualizado = document.getElementById('tiempo-desconexion').value;
                localStorage.setItem('tiempo_desconexion', tiempoActualizado);
            });

            let tiempoGuardado = localStorage.getItem('tiempo_desconexion');
            if (tiempoGuardado !== null) {
                document.getElementById('tiempo-desconexion').value = tiempoGuardado;
            }
        });
    </script>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") { ?>
        <p>Changes have been saved</p>
        <?php
    }
}

//Modificar la URL de acceso al panel de WordPress
function url_acceso() {
    // Recuperar el valor de la opción de WordPress
    $texto = get_option('texto_url_acceso');

    // Verifica si la URL solicitada coincide con la URL personalizada de acceso **QUITAR EL /WP-DAMIAN/ CUANDO SE VAYA A UTILIZAR EN PÁGINAS FINALES**
    if ( isset( $_SERVER['REQUEST_URI'] ) && '/' . $texto === $_SERVER['REQUEST_URI'] ) {
        // Requiere el archivo de inicio de sesión de WordPress
        require_once ABSPATH . 'wp-login.php';
        // Sal del script para evitar redireccionamientos
        exit;
    }
}

add_action( 'template_redirect', 'url_acceso' );

//Función que anula wp-admin y wp-login.php y redirige a la home
function evitar_entrada() {
    // Recuperar el valor de la opción de WordPress
    $texto = get_option('texto_url_acceso');

    if (strpos($_SERVER['REQUEST_URI'], $texto) === false) {
        wp_safe_redirect(home_url(), 302);
        exit();
    }
}

add_action('login_head', 'evitar_entrada');

//Función para bloquear el acceso a xmlrpc.php
function bloquear_xmlrpc() {
    // Verifica si la opción xmlrpc_blocked está establecida como verdadera
    $xmlrpc_blocked = get_option('xmlrpc_blocked');
    
    // Verifica si la URL solicitada coincide con xmlrpc.php **QUITAR EL /WP-DAMIAN/ CUANDO SE VAYA A USAR EN OTRAS PÁGINAS**
    if ($xmlrpc_blocked && strpos($_SERVER['REQUEST_URI'], '/xmlrpc.php') !== false) {
        // Envía el encabezado de respuesta 403 - Acceso prohibido
        http_response_code(403);
        // Muestra un mensaje de error
        echo '<h1>Error 403 - Access forbidden</h1>';
        echo '<p>Sorry, you do not have permission to access this page.</p>';
        // Detiene la ejecución del script
        exit;
    }
}

// Agrega el gancho para ejecutar la función bloquear_xmlrpc en el inicio de WordPress
add_action('init', 'bloquear_xmlrpc');

//Función para expulsar al usuario cuando pasen X segundos
function expulsar_usuario($expiry, $user_id, $remember) {
    // Verifica si la opción desconectar_usuario está establecida como verdadera
    $desconectar_usuario = get_option('desconectar_usuario');

    //Recupera el valor del input del tiempo de desconexión
    $tiempo_desconexion = get_option('tiempo_desconexion');

    if ($desconectar_usuario) {
        //Modifica el tiempo de la cookie de sesión de tal modo que cuando pasen estos segundos te expulsa de WordPress
        return $tiempo_desconexion;
    } else {
        // Si la opción no está marcada, devuelve el valor original de $expiry para mantener la sesión activa normalmente
        return $expiry;
    }
}

add_filter('auth_cookie_expiration', 'expulsar_usuario', 10, 3);