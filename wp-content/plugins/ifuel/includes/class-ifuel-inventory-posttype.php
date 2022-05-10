<?php
require_once PLUGIN_ROOT . '/includes/class-ifuel-sales-tally-posttype.php';

class InventoryPosttype
{
    public static $labels = array(
        'name' => 'iFuel Inventory',
        'singular_name' => 'Inventory',
        'add_new' => 'Add New Inventory',
        'add_new_item' => 'Add New Inventory',
        'edit_item' => 'Edit Inventory',
        'new_item' => 'New Inventory',
        'all_items' => 'All iFuel Inventory',
        'view_item' => 'View Inventory',
        'search_items' => 'Search iFuel Inventory',
        'not_found' =>  'No iFuel Inventory Found',
        'not_found_in_trash' => 'No iFuel Inventory found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'iFuel Inventory',
    );

    public static function getArgs()
    {
        $args = array(
            'labels' => self::$labels,
            'public' => true,
            'has_archive' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'query_var' => true,
            'menu_icon' => 'dashicons-cart',
            'supports' => array(
                'title',
                'custom-fields',
            )
        );

        return $args;
    }

    public static function shortcodes()
    {
        add_shortcode('ifuel_inventory', 'inventory_summary');
        function inventory_summary($atts)
        {
            ob_start();

            $user = wp_get_current_user();
            $branch = get_user_meta($user->ID, 'branch_location');
            if (!in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }

            $products = get_posts([
                'post_type' => INVENTORY_POST_TYPE,
                'meta_key' => 'branch',
                'meta_query' => array(
                    array(
                        'key' => 'branch',
                        'value' => $branch[0] ?? 'global',
                        'compare' => '=',
                    )
                )
            ]);

?>
<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Volume</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $key => $product) : ?>
        <?php
                        $volume = get_post_meta($product->ID, 'volume');
                        ?>
        <tr>
            <td><?php echo ((float)$volume[0] <= 5000 ? '<a style="color: red">⚠</a>&nbsp;' : '') ?><?php echo $product->post_title ?>
            </td>
            <td><?php echo $volume[0] ?></td>
            <td><a style="cursor: pointer;" onclick="reOrder(`<?php echo $product->ID ?>`)">Reorder</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
function reOrder(id) {
    const volume = prompt('Enter volume: ');
    let requestUrl = `<?php echo add_query_arg([
                                            'volume' => '$volume',
                                            'branch' => $branch[0],
                                            'product' => '$id'
                                        ], get_page_link(get_page_by_title('iFuel Inventory'))) ?>`;
    requestUrl = requestUrl.replaceAll('$volume', volume);
    requestUrl = requestUrl.replaceAll('$id', id);
    window.location = requestUrl;
}
</script>
<?php

            $html = ob_get_clean();

            return $html;
        }
    }

    public static function register()
    {
        register_post_type(INVENTORY_POST_TYPE, self::getArgs());

        $user = wp_get_current_user();
        $branch = get_user_meta($user->ID, 'branch_location');
        $posts = get_posts([
            "post_type" => INVENTORY_POST_TYPE,
            'meta_key' => 'branch',
            'meta_query' => array(
                array(
                    'key' => 'branch',
                    'value' => $branch[0] ?? 'global',
                    'compare' => '=',
                )
            )
        ]);

        if (sizeof($posts) == 0) {
            foreach (SalesTallyPostType::$form_fields1['Fuel Type']['options'] as $key => $product) {
                $post = wp_insert_post([
                    'post_title' => $product,
                    "post_status" => "publish",
                    "post_content" => " ",
                    'post_type' => INVENTORY_POST_TYPE,
                ]);
                add_post_meta($post, 'volume', 0);
                add_post_meta($post, 'branch', $branch[0] ?? "global");
            }
        }

        if (isset($_GET['volume']) && isset($_GET['branch']) && isset($_GET['product'])) {

            if (!empty($_GET['volume']) && is_numeric($_GET['volume'])) {
                $new_vol = (float)(get_post_meta($_GET['product'], 'volume') ?? [0])[0];
                $new_vol += (float)$_GET['volume'];
                update_post_meta($_GET['product'], 'volume', $new_vol);
            ?>
<script>
window.location = `<?php echo get_page_link(get_page_by_title('iFuel Inventory')) ?>`;
</script>
<?php
            }
        }
    }

    public static function hooks()
    {
        add_filter('enter_title_here', 'changetitle', 20, 2);
        function changetitle($title, $post)
        {

            if ($post->post_type == INVENTORY_POST_TYPE) {
                $my_title = "Product Name";
                return $my_title;
            }

            return $title;
        }

        add_action('admin_bar_menu', 'add_inventory_menu', 100);
        function add_inventory_menu($admin_bar)
        {
            $user = wp_get_current_user();
            if (!in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }
            $page = get_page_by_title('iFuel Inventory');
            $listpage = get_page_by_title('iFuel Products');
            $admin_bar->add_menu(array(
                'id'    => 'ifuel-inventory',
                'title' => 'Inventory', // Your menu title
                'href'  => get_page_link($page), // URL
                'meta'  => array(
                    'target' => '_blank',
                ),
            ));
        }

        add_filter('manage_ifuel_inventory_posts_columns', function ($columns) {
            $custom_col_order = array(
                'cb' => __('cb'),
                'title' => __('Name'),
                'branch' => __('Branch'),
                'volume' => __('Volume'),
            );
            return $custom_col_order;
        });

        add_action('manage_ifuel_inventory_posts_custom_column', 'custom_inventory_columns', 10, 2);
        function custom_inventory_columns($column, $post_id)
        {
            switch ($column) {

                case 'branch':
                    $branch = (get_post_meta($post_id, 'branch') ?? [])[0];
                    echo $branch;
                    break;

                case 'volume':
                    $volume = (get_post_meta($post_id, 'volume') ?? [])[0];
                    echo $volume;
                    break;
            }
        }
    }
}