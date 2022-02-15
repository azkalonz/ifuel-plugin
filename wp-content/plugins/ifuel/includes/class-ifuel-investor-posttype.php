<?php

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
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'product'),
            'query_var' => true,
            'menu_icon' => 'dashicons-randomize',
            'supports' => array(
                'title',
                // 'editor',
                // 'excerpt',
                // 'trackbacks',
                // 'custom-fields',
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
        register_post_type('investor', self::getArgs());
        register_taxonomy(
            'investor_locations',
            'investor',
            array(
                'hierarchical' => true,
                'label' => 'Locations',
                'query_var' => true,
                'rewrite' => array('slug' => 'investor-locations')
            )
        );
        register_post_status('unread', array(
            'label'                     => _x('Unread', 'investor'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>'),
        ));
    }

    public static function hooks()
    {
        add_filter('enter_title_here', 'my_title_place_holder', 20, 2);
        function my_title_place_holder($title, $post)
        {

            if ($post->post_type == 'investor') {
                $my_title = "Investor Name";
                return $my_title;
            }

            return $title;
        }
    }
}