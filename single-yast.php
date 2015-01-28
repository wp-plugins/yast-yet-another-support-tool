<?php
wp_reset_query();
if(!have_posts()){
    wp_die(__('Not ticket here, this is an error...','yast'));
}
get_header();
while (have_posts()) : the_post(); ?>
    <body class="container">
	<div id="content" class="site-content yast-front">
    	    <article>
		<div class="entry-content"><!-- A common content class -->
		    <?php $_YAST->single(get_the_ID()) ?>
		</div>
    	    </article>

	</div>
    </div>
<?php endwhile; get_footer() ?>