<?php
$conf = $this->options;

if (!$ticket || !isset($ticket->assignedto)) {
    $ticket = $this->get_ticket(isset($_GET['post']) && is_numeric($_GET['post']) ? $_GET['post'] : NULL);
}
$types = get_the_terms($ticket->ID, 'ticket_type');
if (!$types) {
    $types = array();
}
?>
<div class="ticket_description">
    <?php echo apply_filters('the_content', $ticket->post_content) ?>
</div>
<div class="yast_ticket_navigator">
    <ul>
	<?php echo $this->label($this->display_user($ticket->reporter), 'admin-users'); ?>


	<?php
	if ($ticket->page != NULL) {
	    echo $this->label('<a href="' . $ticket->page['url'] . '" target="_blank">' . $ticket->page['url'] . '</a>', 'admin-links');
	}
	?>
    </ul>

    <ul>

	<?php
	if (is_numeric($ticket->priority)) {
	    $level = $this->levels($ticket->priority);
	    echo $this->label($level['name'], 'info', 'yast-level-' . $ticket->priority);
	}
	?>

	<?php
	foreach ($types as $type) {
	    echo $this->label($type->name, 'tag');
	}
	?>


	<?php
	if ($ticket->visibility == 'private') {
	    echo $this->label(__('Private', 'yast'), 'lock', 'yast-private');
	}
	else {
	    echo $this->label(__('Public', 'yast'), 'lock', 'yast-public');
	}
	?>

	<?php
	if ($ticket->navigator != NULL):
	    global $YAST_tools;
	    $nav = $YAST_tools->navigator($ticket->navigator['userAgent']);

	    foreach ($nav as $prop => $vals) {
		$str = '';
		$class = '';
		foreach ($vals as $type => $val) {
		    $str.=$val.=' ';
		    $class.=' icon-yast-' . $val;
		}
		if (!empty($str)) {
		    echo $this->label($str, strtolower($class), strtolower('yast-' . $prop . ' ' . $str), NULL, 'li', 'icon-yast');
		}
	    }
	    ?>

	<?php endif ?>

    </ul>
</div>



<input id="comment_status" type="hidden" value="open" name="comment_status">
<input id="ping_status" type="hidden" value="open" name="ping_status">

<?php
$children = new WP_Query('post_type=yast&post_status=all&posts_per_page=-1&post_parent=' . $ticket->ID);
if (sizeof($children->posts) > 0) {
    ?>
    <h3><?php _e('Merged tickets :', 'yast') ?></h3>
    <?php
    foreach ($children->posts as $child) {
	$child = $this->get_ticket($child->ID);
	?>
	<div class="ticket_description">
	    <h4>
		<a href="<?php echo $child->lien ?>">
		<?php echo $child->post_title ?>
		</a>
	    <?php echo $this->display_user($child->reporter) ?>
	    </h4>
	<?php echo apply_filters('the_content', $child->post_content) ?>
	    <p><a href="<?php echo $child->page['url'] ?>" target="_blank"><?php echo $child->page['url'] ?></a></p>
	</div>
	<?php
    }
}