<?php
/*
Plugin Name: Security WP
Description: Add some options to improve your security on Wordpress.
Version: 1.0
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
    }
    
    // Obtener el valor actual de la opción guardada
    $texto = get_option('texto_url_acceso'); ?>
    <head>
        <!-- Enlaza tu archivo CSS -->
        <link rel="stylesheet" type="text/css" href="<?php echo plugins_url( './assets/styles.css', __FILE__ ); ?>">
    </head>

    <h1>Configuration</h1>

    <!-- Formulario para el cambio de URL de acceso a WordPress -->
    <form id="formulario" method="post">
        <h2>Change WordPress login URL</h2>
        <br>
        <label><?php echo $_SERVER['HTTP_HOST']; ?>/</label>
        <input type="text" id="url-acceso" name="url-acceso" value="<?php echo esc_attr($texto); ?>">
        <input type="hidden" id="admin" name="admin" value="admin"> <!-- Se crea un input vacío para poder usarlo con la condición if y así detectar solo este formulario -->
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
    </script>

    <!-- Formulario para activar o desactivar el bloqueo de xmlrpc.php -->
    <form id="xml-rpc" method="post">
        <h2>Block XML-RPC</h2>
        <br>
        <input type="checkbox" id="xmlrpc" name="xmlrpc">Hola</input>
        <br><br>
        <!-- Agrega un campo oculto con el id del formulario -->
        <input type="hidden" name="id_formulario" value="xml-rpc">
        <button type="submit">Save changes</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['admin'] == 'admin') { ?>
        <p>Changes have been saved</p>
        <?php
    }
}

//Modificar la URL de acceso al panel de WordPress
function url_acceso() {
    // Recuperar el valor de la opción de WordPress
    $texto = get_option('texto_url_acceso');

    // Verifica si la URL solicitada coincide con la URL personalizada de acceso **QUITAR EL /WP-DAMIAN/ CUANDO SE VAYA A UTILIZAR EN PÁGINAS FINALES**
    if ( isset( $_SERVER['REQUEST_URI'] ) && '/wp-damian/' . $texto === $_SERVER['REQUEST_URI'] ) {
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
    // Verifica si la URL solicitada coincide con xmlrpc.php **QUITAR EL /WP-DAMIAN/ CUANDO SE VAYA A USAR EN OTRAS PÁGINAS**
    if (strpos($_SERVER['REQUEST_URI'], '/wp-damian/xmlrpc.php') !== false) {
        // Envía el encabezado de respuesta 403 - Acceso prohibido
        http_response_code(403);
        // Muestra un mensaje de error
        echo '<h1>Error 403 - Acceso prohibido</h1>';
        echo '<p>Lo sentimos, no tienes permiso para acceder a esta página.</p>';
        // Detiene la ejecución del script
        exit;
    }
}

// Agrega el gancho para ejecutar la función bloquear_xmlrpc en el inicio de WordPress
add_action('init', 'bloquear_xmlrpc');