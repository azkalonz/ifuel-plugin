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
        'Date' => ['type' => 'date'],
        'Reading in Liters' => ['type' => 'text'],
    );
    public static $form_fields2 = array(
        'Pump' => ['type' => 'text'],
        'Total Sales Amt.' => ['type' => 'text'],
        'Total Sales Volume' => ['type' => 'text'],
        'Shift Schedule' => ['type' => 'datetime-local'],
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

    public static function build_form_field($title, $args, $value)
    {
        $key = sanitize_title($title);
        switch ($args['type']) {
            case 'select':
?>
<label for="<?php echo $key ?>"><?php echo $title ?></label>
<select id="<?php echo $key ?>" name="<?php echo $key ?>">
    <?php foreach ($args['options'] as $key => $value) : ?>
    <option <?php echo $value == $value ? 'selected' : '' ?>>
        <?php echo $value ?>
    </option>
    <?php endforeach; ?>
</select>
<?php
                break;
            default:
            ?>
<label for="<?php echo $key ?>"><?php echo $title ?></label>
<input type="<?php echo $args['type'] ?>" id="<?php echo $key ?>" name="<?php echo $key ?>"
    value="<?php echo $value ?>" />
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

    public static function add_tally()
    {
        return $_POST;
    }

    public static function register()
    {
        register_post_type(SALES_TALLY_POST_TYPE, self::getArgs());
        add_shortcode('sales_tally', 'sales_tally_func');
        function sales_tally_func($atts)
        {
            $user = wp_get_current_user();
            $branch = get_user_meta($user->ID, 'branch_location');
            if (!in_array(sanitize_title(BRANCH_MANAGER_ROLE), $user->roles)) {
                return;
            }
            if (isset($_POST['add_tally'])) {
                $values = SalesTallyPostType::add_tally();
            }
            $location = get_term_by('slug', $branch[0], INVESTOR_TAXONOMY);
            $title = 'iFUEL ' . $location->name;
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
                    <input type="button" value="Add Sales" onclick="submitTally()" />
                </div>
            </div>
        </div>
        <input type="hidden" name="add_tally" value="true" />
    </form>
</div>
<script>
function submitTally() {
    const form = document.querySelector("#sales-tally");
    form.classList.add('submitting');
    form.submit();
}
</script>
<?php
        }
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
            $admin_bar->add_menu(array(
                'id'    => 'my-item',
                'title' => 'Sales Tally', // Your menu title
                'href'  => get_page_link($page), // URL
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
}