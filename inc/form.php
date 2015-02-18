<?php

$conf = $this->options;

$currentUrl = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$atts = shortcode_atts(array(
    'type' => $conf['default_type'],
    'only_known' => true,
    'class' => '',
    'force_ssl' => $conf['force_ssl'],
    'visibility' => false,
    'title' => __('Hi %s! Do you want to open a ticket about this page ?', 'yast'),
    'currentUrl' => 'http://' . $currentUrl,
    'username' => '',
	), $atts);

if ($atts['only_known'] === true && !is_user_logged_in()) {
    return;
}
if (is_multisite()) {
    switch_to_blog($this->options['support_site']);
}
$types = $this->default_types();
if (is_multisite()) {
    restore_current_blog();
}
$foradmin = (!isset($_GET['page']) || $_GET['page'] != 'yast_list') ? false : true;

global $current_user;
get_currentuserinfo();
$admin_url = admin_url();
if ($atts['force_ssl'] == true) {
    $admin_url = str_replace('http://', 'https://', $admin_url);
}
$ret = '';

if (isset($_REQUEST['form_message'])) {
    if ($_REQUEST['form_message'] == 'ok') {
	$ret.='<div class="yast_alert">' . __('Report succesfully registered !', 'yast') . '</div>';
    }
}
$ret.='
	  <form method="POST" action="' . $admin_url . 'admin-post.php?action=yastform" id="report_ticketform"  class="yast_reportform ' . $atts['class'] . '">'
	. '<input type="hidden" name="action" value="yastform" />
	  	   <input type="hidden" name="from" value="shortcode" />';
if (!empty($atts['username'])) {
    $ret.='<input type="hidden" name="user" value="' . $this->encrypt($atts['username']) . '" />';
}
if (!$foradmin) {
    $ret.=(!empty($atts['title']) ? '<h3>' . sprintf($atts['title'], $current_user->display_name) . '</h3>' : '') . ''
	    . '<input type="hidden" name="reporter" id="ticketrep_reporter" value="' . $this->current_reporter() . '" class="form-control" />';
}
else {
    wp_enqueue_script('user-suggest', admin_url('/js/user-suggest.min.js'), array('jquery'), false, true);
    $ret.='<div class="row">'
	    . '<div class="col-md-12">'
	    . '<label for="ticketrep_reporter">' . __('From:', 'yast') . '</label>'
	    . ' <input type="text" name="reporter" id="ticketrep_reporter" class="wp-suggest-user form-control" value="' . $this->current_reporter() . '" />'
	    . '</div>'
	    . '</div>';
    //<input name="assigned" data-id="<?php echo  $ticket->ID " type="text" id="yast_assigned" value="<?php echo  $ticket->assigned->user_login " />
}
$ret.='<div class="row">
	    <label class="col-md-4">
	  	' . __('Priority:', 'yast') . '
		<select name="priority" id="ticketrep_priority" class="form-control">';
$levels = $this->levels();
foreach ($levels as $id => $level) {
    $ret.=' <option value="' . $id . '" style="color:' . $level['color'] . '">' . $level['name'] . '</option>';
}
$ret.='</select>
		</label>

		<label class="col-md-4">
	  	' . __('Report type:', 'yast') . '
		<select name="type" id="ticketrep_type" class="form-control">';
foreach ($types as $type) {
    if ($foradmin || empty($atts['type']) || $atts['type'] == $type->slug) {
	if (sizeof($type->children) > 0) {
	    $ret.='<optgroup label="' . $type->name . '">';
	    foreach ($type->children as $child) {
		$ret.='<option value="' . $child->slug . '">' . $child->name . '</option>';
	    }
	    $ret.='</optgroup>';
	}
	else {
	    $ret.='<option value="' . $type->slug . '">' . $type->name . '</option>';
	}
    }
}
$ret.='</select>
	  	</label>';
if (!$atts['visibility']) {
    $ret.='<label class="col-md-4">
	 	' . __('Visibility:', 'yast') . '
		<select name="visibility" id="ticketrep_visibility" class="form-control">
		 	<option value="public">' . __('Public', 'yast') . '</option>
		 	<option value="private">' . __('Private', 'yast') . '</option>
		</select>
	  	</label>';
}
else {
    $ret.='<input type="hidden" name="visibility" value="' . $atts['visibility'] . '" />';
}
$ret.='</div>
	  <h3>' . __('Why do you  open a ticket ?', 'yast') . '</h3>'
	. '<div class="row">'
	. '<div class="col-md-12">'
	. '<label for="ticketrep_title">' . __('Title:', 'yast') . ' </label>'
	. '<input type="text" name="title" id="ticketrep_title" class="form-control" autocomplete="off">'
	. '</div>'
	. '</div>';
if ($content === NULL || (false === $contents = $this->extract_fields($content))) {
    $ret.='<div class="row">'
	    . '<div class="col-md-12">'
	    . '<label for="ticketrep_description">' . __('Description:', 'yast') . '</label>'
	    . '<textarea name="description" id="ticketrep_description" cols="50" rows="7" class="form-control"></textarea>'
	    . '</div></div>';
}
else {

    foreach ($contents as $field) {
	$html_field = '';
	if ($field['type'] == 'select') {
	    $html_field .= '<label>' . $field['label'] . ' <select name="description[' . $field['name'] . ']" ' . ($field['required'] ? 'required' : '') . '>';
	    foreach ($field['values'] as $value) {
		$html_field .= '<option value="' . $value . '">' . $value . '</option>';
	    }
	    $html_field .= '</select></label>';
	}
	elseif ($field['type'] == 'radio' || $field['type'] == 'checkbox') {
	    $html_field.='<fieldset><legend>' . $field['label'] . '</legend>';
	    foreach ($field['values'] as $value) {
		$html_field .= '<label><input type="' . $field['type'] . '" name="description[' . $field['name'] . ']' . ($field['type'] == 'checkbox' ? '[' . $value . ']' : '') . '" ' . ($field['required'] ? 'required' : '') . ' ';
		$html_field .= ' value="' . $value . '">';
		$html_field .= ' ' . $value . '</label> ';
	    }
	    $html_field .= '</fieldset>';
	}
	elseif ($field['type'] == 'textarea') {
	    $html_field .= '<label>' . $field['label'] . ' <textarea name="description[' . $field['name'] . ']" ' . ($field['required'] ? 'required' : '') . '>' . $field['values'] . '</textarea></label>';
	}
	else {
	    $html_field .= '<label>' . $field['label'] . ' <input type="' . $field['type'] . '" name="description[' . $field['name'] . ']" value="' . $field['values'] . '" ' . ($field['required'] ? 'required' : '') . '></label>';
	}
	$html_field .= '';
	$content = str_replace($field['pattern'], $html_field, $content);
    }
    $ret.=$content;
}
$ret.='
		<div id="yast_techsub">
	   <h3>' . __('Technical details:', 'yast') . '</h3>
	   <p>' . __('These informations will be sent with your report:', 'yast') . '</p>
	   </div>
	   <div id="yast_techsubinfos">
	  <div class="row">
	    <div class="col-md-12">
	  	<label class="input-group">
	  		<span class="input-group-addon">' . __('Page URL:', 'yast') . '</span>
	  		<input type="text" name="page_url" id="ticketrep_page_url"  class="form-control" value="' . $atts['currentUrl'] . '" ' . ($foradmin ? '' : 'readonly') . '>
	  	</label>
	    </div>
	  </div>
	  <div class="row">
	    <div class="col-md-12">
	  	<label class="input-group">
	  		<span class="input-group-addon">' . __('Variables:', 'yast') . '</span>
	  		<textarea name="page_post" id="ticketrep_page_post" cols="60" rows="5"  class="form-control" ' . ($foradmin ? '' : 'readonly') . '>' . esc_attr(stripslashes(serialize($_POST))) . '</textarea>
	  	</label>
	  </div>
	  </div>
	  <div class="row">
	    <div class="col-md-12">
	  	<label class="input-group">
	  		<span class="input-group-addon">' . __('Navigator:', 'yast') . '</span>
	  		<input type="text" name="navigator_userAgent" id="ticketrep_navigator"  class="form-control" value="' . esc_attr($_SERVER['HTTP_USER_AGENT']) . '"  ' . ($foradmin ? '' : 'readonly') . '>
	  	</label>
	  </div>
	  </div>
	 </div>
	<div class="row">
		<div class="col-md-12">
			<input type="submit" value="' . __('Submit', 'yast') . '" class="button button-primary button-large btn btn-primary">
		</div>
	</div>
    </form>';
return $ret;
