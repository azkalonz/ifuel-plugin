<?php
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
            update_post_meta($product->ID, 'volume', $volume - (float)$data['total-sales-volume']);
        }

        return $data;
    }

    public static function register()
    {
        register_post_type(SALES_TALLY_POST_TYPE, self::getArgs());
        add_role(sanitize_title(BRANCH_MANAGER_ROLE), BRANCH_MANAGER_ROLE);
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
        function sales_tally_list_func($atts)
        {
            ob_start();
            if (isset($_POST['delete'])) {
                $deleted = 0;
                foreach ($_POST['delete'] as $key => $value) {
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
    <label for="datepicker">
        Date Range
    </label>
    <input id="datepicker" />
    <form method="POST">
        <input type="submit" value="Delete" style="display: none;" />
        <?php if (isset($deleted)) : ?>
        <div class="success">
            <span>Deleted <b><?php echo $deleted ?></b> items successfully.</span>
        </div>
        <?php endif; ?>
        <table style="margin-top:0;">
            <tbody>
                <tr>
                    <th>
                        <input type="checkbox" id="check-all" />
                    </th>
                    <th>Date</th>
                    <th>Sales Volume</th>
                    <th>Sales Amount</th>
                    <th>Reading (L)</th>
                    <th>Variance (L)</th>
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
                        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
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