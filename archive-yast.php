<?php
/**
 * @package WordPress
 * @subpackage Genese
 */
get_header();
?>
    <?php /* Start the Loop */ ?>
    <?php wp_reset_query();
    query_posts($_YAST->query());
    ?>
    <div class="container">
	<div id="content" class="site-content yast-front">
	    <div class="entry-content">
		<h1><?php _e('Recent public tickets','yast') ?></h1>
		<?php $_YAST->liste_paginate($wp_query); ?>
	    <ol class="yast-list">
<?php while (have_posts()) : the_post(); ?>
    		<li>
    		    <article id="post-<?php the_ID(); ?>">
    			<a href="<?php the_permalink() ?>" title="<?php the_title ?>">
    			    <header>
    				<h3 class="entry-title"><?php the_title(); ?></h3>
				<time><?php the_date()?></time>
    			    </header>
    			    <div class="entry-content">
			    <?php the_excerpt(); ?>
    			    </div>
    			</a>
    		    </article><!-- #post-<?php the_ID(); ?> -->
    		</li>
<?php endwhile; ?>
	    </ol>
	    </div>
	</div>
    </div>
<?php get_footer(); ?>