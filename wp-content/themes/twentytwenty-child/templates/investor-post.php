<?php

/**
 * Template Name: Investor
 * Template Post Type: investor
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since 1.0
 */

get_header();
?>
<main id="site-content" role="main">

    <?php

	if (have_posts()) {

		while (have_posts()) {
			the_post();
			$meta = get_post_meta($post->ID);
			$user = get_user_by('ID', $meta['user_id'][0]);
		}
	}

	?>

</main><!-- #site-content -->

<?php get_template_part('template-parts/footer-menus-widgets'); ?>

<?php get_footer(); ?>