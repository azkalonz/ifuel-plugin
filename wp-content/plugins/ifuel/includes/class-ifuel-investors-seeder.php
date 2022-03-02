<?php

class InvestorsSeeder
{
    public static $requiredHeaders = array('name', 'date_of_payment', 'mode_of_payment', 'home_address', 'location', 'contact_no', 'email_address', 'broker', 'unit_manager', 'share');

    public static function seed()
    {
        $csv = PLUGIN_ROOT . '/database/artifacts/investors.csv';
        if (($handle = fopen($csv, "r")) !== FALSE) {
            if (self::validate_csv($handle)) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) > 1) {
                        self::create_user($data);
                    }
                }
            }
            fclose($handle);
        }
    }

    public static function create_user($data)
    {
        global $wpdb;
        if (empty($data[4])) return;
        if (!empty($data[6])) {
            $term_slug = sanitize_title($data[4]);

            $user_data = array(
                'user_login' => $data[6],
                'user_email' => $data[6],
                'user_nicename' => $data[0],
                'role' => 'investor'
            );
            if (!username_exists($data[6])) {
                $user = wp_insert_user($user_data);
                $user = get_user_by('email', $user_data['user_email']);
            } else {
                $user = get_user_by('email', $user_data['user_email']);
                $sql = "SELECT * FROM wp_postmeta WHERE meta_key = 'user_id' AND meta_value = '$user->ID'";
                $results = $wpdb->get_results($sql);
                $old_post = $results[0]->post_id;
                $locations = get_the_terms($old_post, INVESTOR_TAXONOMY);
                foreach (self::$requiredHeaders as $key => $value) {
                    delete_post_meta((int)$old_post, $value);
                }
                wp_delete_post($old_post, true);
            }
            $postarr = [
                'post_title' => $data[0],
                'post_status' => 'publish',
                'post_type' => 'investor',
            ];
            $post = wp_insert_post($postarr);

            $l = [];
            $l[$data[4]] = $term_slug;
            if (!$locations) {
                $locations = [];
            }
            foreach ($locations as $key => $value) {
                $l[$value->name] = $value->slug;
            }
            array_unique($l);

            foreach ($l as $key => $value) {
                if (!get_term_by('slug', $value, INVESTOR_TAXONOMY))
                    wp_insert_term($key, INVESTOR_TAXONOMY, array(
                        'slug' => $value
                    ));
            }

            wp_set_object_terms($post, $l, INVESTOR_TAXONOMY);

            foreach (self::$requiredHeaders as $key => $value) {
                add_post_meta($post, $value, $data[$key]);
            }

            update_post_meta($post, 'location', strtoupper(implode(", ", $l)));

            if ($user->ID)
                add_post_meta($post, 'user_id', $user->ID);
        }
    }

    public static function validate_csv($csv_file)
    {

        $firstLine = fgets($csv_file);
        $fileHeader = str_getcsv(trim($firstLine), ',', "'");
        $fileHeader[0] = "name";
        if ($fileHeader !== self::$requiredHeaders) {
            return false;
        }

        return true;
    }
}