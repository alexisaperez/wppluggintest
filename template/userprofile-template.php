<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other 'pages' on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage MMM
 * @since MMM 1.0
 */

/**
 * get the content with formating
 */
//$content = apply_filters('the_content', get_the_content());
//$content = $post->post_content;
//$content = str_replace(']]>', ']]&gt;', $content);

/* get the header */
get_header();

$setting = BSettings::get_instance();
$title = $setting->get_value('membership-title');
$profile_url = BUtils::get_profile_url();

$page_id = get_the_ID();
?>

<div class="mmmm_template_header">
	<h2><?php echo $title; ?></h2>
	<ul class="ncoa_membership_tabs">
		<li><a href="<?php echo get_permalink($profile_url); ?>" class="<?php echo ($profile_url == $page_id)?'active':''; ?>">Your Profile</a></li>
		<?php

			//check domain for additional tabs
			//if($_SERVER['HTTP_HOST'];
			$domains = BUtils::get_domain_names();
			$hosturl = explode('.', $_SERVER['HTTP_HOST'], 2);
			$host = $hosturl[1];

			if (in_array($host, $domains)) {
				//$key = array_search($host, $domains);
				$setting = BSettings::get_instance();
        $mmmdomain = $setting->get_value('mmm-domain');
        $niscdomain = $setting->get_value('nisc-domain');

				switch ($host) {
					case $mmmdomain:

						unset($_SESSION["niscdomain"]);
						$subscription_url = BUtils::get_subscription_url();
						$saved_results_url = BUtils::get_saved_results_url();

						if($subscription_url != ""): ?>
							<li><a href="<?php echo get_permalink($subscription_url); ?>" class="<?php echo ($subscription_url == $page_id)?'active':''; ?>"><?php echo get_the_title($subscription_url); ?></a></li>
						<?php endif; ?>
						<?php if($saved_results_url != ""): ?>
							<li><a href="<?php echo get_permalink($saved_results_url) ?>" class="<?php echo ($saved_results_url == $page_id)?'active':''; ?>"><?php echo get_the_title($saved_results_url); ?></a></li>
						<?php endif;
					break;
					case $niscdomain:

						$_SESSION["niscdomain"] = 1;
						$mgn_url = BUtils::get_mng_url();
						$benefits_url = BUtils::get_benefits_url();
						$accreditation_url = BUtils::get_accreditation_url();
						$opportunities_url = BUtils::get_opportunities_url();
						$news_url = BUtils::get_news_url();

        		$auth = BAuth::get_instance();
            $user_data = (array) $auth->userData;

						//user management tab if plugin active
						if ( ! function_exists( 'is_plugin_active' ) ){
				     require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				    }

				 		if ( is_plugin_active( 'nisc-users-management/nisc-users-management.php' ) ){
				 			if(isset($user_data['related']) && !empty($user_data['related'])){
				 				$primary_profile = $user_data['related'][1];
				 				if($primary_profile->isprimary == TRUE){
				 			?>
							    <li><a href="<?php echo get_permalink($mgn_url); ?>" class="<?php echo ($mgn_url == $page_id)?'active':''; ?>"><?php echo get_the_title($mgn_url); ?></a></li>
							<?php
								}
							}
				    }

						if($benefits_url != ""): ?>
							<li><a href="<?php echo get_permalink($benefits_url); ?>" class="<?php echo ($benefits_url == $page_id)?'active':''; ?>"><?php echo get_the_title($benefits_url); ?></a></li>
						<?php endif;
						if($accreditation_url != ""): ?>
							<li><a href="<?php echo get_permalink($accreditation_url) ?>" class="<?php echo ($accreditation_url == $page_id)?'active':''; ?>"><?php echo get_the_title($accreditation_url); ?></a></li>
						<?php endif;
						if($opportunities_url != ""): ?>
							<li><a href="<?php echo get_permalink($opportunities_url) ?>" class="<?php echo ($opportunities_url == $page_id)?'active':''; ?>"><?php echo get_the_title($opportunities_url); ?></a></li>
						<?php endif;
						if($news_url != ""): ?>
							<li><a href="<?php echo get_permalink($news_url) ?>" class="<?php echo ($news_url == $page_id)?'active':''; ?>"><?php echo get_the_title($news_url); ?></a></li>
						<?php endif;
						break;
				}
			}
		?>


	</ul>
	<div class="clear"></div>
	<div class="ncoa_membership_tabs_bar"></div>
</div>

<?php while ( have_posts() ): the_post(); /* the loop */ ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('mmmm_template_body'); ?>>

	<div class="entry-content mmm-logged-content">

		<?php
			$content = apply_filters( 'the_content', get_the_content() );
			$content = str_replace( ']]>', ']]&gt;', $content );
			echo $content;
		 ?>

	</div>

</article>

<?php endwhile; ?>

<?php get_footer(); ?>
