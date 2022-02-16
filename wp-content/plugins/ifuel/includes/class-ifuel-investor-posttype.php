<?php
require_once PLUGIN_ROOT . '/includes/class-ifuel-investors-seeder.php';

class InvestorPostType
{
    public static $labels = array(
        'name' => 'Investors',
        'singular_name' => 'Investor',
        'add_new' => 'Add New Investor',
        'add_new_item' => 'Add New Investor',
        'edit_item' => 'Edit Investor',
        'new_item' => 'New Investor',
        'all_items' => 'All Investors',
        'view_item' => 'View Investor',
        'search_items' => 'Search Investors',
        'not_found' =>  'No Investors Found',
        'not_found_in_trash' => 'No Investors found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Investors',
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
            'rewrite' => array('slug' => 'product'),
            'query_var' => true,
            'menu_icon' => 'dashicons-randomize',
            'supports' => array(
                'title',
                'custom-fields',
                // 'editor',
                // 'excerpt',
                // 'trackbacks',
                // 'comments',
                // 'revisions',
                // 'thumbnail',
                // 'author',
                // 'page-attributes'
            )
        );

        return $args;
    }

    public static function register()
    {
        register_post_type(INVESTOR_POST_TYPE, self::getArgs());
        register_taxonomy(
            INVESTOR_TAXONOMY,
            INVESTOR_POST_TYPE,
            array(
                'hierarchical' => true,
                'label' => 'Locations',
                'query_var' => true,
                'rewrite' => array('slug' => 'investor-locations')
            )
        );
        register_post_status('banned', array(
            'label'                     => _x('Banned', INVESTOR_POST_TYPE),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Banned <span class="count">(%s)</span>', 'Banned <span class="count">(%s)</span>'),
        ));
    }

    public static function hooks()
    {
        add_role(INVESTOR_POST_TYPE, 'Investor');
        add_filter('manage_investor_posts_columns', function ($columns) {
            $custom_col_order = array(
                'cb' => __('cb'),
                'title' => __('Name'),
                'date_of_payment' => __('Payment Date'),
                'share' => __('Share'),
                'broker' => __('Broker'),
                'location' => __('Location'),
            );
            return $custom_col_order;
        });

        add_action('manage_investor_posts_custom_column', 'custom_investor_column', 10, 2);
        function custom_investor_column($column, $post_id)
        {
            switch ($column) {

                case 'share':
                    $share = (get_post_meta($post_id, 'share') ?? [])[0];
                    echo ((float) $share * 100) . '%';
                    break;

                case 'location':
                    $location = (get_post_meta($post_id, 'location') ?? [])[0];
                    echo $location;
                    break;

                case 'date_of_payment':
                    $date_of_payment = (get_post_meta($post_id, 'date_of_payment') ?? [])[0];
                    echo $date_of_payment;
                    break;

                case 'broker':
                    $broker = (get_post_meta($post_id, 'broker') ?? [])[0];
                    echo $broker;
                    break;
            }
        }

        add_filter('enter_title_here', 'my_title_place_holder', 20, 2);
        function my_title_place_holder($title, $post)
        {

            if ($post->post_type == INVESTOR_POST_TYPE) {
                $my_title = "Investor Name";
                return $my_title;
            }

            return $title;
        }

        add_action('admin_head-edit.php', 'toolbar');

        function toolbar()
        {
            InvestorPostType::custom_toolbar();
        }
    }

    public static function import_csv($tmp_name)
    {
        $path = TEMP_DIR . '/investors.csv';
        $OK = move_uploaded_file($tmp_name, $path);
        if ($OK) {
            $csv = $path;
            $is_valid_format = false;
            if (($handle = fopen($csv, "r")) !== FALSE) {
                if (InvestorsSeeder::validate_csv($handle)) {
                    $is_valid_format = true;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) > 1) {
                            InvestorsSeeder::create_user($data);
                        }
                    }
                }
                fclose($handle);
            }
            if ($is_valid_format) {
                return $path;
            }
        }
        return false;
    }

    public static function custom_toolbar()
    {
        global $current_screen;
        if (INVESTOR_POST_TYPE != $current_screen->post_type) {
            return;
        }
        if (isset($_FILES['investors_csv'])) {
            $ext = explode('.', $_FILES['investors_csv']['name']);
            $extension = end($ext);
            $errors = [];
            if ($extension == 'csv') {
                $csv_path = self::import_csv($_FILES['investors_csv']['tmp_name']);
                if ($csv_path) {
                    add_action('admin_notices', function () use (&$total) {
                        $class = 'notice notice-success is-dismissible';
                        $message = __('Imported successfully');
                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
                    });
                } else {
                    $headers = implode(", ", InvestorsSeeder::$requiredHeaders);
                    array_push($errors, "Invalid file structure. Follow the format: $headers");
                }
            }
            if (sizeof($errors) > 0) {
                foreach ($errors as $error) {
                    add_action('admin_notices', function () use (&$error) {
                        $class = 'notice notice-error';
                        $message = __($error);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
                    });
                }
            }
        }

?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    jQuery(".page-title-action").after(`
    <form id="import-investors-csv" action="<?php echo basename($_SERVER['REQUEST_URI']); ?>" class="page-title-action" style="display: inline-block" method="POST" enctype="multipart/form-data">
        <label for="file-upload">
            Import CSV
        </label>
        <input id="file-upload" name="investors_csv" type="file" accept=".csv" style="display: none;" onchange="importCSV()"/>
    </form>
`);
});

function importCSV() {
    jQuery('#import-investors-csv').submit();
}
</script>
<?php
    }
}