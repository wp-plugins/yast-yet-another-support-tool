<?php
if (!$ticket ||!isset($ticket->assignedto)){
$ticket = $this->get_ticket(isset($_GET['post'])&& is_numeric($_GET['post']) ? $_GET['post']: NULL);
}
?>
<?php if ($ticket->navigator != ''): ?>
<div class="ticket_navigator">
    <h5><?php _e('Environment', 'yast') ?></h5>
    <p><?php _e('User Agent:', 'yast') ?> <?php echo $ticket->navigator['userAgent'] ?></p>
</div>
<?php endif ?>

<?php if ($ticket->page != NULL): ?>
<div class="ticket_case_url">
    <h5><?php _e('Public URL', 'yast') ?></h5>
    <p><a href="<?php echo $ticket->front_link ?>"><?php echo $ticket->front_link ?></a></p>
    <h5><?php _e('Case URL', 'yast') ?></h5>
    <p><a href="<?php echo $ticket->page['url'] ?>" target="ticket_preview" id="ticket_preview_link"><?php echo $ticket->page['url'] ?></a></p>
    <?php if (sizeof($ticket->page['post']) > 1): ?>
    <b>POST:</b>
    <pre><?php print_r($ticket->page['post']) ?></pre>
    <?php else: ?>
    <p><?php _e('No POST variables', 'yast') ?></p>
    <?php endif ?>
    <iframe src="about:blank" height="200" width="100%" name="ticket_preview" id="ticket_preview_iframe"></iframe>
</div>
<?php endif;