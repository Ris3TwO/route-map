<?php

/**
 * Plugin Name: Route Map
 * Plugin URI: http://oncoders.io/wordpress
 * Description: Inserta un mapa de Colombía a través de un shortcode de las rutas disponibles en la web.
 * Version: 1.0.7-beta
 * Author: OnCoders
 * Author URI: https://oncoders.io/
 * Requires at least: 4.2
 * Tested up to: 6.0
 *
 * Text Domain: route-map
 * Domain path: /languages/
 */

/**
 * Register a db version
 */

register_activation_hook(__FILE__, 'route_map_create_db');
function route_map_create_db()
{
    global $wpdb;
    $version = get_option('route_map_version', '1.0.7-beta');
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'route_map';

    $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        title varchar(255) NOT NULL,
        description varchar(255) NOT NULL,
        departments varchar(255) NOT NULL,
		create_at datetime DEFAULT NOW() NOT NULL,
		modified_at datetime DEFAULT NOW() NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function load_custom_wp_admin_style($hook)
{
    wp_enqueue_style(
        'custom_wp_admin_css',
        plugins_url('/css/admin-style.css', __FILE__)
    );
}
add_action('admin_enqueue_scripts', 'load_custom_wp_admin_style');

/**
 * Add admin menu
 */

add_action('admin_menu', 'oc_route_map_setup_menu');

function oc_route_map_setup_menu()
{
    add_menu_page('Listado de Rutas', 'Rutas', 'manage_options', 'route-map', 'oc_admin_map');
}

add_action('admin_menu', 'oc_route_map_add_submenu_page');

function oc_route_map_add_submenu_page()
{
    add_submenu_page(
        'route-map',
        'Añadir ruta',
        'Añadir ruta',
        'manage_options',
        'route-map-add',
        'oc_admin_map_add'
    );
    add_submenu_page(
        null,
        'Editar ruta',
        'Editar ruta',
        'manage_options',
        'route-map-edit',
        'oc_admin_map_edit'
    );
    add_submenu_page(
        null,
        'Eliminar ruta',
        'Eliminar ruta',
        'manage_options',
        'route-map-delete',
        'oc_admin_map_delete'
    );
}

/**
 * Add new route
 */
function oc_admin_map_add()
{

    global $wpdb;

    $route = $wpdb->prefix . 'route_map';
    $post = $wpdb->prefix . 'posts';

    $pages = $wpdb->get_results("SELECT * FROM $post WHERE post_type = 'page' && post_title LIKE '%Ruta%'");

    $json = file_get_contents(__DIR__ . '/data/colombia.json');
    $departmentList = json_decode($json);

    function cmp($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    usort($departmentList, "cmp");

    if (isset($_POST['submit'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $post_id = $_POST['post_id'];

        $lastElement = end($_POST['departments']);

        $departments = "";

        foreach ($_POST['departments'] as $value) {
            if ($value == $lastElement) {
                $departments .= $value;
                break;
            }
            $departments .= $value . ", ";
        }

        $wpdb->insert(
            $route,
            array(
                'post_id' => $post_id,
                'title' => $title,
                'description' => $description,
                'departments' => $departments,
            )
        );
    }

    ?>

    <div>
        <h1>Añadir ruta</h1>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="post_id">Ruta</label>
                        </th>
                        <td>
                            <select name="post_id" id="post_id" required>
                                <option value="">Seleccione una ruta</option>
                                <?php foreach ($pages as $result) { ?>
                                    <option value="<?php echo $result->ID; ?>">
                                        <?php echo $result->post_title; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="title">Título</label>
                        </th>
                        <td>
                            <input name="title" type="text" id="title" value="" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description">Descripción</label>
                        </th>
                        <td>
                            <textarea name="description" id="description" cols="30" rows="3" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="departments">Departamentos</label>
                        </th>
                        <td>
                            <select name="departments[]" id="departments" multiple required>
                                <option value="">Seleccione los departamentos</option>
                                <?php foreach ($departmentList as $result) { ?>
                                    <option value="<?php echo $result->id; ?>">
                                        <?php echo $result->name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>

                </tbody>
            </table>
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Añadir ruta">
            <button type="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=route-map'); ?>'"
                class="button button-secondary">Cancelar</button>
        </form>
    </div>

    <?php
}

/**
 * List routes
 */
function oc_admin_map()
{
    global $wpdb;
    $route = $wpdb->prefix . 'route_map';
    $post = $wpdb->prefix . 'posts';
    $version = get_option('route_map_version', '1.0.7-beta');
    $results = $wpdb->get_results("SELECT * FROM $route, $post WHERE $route.post_id = $post.ID");

    $json = file_get_contents(__DIR__ . '/data/colombia.json');
    $departmentList = json_decode($json);

    function cmp($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    usort($departmentList, "cmp");

    function findObjectById($id, $obj)
    {
        foreach ($obj as $item) {
            if ($item->id == $id) {
                return $item->name;
            }
        }

        return false;
    }

    if (empty($results)) {
        echo '<div class="notice notice-warning is-dismissible">
                <p>No hay rutas registradas.</p>
            </div>';
    } else {

        foreach ($results as $result) {
            $result->departmentNames = '';
            $res = explode(", ", $result->departments);

            foreach ($res as $value) {
                if ($value == end($res)) {
                    $result->departmentNames .= findObjectById($value, $departmentList);
                    break;
                }
                $result->departmentNames .= findObjectById($value, $departmentList) . ", ";
            }
        }
    }

    ?>
    <!-- Custom style for plugin -->
    <div class="wrap">
        <h1>Mapa de rutas</h1>
        <p>Version:
            <?php echo $version; ?>
        </p>
        <p>Shortcode:
            <?php echo '[route_map]'; ?>
        </p>

        <div class="table-header">
            <h2>Rutas</h2>

            <button class="page-title-action"
                onclick="window.location.href='<?php echo admin_url('admin.php?page=route-map-add'); ?>'">
                Añadir ruta
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th class="manage-column">ID</th>
                    <th class="manage-column">Ruta</th>
                    <th class="manage-column">Título</th>
                    <th class="manage-column">Descripción</th>
                    <th class="manage-column">Departamentos</th>
                    <th class="manage-column">Fecha de creación</th>
                    <th class="manage-column">Fecha de modificación</th>
                    <th class="manage-column">Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)) { ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay rutas registradas</td>
                    </tr>
                <?php } else { ?>
                    <?php
                    foreach ($results as $result) { ?>
                        <tr>
                            <td>
                                <?php echo $result->id; ?>
                            </td>
                            <td>
                                <?php echo $result->post_title; ?>
                            </td>
                            <td>
                                <?php echo $result->title; ?>
                            </td>
                            <td>
                                <?php echo $result->description; ?>
                            </td>
                            <td>
                                <?php echo $result->departmentNames; ?>
                            </td>
                            <td>
                                <?php
                                $date = date_create($result->create_at);

                                echo date_format($date, "d/m/Y");
                                ?>
                            </td>
                            <td>
                                <?php
                                $date = date_create($result->modified_at);
                                echo date_format($date, "d/m/Y");
                                ?>
                            </td>
                            <td>
                                <button class="button button-info"
                                    onclick="window.location.href='<?php echo admin_url('admin.php?page=route-map-edit&id=' . $result->id) ?>'">
                                    Editar
                                </button>
                                <button class="button button-danger"
                                    onclick="window.location.href='<?php echo admin_url('admin.php?page=route-map-delete&id=' . $result->id) ?>'">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Edit route
 */

function oc_admin_map_edit()
{
    global $wpdb;
    $route = $wpdb->prefix . 'route_map';
    $post = $wpdb->prefix . 'posts';
    $version = get_option('route_map_version', '1.0.7-beta');
    $id = $_GET['id'];
    $results = $wpdb->get_results("SELECT * FROM $route, $post WHERE $route.post_id = $post.ID AND $route.id = $id");

    $json = file_get_contents(__DIR__ . '/data/colombia.json');
    $departmentList = json_decode($json);

    function cmp($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    usort($departmentList, "cmp");

    function findObjectById($id, $obj)
    {
        foreach ($obj as $item) {
            if ($item->id == $id) {
                return $item->name;
            }
        }

        return false;
    }

    if (empty($results)) {
        echo '<div class="notice notice-warning is-dismissible">
                <p>No hay rutas registradas.</p>
            </div>';
    } else {

        foreach ($results as $result) {
            $result->departmentNames = '';
            $res = explode(", ", $result->departments);

            foreach ($res as $value) {
                if ($value == end($res)) {
                    $result->departmentNames .= findObjectById($value, $departmentList);
                    break;
                }
                $result->departmentNames .= findObjectById($value, $departmentList) . ", ";
            }
        }
    }

    if (isset($_POST['submit'])) {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $modified_at = date('Y-m-d H:i:s');

        $departments = "";

        foreach ($_POST['departments'] as $value) {
            if ($value == end($_POST['departments'])) {
                $departments .= $value;
                break;
            }
            $departments .= $value . ", ";
        }

        $wpdb->update(
            $route,
            array(
                'title' => $title,
                'description' => $description,
                'departments' => $departments,
                'modified_at' => $modified_at
            ),
            array('id' => $id)
        );

        echo '<div class="notice notice-success is-dismissible">
                <p>Ruta actualizada correctamente.</p>
            </div>';

        $results = $wpdb->get_results("SELECT * FROM $route, $post WHERE $route.post_id = $post.ID AND $route.id = $id");
    }


    ?>
    <!-- Custom style for plugin -->
    <div class="wrap">
        <h1>Mapa de rutas</h1>
        <p>Version:
            <?php echo $version; ?>
        </p>
        <p>Shortcode:
            <?php echo '[route_map]'; ?>
        </p>

        <div class="table-header">
            <h2>Editar ruta</h2>
        </div>

        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <input type="hidden" name="action" value="edit_route_map">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th class="manage-column">ID</th>
                        <th class="manage-column">Ruta</th>
                        <th class="manage-column">Título</th>
                        <th class="manage-column">Descripción</th>
                        <th class="manage-column">Departamentos</th>
                        <th class="manage-column">Fecha de creación</th>
                        <th class="manage-column">Fecha de modificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)) { ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay rutas registradas</td>
                        </tr>
                    <?php } else { ?>
                        <?php
                        foreach ($results as $result) { ?>
                            <tr>
                                <td>
                                    <?php echo $result->id; ?>
                                </td>
                                <td>
                                    <?php echo $result->post_title; ?>
                                </td>
                                <td>
                                    <input type="text" name="title" value="<?php echo $result->title; ?>">
                                </td>
                                <td>
                                    <input type="text" name="description" value="<?php echo $result->description; ?>">
                                </td>
                                <td>
                                    <select name="departments[]" id="departments" multiple>
                                        <?php
                                        foreach ($departmentList as $department) {
                                            if (in_array($department->id, explode(", ", $result->departments))) {
                                                echo '<option value="' . $department->id . '" selected>' . $department->name . '</option>';
                                            } else {
                                                echo '<option value="' . $department->id . '">' . $department->name . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <?php
                                    $date = date_create($result->create_at);

                                    echo date_format($date, "d/m/Y");
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $date = date_create($result->modified_at);
                                    echo date_format($date, "d/m/Y");
                                    ?>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
            <button name="submit" id="submit" class="button button-primary" type="submit">Guardar</button>
        </form>
    </div>
    <?php
}

/**
 * Remove route
 */

function oc_admin_map_delete()
{
    global $wpdb;
    $route = $wpdb->prefix . 'route_map';
    $id = $_GET['id'];

    $wpdb->delete(
        $route,
        array('id' => $id)
    );

    echo '<div class="notice notice-success is-dismissible">
            <p>Ruta eliminada correctamente.</p>
        </div>';

    ?>

    <p class="countdown">Se le redireccionará en
        <time>
            <strong id="seconds">5</strong> segundos
        </time>.
    </p>

    <script>
        var el = document.getElementById('seconds'),
            total = el.innerHTML,
            timeinterval = setInterval(function () {
                total = --total;
                el.textContent = total;
                if (total <= 0) {
                    clearInterval(timeinterval);
                    window.location.href = "<?php echo admin_url('admin.php?page=route-map'); ?>";
                }
            }, 1000);
    </script>
    <?php
}

/**
 * Add endpoint to get all routes
 */

add_action('rest_api_init', function () {
    register_rest_route(
        'route-map/v1',
        '/routes',
        array(
            'methods' => 'GET',
            'callback' => 'get_routes',
        )
    );
});

/**
 * Get all routes
 */

function get_routes()
{
    global $wpdb;
    $route = $wpdb->prefix . 'route_map';
    $post = $wpdb->prefix . 'posts';

    $json = file_get_contents(__DIR__ . '/data/colombia.json');
    $departmentList = json_decode($json);

    $results = $wpdb->get_results("SELECT title, description, departments FROM $route, $post WHERE $route.post_id = $post.ID");

    function cmp($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    usort($departmentList, "cmp");

    function findObjectById($id, $obj)
    {
        foreach ($obj as $item) {
            if ($item->id == $id) {
                return $item->name;
            }
        }

        return false;
    }

    foreach ($results as $result) {
        $result->departmentNames = '';
        $res = explode(", ", $result->departments);

        foreach ($res as $value) {
            if ($value == end($res)) {
                $result->departmentNames .= findObjectById($value, $departmentList);
                break;
            }
            $result->departmentNames .= findObjectById($value, $departmentList) . ", ";
        }
    }

    return $results;
}

/**
 * Check if update for the plugin exists
 */

add_action('admin_init', 'oc_check_update');

function oc_check_update()
{
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
    $plugin_slug = basename(dirname(__FILE__)) . '/' . basename(__FILE__);

    if (is_plugin_active($plugin_slug)) {
        $response = wp_remote_get('https://api.github.com/repos/Ris3TwO/route-map/releases/latest');
        if (!is_wp_error($response)) {
            $body = json_decode($response['body']);
            if ($body->message == 'Not Found') {
                return;
            }
            $latest_version = $body->tag_name;
            if (version_compare($plugin_version, $latest_version, '<')) {
                add_action('admin_notices', 'oc_update_notice');
            }
        }
    }
}


function oc_update_notice()
{
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
    $plugin_slug = basename(dirname(__FILE__)) . '/' . basename(__FILE__);

    $response = wp_remote_get('https://api.github.com/repos/Ris3TwO/route-map/releases/latest');
    if (!is_wp_error($response)) {
        $body = json_decode($response['body']);

        if ($body->message !== 'Not Found') {
            $latest_version = $body->tag_name;
            $download_url = $body->zipball_url;
        }

    }

    echo '<div class="notice notice-warning is-dismissible">
            <p>Hay una nueva versión de <strong>Route Map</strong> disponible. <a href="' . $download_url . '" target="_blank">Descargar versión ' . $latest_version . '</a></p>
        </div>';
}

/**
 * [route_map] returns the Current map of experience routes.
 * @return string Current map of experience routes.
 */

add_shortcode('route_map', 'oc_map');
function oc_map()
{
    return '<div id="app"></div>';
}

function add_vuejs()
{
    wp_register_script('vuejs', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', [], '2.5.17');
    wp_enqueue_script('vuejs');
    wp_register_script('vue-loader', 'https://unpkg.com/http-vue-loader', [], '1.2.4');
    wp_enqueue_script('vue-loader');
}

add_action('wp_enqueue_scripts', 'add_vuejs');

function custom_styles()
{
    wp_enqueue_style('custom-style', plugin_dir_url(__FILE__) . 'css/app.css');

}
add_action('wp_enqueue_scripts', 'custom_styles');
function custom_map_styles()
{
    wp_enqueue_style('map-style', plugin_dir_url(__FILE__) . 'css/map.css');

}
add_action('wp_enqueue_scripts', 'custom_map_styles');

wp_enqueue_script('map', plugin_dir_url(__FILE__) . 'app.js', [], '1.0', true);

/** Always end your PHP files with this closing tag */
?>