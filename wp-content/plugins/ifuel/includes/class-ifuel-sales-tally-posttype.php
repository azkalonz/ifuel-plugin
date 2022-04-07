<?php
session_start();
require_once PLUGIN_ROOT . '/includes/class-ifuel-investors-seeder.php';

class SalesTallyPostType
{
    public static $form_fields1 = array(
        'Fuel Type' => [
            'type' => 'select',
            'options' => array('Diesel', 'iGaz 95', 'iGaz 91')
        ],
        'Pump Reading Beg' => ['type' => 'text'],
        'Pump Reading End' => ['type' => 'text'],
        'Date' => ['type' => 'date', 'hasDefault' => true],
        'Reading in Liters' => ['type' => 'text'],
    );
    public static $form_fields2 = array(
        'Pump' => [
            'type' => 'select',
            'options' => array('Pump 1', 'Pump 2', 'Pump 3', 'Pump 4', 'Pump 5', 'Pump 6')
        ],
        'Total Sales Amt.' => ['type' => 'text'],
        'Total Sales Volume' => ['type' => 'text'],
        'Shift Schedule' => [
            'type' => 'select',
            'options' => array('1st', '2nd', '3rd')
        ],
        'Variance in Liters' => ['type' => 'text'],
        'Cashier' => ['type' => 'text'],
    );
    public static $labels = array(
        'name' => 'Sales Tally',
        'singular_name' => 'Sale',
        'add_new' => 'Add New Sale',
        'add_new_item' => 'Add New Sale',
        'edit_item' => 'Edit Sale',
        'new_item' => 'New Sale',
        'all_items' => 'All Sales Tally',
        'view_item' => 'View Sale',
        'search_items' => 'Search Sales Tally',
        'not_found' =>  'No Sales Tally Found',
        'not_found_in_trash' => 'No Sales Tally found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Sales Tally',
    );

    public function getDefault($key)
    {
        switch ($key) {
            case sanitize_title("Date"):
                return date('Y-m-d');
        }
        return null;
    }

    public static function getStyle()
    {
?>
<style>
.sales-tally {
    width: 100% !important;
    max-width: 900px !important;
    margin: 0 auto;
    background-color: #ffffff;
    border: 1px solid #bcbcbc;
    padding: 30px;
}

.sales-tally .success {
    background: #c9ffc9;
    border: 1px solid green;
    padding: 8px;
    border-radius: 8px;
    margin: 13px 0;
    font-size: smaller;
}

.sales-tally .error {
    background: lightpink;
    border: 1px solid red;
    padding: 8px;
    border-radius: 8px;
    margin: 13px 0;
    font-size: smaller;
}

.sales-tally h1 {
    text-align: center;
    margin: 0;
    margin-bottom: 50px;
}

.sales-tally .container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
}

.sales-tally form.submitting {
    pointer-events: none;
    user-select: none;
    opacity: 0.3;
}

.sales-tally input,
.sales-tally select {
    padding: 6px;
    font-size: 18px;
    border: none;
    border-radius: 7px;
    margin-bottom: 13px;
    border: 1px solid #bcbcbc;
}

.sales-tally .footer {
    text-align: right;
    margin-top: 18px;
}
</style>
<?php
    }
    public static function build_form_field($title, $args, $tvalue)
    {
        $SALES_TYPE = new SalesTallyPostType();
        $key = sanitize_title($title);
        switch ($args['type']) {
            case 'select':
        ?>
<label for="<?php echo $key ?>"><?php echo $title ?></label>
<select id="<?php echo $key ?>" name="<?php echo $key ?>">
    <?php foreach ($args['options'] as $key => $value) : ?>
    <?php if (isset($tvalue)) : ?>
    <option <?php echo $value == $tvalue ? 'selected' : '' ?>>
        <?php echo $value ?>
    </option>
    <?php endif; ?>
    <?php if (!isset($tvalue)) : ?>
    <option <?php echo $key == 0 ? 'selected' : '' ?>>
        <?php echo $value ?>
    </option>
    <?php endif; ?>
    <?php endforeach; ?>
</select>
<?php
                break;
            default:
            ?>
<label for="<?php echo $key ?>"><?php echo $title ?></label>
<input type="<?php echo $args['type'] ?>" id="<?php echo $key ?>" name="<?php echo $key ?>"
    value="<?php echo isset($args['hasDefault']) && $args['hasDefault'] ? $SALES_TYPE->getDefault($key) : $tvalue ?>" />
<?php
                break;
        }
    }

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
                'custom-fields',
            )
        );

        return $args;
    }

    public static function add_tally($data)
    {
        if (empty($data['date'])) {
            $data['date'] = date('Y-m-d');
        }
        $user = wp_get_current_user();
        $branch = get_user_meta($user->ID, 'branch_location');
        $not_enough_volume = false;

        $inventory = get_posts([
            's' => $data['fuel-type'],
            'post_type' => INVENTORY_POST_TYPE,
            'meta_key' => 'branch',
            'meta_query' => array(
                'key' => 'branch',
                'value' => $branch[0],
                'compare' => '='
            )
        ]);

        foreach ($inventory as $key => $product) {
            $volume = get_post_meta($product->ID, 'volume');
            $volume = (float)$volume[0] ?? 0;
            $newvolume = $volume - (float)$data['total-sales-volume'];
            if ($newvolume < 0) {
                $not_enough_volume = true;
                continue;
            }
            update_post_meta($product->ID, 'volume', $newvolume);
        }

        if ($not_enough_volume) {
            return [
                "error" => "Not enough volume on {$data['fuel-type']}."
            ];
        }

        $post = wp_insert_post([
            'ID' => (int)(isset($data['ID']) ? $data['ID'] : 0),
            'post_title' => date('Y-m-d'),
            "post_status" => "publish",
            "post_content" => " ",
            'post_type' => SALES_TALLY_POST_TYPE,
        ], true);
        foreach ($data as $key => $value) {
            if ($key == 'ID') continue;
            delete_post_meta($post, $key);
            add_post_meta($post, $key, $value);
        }
        add_post_meta($post, 'branch', $branch[0]);
        $data['ID'] = $post;

        return $data;
    }

    public static function register()
    {
        register_post_type(SALES_TALLY_POST_TYPE, self::getArgs());
        add_role(sanitize_title(BRANCH_MANAGER_ROLE), BRANCH_MANAGER_ROLE);

        try {
            if (isset($_FILES['sales_tally']['tmp_name'])) {
                echo 'found';
                $_SESSION['sales_file'] = $_FILES['sales_tally']['tmp_name'];
                $file = $_SESSION['sales_file'];
                $csv = file_get_contents($file);
                $array = array_map("str_getcsv", explode("\n", $csv));
                $_SESSION['sales_json'] = json_encode($array);
                unset($_SESSION['sales_error']);
                return;
            }

            if (isset($_POST['sales_file']) && isset($_SESSION['sales_json'])) {
                echo 'decoded';
                $sales = json_decode($_SESSION['sales_json']);
                $keys = [];
                $mapped_keys = [];
                $user = wp_get_current_user();
                $branch = get_user_meta($user->ID, 'branch_location');
                $inserted = 0;

                foreach (SalesTallyPostType::$form_fields1 as $key => $field) {
                    array_push($keys, $key);
                }

                foreach (SalesTallyPostType::$form_fields2 as $key => $field) {
                    array_push($keys, $key);
                }

                foreach ($sales[0] as $s => $v) {
                    $mapped_keys[$v] = $s;
                }

                foreach ($keys as $key => $value) {
                    $mapped_key = $mapped_keys[$_POST[sanitize_title($value)]];
                    $mapped_keys[sanitize_title($value)] = $mapped_key;
                }

                foreach ($sales as $k => $sale) {
                    if ($k == 0 || empty($sale[0])) continue;

                    $date = explode('/', $sale[$mapped_keys['date']]);
                    $date = $date[2] . '-' . $date[0] . '-' . $date[1];
                    $post = wp_insert_post([
                        'post_title' => date('Y-m-d', strtotime($sale[$mapped_keys['date']])),
                        "post_status" => "publish",
                        "post_content" => " ",
                        'post_type' => SALES_TALLY_POST_TYPE,
                    ], true);

                    if ($post) {
                        $inserted++;
                    }

                    foreach ($keys as $key => $value) {
                        if ($value == 'Date') {
                            $sale[$mapped_keys[sanitize_title($value)]] = date('Y-m-d', strtotime($sale[$mapped_keys[sanitize_title($value)]]));
                        }
                        add_post_meta($post, sanitize_title($value), $sale[$mapped_keys[sanitize_title($value)]]);
                    }
                    add_post_meta($post, 'branch', $branch[0]);
                }

                $_SESSION['message'] = 'Imported <b>' . $inserted . '</b> sales.';
                unset($_SESSION['sales_file']);
                unset($_SESSION['sales_json']);
                unset($_SESSION['sales_error']);
            } else {
                unset($_SESSION['message']);
            }
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public static function hooks()
    {
        add_action('admin_bar_menu', 'add_toolbar_items', 100);
        function add_toolbar_items($admin_bar)
        {
            $user = wp_get_current_user();
            if (!in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }
            $page = get_page_by_title('Sales Tally');
            $listpage = get_page_by_title('Sales Tally List');
            $admin_bar->add_menu(array(
                'id'    => 'sales-tally',
                'title' => 'Sales Tally', // Your menu title
                'href'  => get_page_link($page), // URL
                'meta'  => array(
                    'target' => '_blank',
                ),
            ));

            $admin_bar->add_menu(array(
                'id'    => 'sales-tally-list',
                'parent' => 'sales-tally',
                'title' => 'All Sales',
                'href'  => get_page_link($listpage),
                'meta'  => array(
                    'target' => '_blank',
                ),
            ));
        }

        function user_branch_form($user)
        {
            if (isset($user->roles) && !in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }
            ?>
<h2>Branch</h2>
<table class="form-table">
    <tr>
        <th><label for="user_branch_location">Location</label></th>
        <td>
            <select type="date" name="user_branch_location" id="user_branch_location">
                <?php
                            $taxonomies = get_terms(array('taxonomy' => INVESTOR_TAXONOMY));
                            foreach ($taxonomies as $key => $value) : ?>
                <option
                    <?php echo esc_attr(get_user_meta($user->ID, 'branch_location', true)) == $value->slug ? 'selected' : '' ?>
                    value="<?php echo $value->slug ?>"><?php echo $value->name ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
</table>
<?php
        }
        add_action('show_user_profile', 'user_branch_form'); // editing your own profile
        add_action('edit_user_profile', 'user_branch_form'); // editing another user
        add_action('user_new_form', 'user_branch_form'); // creating a new user

        function userMetaBirthdaySave($userId)
        {
            if (!current_user_can('edit_user', $userId) || !isset($_REQUEST['user_branch_location'])) {
                return;
            }

            update_user_meta($userId, 'branch_location', $_REQUEST['user_branch_location']);
        }
        add_action('personal_options_update', 'userMetaBirthdaySave');
        add_action('edit_user_profile_update', 'userMetaBirthdaySave');
        add_action('user_register', 'userMetaBirthdaySave');
    }

    public static function shortcodes()
    {
        add_shortcode('sales_tally_list', 'sales_tally_list_func');

        if (isset($_GET['action']) && $_GET['action'] == 'cancel-import') {
            unlink($_SESSION['sales_file']);
            unset($_SESSION['sales_file']);
        }

        if (isset($_SESSION['sales_file'])) {
            function sales_tally_list_func($atts)
            {
                $fd = fopen($_SESSION['sales_file'], "r");

                $counter = 0;

                while (!feof($fd)) {
                    if ($counter === 1)
                        break;

                    $buffer = fgetcsv($fd, 5000);
                    ++$counter;
                }
                fclose($fd);

                $keys = [];

                foreach (SalesTallyPostType::$form_fields1 as $key => $field) {
                    array_push($keys, $key);
                }

                foreach (SalesTallyPostType::$form_fields2 as $key => $field) {
                    array_push($keys, $key);
                }

                if (isset($buffer)) {

            ?>
<form method="POST">
    <input type="hidden" name="sales_file" value="<?php echo $_SESSION['sales_file'] ?>" />
    <table>
        <thead>
            <tr>
                <th>Field</th>
                <th>Mapping</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($keys as $key => $value) :
                                    ob_start();
                                ?>
            <select name="<?php echo sanitize_title($value) ?>">
                <?php foreach ($buffer as $k => $v) : ?>
                <option <?php echo levenshtein($v, $value) == 0 ? 'selected' : '' ?>><?php echo $v ?></option>
                <?php endforeach; ?>
            </select>
            <?php
                                    $select = ob_get_clean(); ?>

            <tr>
                <td><?php echo $value ?></td>
                <td>
                    <?php echo $select ?>
                </td>
            </tr>
            <?php endforeach;
                            }
                            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right">
                    <a class="button"
                        href="<?php echo add_query_arg('action', 'cancel-import', get_page_link(get_page_by_title('Sales Tally List'))) ?>">Cancel</a>
                    <input class="button" type="submit" value="Proceed" />
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<?php
                $html = ob_get_clean();

                return $html;
            }
        }

        if (!function_exists('sales_tally_list_func')) {
            function sales_tally_list_func($atts)
            {
                ob_start();

                if (isset($_POST['delete'])) {
                    $deleted = 0;
                    foreach ($_POST['delete'] as $key => $value) {
                        $post = get_post($value);
                        $fueltype = get_post_meta($post->ID, 'fuel-type', true);
                        $salesvolume = get_post_meta($post->ID, 'total-sales-volume', true);
                        $user = wp_get_current_user();
                        $branch = get_user_meta($user->ID, 'branch_location');

                        $inventory = get_posts([
                            's' => $fueltype,
                            'post_type' => INVENTORY_POST_TYPE,
                            'meta_key' => 'branch',
                            'meta_query' => array(
                                'key' => 'branch',
                                'value' => $branch[0],
                                'compare' => '='
                            )
                        ]);

                        foreach ($inventory as $key => $product) {
                            $volume = get_post_meta($product->ID, 'volume');
                            $volume = (float)$volume[0] ?? 0;
                            update_post_meta($product->ID, 'volume', $volume + (float)$salesvolume);
                        }
                        if (wp_delete_post((int)$value, true)) {
                            $deleted++;
                        }
                    }
                }
                SalesTallyPostType::getStyle();
                $user = wp_get_current_user();
                $branch = get_user_meta($user->ID, 'branch_location');
                $from = $_GET['from'] ?? date('Y/m/01');
                $to = $_GET['to'] ??  date('Y/m/d');
                $the_query = new WP_Query(
                    array(
                        'posts_per_page' => 10,
                        'post_type' => SALES_TALLY_POST_TYPE,
                        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key' => 'branch',
                                'value' => $branch[0],
                            ),
                            array(
                                'key' => 'date',
                                'value' => array($from, $to),
                                'compare' => 'BETWEEN',
                                'type' => 'DATE'
                            ),
                        )
                    )
                );
                ?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<div class="sales-tally">
    <h1 style="text-transform: capitalize;"><?php echo $branch[0] ?></h1>
    <div style="display: flex; justify-content: space-between; align-items: center">
        <div>
            <label for="datepicker">
                Date Range
            </label>
            <input id="datepicker" />
        </div>
        <?php
                            $q = get_posts([
                                'post_type' => SALES_TALLY_POST_TYPE,
                                'meta_key' => 'branch',
                                'meta_query' => array(
                                    'key' => 'branch',
                                    'value' => $branch[0],
                                    'compare' => '='
                                )
                            ]);
                            if (sizeof($q) <= 0) :
                            ?>
        <form id="import-form" method="POST" enctype="multipart/form-data"
            action="<?php echo add_query_arg('action', 'import', get_page_link(get_page_by_title('Sales Tally List'))) ?>">
            <label class="button" for="import-sales">Import</label>
            <input id="import-sales" class="button" accept=".csv" value="Import" type="file" style="display: none;"
                name="sales_tally" onchange="document.querySelector('#import-form').submit()" />
        </form>
        <?php endif; ?>
    </div>
    <form method="POST">
        <input type="submit" value="Delete" style="display: none;" />
        <?php if (isset($deleted)) : ?>
        <div class="success">
            <span>Deleted <b><?php echo $deleted ?></b> items successfully.</span>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['sales_error'])) : ?>
        <div class="error">
            <span><?php echo $_SESSION['sales_error'] ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])) : ?>
        <div class="success">
            <span><?php echo $_SESSION['message'] ?></span>
        </div>
        <?php endif; ?>
        <table style="margin-top:0;">
            <tbody>
                <tr>
                    <th>
                        <input type="checkbox" id="check-all" />
                    </th>
                    <th>Date</th>
                    <th>Fuel Type</th>
                    <th>Pump Reading Beg</th>
                    <th>Pump Reading End</th>
                    <th>Sales Volume</th>
                    <th>Sales Amount</th>
                    <th>Reading (L)</th>
                    <th>Variance (L)</th>
                    <th>Shift Schedule</th>
                    <th>Cashier</th>
                </tr>
                <?php while ($the_query->have_posts()) : $the_query->the_post(); ?>
                <?php
                                        $meta = get_post_meta(get_the_ID());
                                        ?>
                <tr>
                    <td>
                        <input type="checkbox" class="check-sale" name="delete[]" value="<?php echo the_ID() ?>" />
                    </td>
                    <td>
                        <a href="<?php echo add_query_arg('ID', get_the_ID(), get_page_link(get_page_by_title('Sales Tally'))) ?>"
                            class="file-title" target="_blank">
                            <?php echo !empty($meta['date'][0]) ? $meta['date'][0] : get_the_title() ?>
                        </a>
                    </td>
                    <td>
                        <?php echo $meta['fuel-type'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['pump-reading-beg'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['pump-reading-end'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['total-sales-volume'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['total-sales-amt'][0] ?>

                    </td>
                    <td>
                        <?php echo $meta['reading-in-liters'][0] ?>

                    </td>
                    <td>
                        <?php echo $meta['variance-in-liters'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['shift-schedule'][0] ?>
                    </td>
                    <td>
                        <?php echo $meta['cashier'][0] ?>
                    </td>
                </tr>
                <?php
                                    endwhile; ?>
            </tbody>
        </table>
    </form>
    <div>
        <?php
                            $big = 999999999; // need an unlikely integer
                            echo paginate_links(array(
                                'base' => str_replace([$big, '&#038;'], ['%#%', '&'], get_pagenum_link($big)),
                                'format' => '?paged=%#%',
                                'current' => max(1, get_query_var('paged')),
                                'total' => $the_query->max_num_pages
                            ));

                            wp_reset_postdata();
                            ?>
    </div>
</div>
<script>
$(function() {
    $('#datepicker').daterangepicker({
        opens: 'left',
        startDate: new Date(`<?php echo $from ?>`),
        endDate: new Date(`<?php echo $to ?>`)
    }, function(start, end, label) {
        let newloc = `<?php echo add_query_arg(array(
                                                    'from' => '$FROM',
                                                    'to' => '$TO'
                                                ), get_page_link(get_page_by_title('Sales Tally List'))) ?>`;
        newloc = newloc.replace('$FROM', start.format('YYYY/MM/DD'));
        newloc = newloc.replace('$TO', end.format('YYYY/MM/DD'));
        window.location = newloc;
    });
    $('#check-all').on('change', function() {
        $('[name="delete[]"]').prop('checked', this.checked)
    })
    $('input').on('change', function() {
        let f = false;
        $('input').each(function() {
            if (this.checked) f = true;
        })
        if (f) {
            $('[type=submit]').show();
        } else {
            $('[type=submit]').hide();
        }
    })
});
</script>
<?php
                $html = ob_get_clean();
                return $html;
            }
        }
        add_shortcode('sales_tally', 'sales_tally_func');
        function sales_tally_func($atts)
        {
            ob_start();
            $user = wp_get_current_user();
            $branch = get_user_meta($user->ID, 'branch_location');
            if (!in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }
            if (isset($_POST['add_tally'])) {
                $values = SalesTallyPostType::add_tally($_POST);
            }
            if (isset($_GET['ID'])) {
                $data = [];
                $post = get_post($_GET['ID']);
                $data['ID'] = $post->ID;
                $data['post_title'] = $post->post_title;
                $data['post_status'] = $post->post_status;
                $data['post_content'] = $post->post_content;
                $data['post_type'] = $post->post_type;
                foreach (get_post_meta($post->ID) as $key => $value) {
                    $data[$key] = $value[0];
                }
                $values = $data;
            }
            $location = get_term_by('slug', $branch[0], INVESTOR_TAXONOMY);
            $title = 'iFUEL ' . $location->name;
                ?>
<?php SalesTallyPostType::getStyle() ?>
<div class="sales-tally">
    <h1><?php echo $title ?></h1>
    <?php if (isset($values['error'])) : ?>
    <div class="error">
        <span><?php echo $values['error'] ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" id="sales-tally">
        <div class="container">
            <div>
                <?php foreach (SalesTallyPostType::$form_fields1 as $key => $value) : ?>
                <?php SalesTallyPostType::build_form_field($key, $value, isset($values) ? $values[sanitize_title($key)] : null) ?>
                <?php endforeach; ?>
            </div>
            <div>
                <?php foreach (SalesTallyPostType::$form_fields2 as $key => $value) : ?>
                <?php SalesTallyPostType::build_form_field($key, $value,  isset($values) ? $values[sanitize_title($key)] : null) ?>
                <?php endforeach; ?>
                <div class="footer">
                    <input type="button" value="New"
                        onclick="window.location = '<?php echo get_page_link(get_page_by_title('Sales Tally')) ?>'" />
                    <input type="button" value="<?php echo isset($values['ID']) ? 'Save' : 'Add Sales' ?>"
                        onclick="submitTally()" />
                </div>
            </div>
        </div>
        <input type="hidden" name="add_tally" value="true" />
        <input type="hidden" name="ID" value="<?php echo isset($values['ID']) ? $values['ID'] : null ?>" />
    </form>
</div>
<script>
function submitTally() {
    const form = document.querySelector("#sales-tally");
    form.classList.add('submitting');
    form.submit();
}
(() => {
    const $ = (e) => document.querySelectorAll(e);
    $('#pump-reading-end, #pump-reading-beg, #reading-in-liters, #total-sales-volume').forEach((el) => {
        el.addEventListener('input', () => {
            try {
                $('#reading-in-liters')[0].value = ($('#pump-reading-end')[0].value - $(
                        '#pump-reading-beg')[0]
                    .value);
                $('#variance-in-liters')[0].value = ($('#total-sales-volume')[0].value - $(
                        '#reading-in-liters')[0]
                    .value);
            } catch (e) {}
        })
    })

})();
</script>
<?php
            $html = ob_get_clean();
            return $html;
        }
    }
}