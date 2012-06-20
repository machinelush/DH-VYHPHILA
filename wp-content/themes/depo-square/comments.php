<?php // Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if ( post_password_required() ) { ?>
		<p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.', 'depo-squared'); ?></p>
	<?php
		return;
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'alt';
?>

<!-- You can start editing here. -->

<?php if ( have_comments() ) : ?>
	<h3 id="comments"><?php comments_number(__('No Responses', 'depo-squared'), __('One Response', 'depo-squared'), __('% Responses', 'depo-squared'));?> <?php printf(__('to &#8220;%s&#8221;', 'depo-squared'), the_title('', '', false)); ?></h3>

	<ol class="commentlist">
		<?php wp_list_comments(array('callback' => 'depo_squared_comment')); ?>
	</ol>

	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
	</div>
<?php endif; ?>

<?php if ( have_comments() && !comments_open() ) { ?>
<p class="nocomments"><?php _e('Comments are closed.', 'depo-squared'); ?></p>
<?php } ?>

<?php if ( comments_open() ) : ?>

<?php comment_form(); ?>

<?php endif; // if you delete this the sky will fall on your head ?>
