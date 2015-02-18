<?php
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: text/javascript");
header("Content-Disposition: inline; filename=yast-form.js;");
header("Content-Transfer-Encoding: binary");

$atts = array(
    'only_known' => false,
    'currentUrl' => '"+ document.location +"'
	);
if(false !== $type = \filter_input(INPUT_GET, 'type')){
    $atts['type'] = $type;
}
if(false !== $title = \filter_input(INPUT_GET, 'title')){
    $atts['title'] = $title;
}
if(false !== $username = \filter_input(INPUT_GET, 'username')){
    $atts['username'] = $username;
}
if(false !== $visibility = \filter_input(INPUT_GET, 'visibility')){
    $atts['visibility'] = $visibility;
}

$html= addslashes(str_replace(array("\n","\t","  "),' ',$this->form($atts,\filter_input(INPUT_GET, 'content'))));
$html = str_replace('\"+ document.location +\"','"+ document.location +"',$html);
?>
var yast_head = document.head || document.getElementsByTagName('head')[0];
var yast_cssLink;
var yast_body;
var yast_Element;

yast_cssLink = document.createElement("link");
yast_cssLink.href = "<?php echo str_replace(array('http:','https:'),'',plugins_url('/css/style.css', __FILE__)) ?>";
yast_cssLink.setAttribute('rel', 'stylesheet');
yast_cssLink.setAttribute('type', 'text/css');
yast_cssLink.setAttribute('media', 'all');

yast_head.appendChild(yast_cssLink);


// Cross-browser wrapper for DOMContentLoaded
// Author: Diego Perini (diego.perini at gmail.com)
// https://github.com/dperini/ContentLoaded
// @win window reference
// @fn function reference
function contentLoaded(win, fn) {
    var done = false, top = true,
    doc = win.document,
    root = doc.documentElement,
    modern = doc.addEventListener,
    add = modern ? 'addEventListener' : 'attachEvent',
    rem = modern ? 'removeEventListener' : 'detachEvent',
    pre = modern ? '' : 'on',
    init = function(e) {
        if (e.type == 'readystatechange' && doc.readyState != 'complete') return;
        (e.type == 'load' ? win : doc)[rem](pre + e.type, init, false);
        if (!done && (done = true)) fn.call(win, e.type || e);
    },
    poll = function() {
        try { root.doScroll('left'); } catch(e) { setTimeout(poll, 50); return; }
        init('poll');
    };
    if (doc.readyState == 'complete') fn.call(win, 'lazy');
    else {
        if (!modern && root.doScroll) {
            try { top = !win.frameElement; } catch(e) { }
            if (top) poll();
        }
        doc[add](pre + 'DOMContentLoaded', init, false);
        doc[add](pre + 'readystatechange', init, false);
        win[add](pre + 'load', init, false);
    }
}
function yast_pop_create(){
    yast_Element = document.createElement('div');
    yast_Element.id = 'yast-support-form';
    yast_Element.innerHTML = "<button onclick=\"yast_pop_close()\" class=\"yast_pop_close_button button btn btn-default btn-sm pull-right\"><?php _e('Cancel','yast')?></button><?php echo $html ?>";
    yast_body.appendChild(yast_Element);
}
function yast_pop_close(){
    document.getElementById('yast-support-form').style.display='none';
}
function yast_pop_open(){
    if(!document.getElementById('yast-support-form')){
	yast_pop_create();
    }
    if(document.getElementById('yast-support-form')){
	document.getElementById('yast-support-form').style.display='block';
	return;
    }
}

contentLoaded(window, function(event) {
    yast_body = document.body || document.getElementsByTagName('body')[0];

    <?php if('no' != \filter_input(INPUT_GET, 'autoload')): ?>
    yast_pop_create();
    <?php endif; ?>
    var yast_buttons = document.getElementsByClassName('yast-dist-support-button');
    if(yast_buttons.length==0){
	var yast_button = document.createElement('a');
	yast_button.id = 'yast-dist-support-button-generated';
	yast_button.setAttribute('class', 'yast-dist-support-button');
	yast_button.innerHTML = "<?php _e('Support !','yast') ?>";
	yast_body.appendChild(yast_button);
	yast_buttons = document.getElementsByClassName('yast-dist-support-button');
    }
    for(i in yast_buttons){
	yast_buttons[i].onclick=function(){
	    yast_pop_open();
	    return false;
	}
    }
});
<?php
exit;