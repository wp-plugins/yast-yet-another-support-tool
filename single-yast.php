<?php
wp_reset_query();
if(!have_posts()){
    wp_die(__('Not ticket here, this is an error...','yast'));
}
the_post();
if($_YAST->options['display_single_in_theme']=='yope'){
    get_header();
}
else{
	wp_enqueue_style('bootstrap', plugins_url('/css/bootstrap.min.css', __FILE__), false, null);

	wp_enqueue_script('jquery');
	wp_enqueue_script('bootstrap', plugins_url('/js/bootstrap.min.js', __FILE__), array('jquery'), false, true);
    ?><!DOCTYPE html>
<html lang="fr" xml:lang="fr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head() ?>
    <title><?php _e('Support ticket', 'yast'); ?> <?php the_ID() ?> : <?php the_title(); ?></title>
  </head>
    <body class="container">
<?php
}
?>
	<div id="content" class="site-content yast-front">
    	    <article>
		<div class="entry-content"><!-- A common content class -->
		    <?php $_YAST->single(get_the_ID()) ?>
		</div>
    	    </article>

	</div>
<?php
if($_YAST->options['display_single_in_theme']=='yope'){
    get_footer();
}
else{
    wp_footer();
    ?>
    </body>
</html>
    <?php
}