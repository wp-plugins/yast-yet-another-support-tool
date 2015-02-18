<?php
global $YAST_tools;
if (isset($_GET['confirm']) && $_GET['confirm'] == 'options_saved') {
    ?>
    <div class="updated" id="yast_options_confirm"><p><strong><?php _e('YAST Support-Tickets options saved !', 'yast') ?></strong></p></div>
    <?php
}
$yast_options = $this->options;
if (in_array($YAST_tools->host(site_url()), $yast_options['trusted_hosts'])) {
    ?>
    <div class="error" id="yast_options_warning_trusted"><p><strong><?php _e('Do you really want to autorize any form of this webiste to send datas to YAST ? This might be a big security issue !', 'yast') ?></strong></p></div>
    <?php
}
?>
<div class="wrap">
    <div class="icon32" id="icon-yast"><br></div>
    <h2><?php _e('Support Tickets Options', 'yast'); ?></h2>
    <form name="form1" id="yast_options_form" method="post" action="admin-post.php">
	<input type="hidden" name="action" value="yastsaveoptions">
	<?php wp_nonce_field('ticket_set_options', 'ticket_set_options'); ?>
	<table>
	    <tr>
		<td colspan="2"><h3><span class="dashicons dashicons-email-alt"> </span> <?php _e('Alerts', 'yast'); ?></h3></td>
	    </tr>
	    <tr>
		<td><label for="alert_emails">
			<?php _e('Send email alert to:', 'yast') ?></label>
		</td>
		<td><input type="text" name="yast_options[alert_emails]" id="alert_emails" class="widefat" value="<?php echo implode(',', $yast_options['alert_emails']); ?>" /></td>
	    </tr>
	    <tr>
		<td colspan="2"><h3><span class="dashicons dashicons-sos"> </span> <?php _e('Support Form', 'yast'); ?></h3></td>
	    </tr>
	    <tr>
		<td><label for="default_type">
			<?php _e('Ticket type to use in adminbar', 'yast') ?></label>
		</td>
		<td><select name="yast_options[default_type]" id="default_type" class="widefat">
			<option value=''></option>
			<?php foreach ($this->types as $type) { ?>
    			<option value='<?php echo $type->slug ?>' <?php
			    if ($yast_options['default_type'] == $type->slug) {
				echo'selected';
			    }
			    ?>><?php echo $type->name ?></option>
				<?php } ?>
		    </select></td>
	    </tr>
	    <tr>
		<td><label for="force_ssl">
			<?php _e('Force SSL', 'yast') ?></label>
		</td>
		<td><select name="yast_options[force_ssl]" id="force_ssl">
			<option value='0' <?php echo ($yast_options['force_ssl'] == 0 ? 'selected' : '') ?>><?php _e('Nope', 'yast') ?></option>
			<option value='1' <?php echo ($yast_options['force_ssl'] == 1 ? 'selected' : '') ?>><?php _e('Yope', 'yast') ?></option>
		    </select>
		</td>
	    </tr>
	    <tr>
		<td><label for="trusted_hosts">
			<?php _e('Trusted hosts', 'yast') ?></label>
		</td>
		<td><textarea name="yast_options[trusted_hosts]" id="trusted_hosts" cols="60" class="widefat"><?php echo implode("\n", $yast_options['trusted_hosts']) ?></textarea>
		    <br><?php _e('One host per line, without http://, Datas sent from these sites will be registered without verification', 'yast') ?><br>
		    <?php _e('To integrate a form in one of these sites, use one the following codes. YOu can customize form by changing the URL parameters.', 'yast') ?><br>
		    <textarea cols="60" readonly>
<!-- Basic YAST Form  -->
<script src="<?php echo admin_url('admin-ajax.php') ?>?action=yast_form_js"></script>

<!-- Custom YAST Form  -->
<script src="<?php echo admin_url('admin-ajax.php') ?>?action=yast_form_js&autoload=no&visibility=private&username=anonymous&type=bug&title=Help%20I%20need%20somebody"></script>
		    </textarea><br>
		    <a href="https://wordpress.org/plugins/yast-yet-another-support-tool/#external" target="_blank"><?php _e('Click here to see more documentation about this feature.', 'yast') ?></a>
		</td>
	    </tr>


	    <?php if (is_multisite()): ?>
    	    <tr>
    		<td colspan="2"><h3><span class="dashicons dashicons-networking"> </span> <?php _e('Multisite', 'yast'); ?></h3></td>
    	    </tr>
    	    <tr>
    		<td><label for="support_site">
			    <?php _e('Use this site as Support site', 'yast') ?></label>
    		</td>
    		<td><select id="support_site" name="yast_options[support_site]" class="widefat">
			    <?php
			    $blogs_list = wp_get_sites(array('limit' => 0, 'deleted' => false, 'archived' => false, 'spam' => false));
			    foreach ($blogs_list as $blog):
				?>
				<option value="<?php echo $blog['blog_id'] ?>" <?php echo ($yast_options['support_site'] === $blog['blog_id'] ? 'selected' : ''); ?>>
				    <?php echo $blog['domain'] ?>
				</option>
			    <?php endforeach; ?>
    		    </select></td>
    	    </tr>
	    <?php endif; ?>

	    <tr>
		<td colspan="2"><h3><span class="dashicons dashicons-admin-appearance"> </span> <?php _e('Displaying', 'yast'); ?></h3></td>
	    </tr>
	    <tr>
		<td><label for="display_single_in_theme">
			<?php _e('Display tickets on front in the theme', 'yast') ?></label>
		</td>
		<td><select id="display_single_in_theme" name="yast_options[display_single_in_theme]">
			<option value="0" <?php selected($yast_options['display_single_in_theme'], 0); ?>><?php _e('Nope', 'yast') ?></option>
			<option value="1" <?php selected($yast_options['display_single_in_theme'], 1); ?>><?php _e('Yope', 'yast') ?></option>
		    </select></td>
	    </tr>
	</table>



	<p class="submit">
	    <input type="submit" value="<?php _e('Apply settings', 'yast'); ?>" class="button button-primary" id="submit" name="submit">
	</p>
    </form>
</div>
