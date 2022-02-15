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
        if (empty($data[6])) return;
        $user = array(
            'user_login' => $data[6],
            'user_email' => $data[6],
            'user_nicename' => $data[0]
        );
        if (!username_exists($data[6])) {
            $user_id = wp_insert_user($user);
            $postarr = [
                'post_title' => $data[0],
                'post_status' => 'publish',
                'post_type' => 'investor',
            ];
            $post = wp_insert_post($postarr);
            foreach (self::$requiredHeaders as $key => $value) {
                add_post_meta($post, $value, $data[$key]);
            }
            add_post_meta($post, 'user_id', $user_id);
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