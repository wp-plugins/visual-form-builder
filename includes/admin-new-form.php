<form method="post" id="visual-form-builder-new-form" action="">
	<input name="action" type="hidden" value="create_form" />
    <?php wp_nonce_field( "create_form" ); ?>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<h3>Create a form</h3>
	
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row"><label for="form-name"><?php _e( 'Name the form' , 'visual-form-builder'); ?></label></th>
				<td>
					<input type="text" value="Enter form name here" placeholder="Enter form name here" autofocus="autofocus" onfocus="this.select();" class="regular-text required" id="form-name" name="form_title" />
					<p class="description">Required. This name is used for admin purposes.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="form-email-sender-name"><?php _e( 'Your Name or Company' , 'visual-form-builder'); ?></label></th>
				<td>
					<input type="text" value="" placeholder="" class="regular-text" id="form-email-sender-name" name="form_email_from_name" />
					<p class="description">Optional - you can change this later</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="form-email-subject"><?php _e( 'E-mail Subject' , 'visual-form-builder'); ?></label></th>
				<td>
					<input type="text" value="" placeholder="" class="regular-text" id="form-email-subject" name="form_email_subject" />
					<p class="description">Optional - you can change this later</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="form-email-from"><?php _e( 'Reply-To E-mail' , 'visual-form-builder'); ?></label></th>
				<td>
					<input type="text" value="" placeholder="" class="regular-text" id="form-email-from" name="form_email_from" />
					<p class="description">Optional - you can change this later</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="form-email-to"><?php _e( 'E-mail To' , 'visual-form-builder'); ?></label></th>
				<td>
					<input type="text" value="" placeholder="" class="regular-text" id="form-email-to" name="form_email_to[]" />
					<p class="description">Optional - you can change this later</p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php 
		submit_button( __( 'Create Form', 'visual-form-builder' ) );
		endif;
	?>
</form>
<h3><?php _e( 'Need more help?' , 'visual-form-builder'); ?></h3>
<ol>
<li><?php _e( 'Click on the Help tab at the top of this page.' , 'visual-form-builder'); ?></li>
<li><a href="http://wordpress.org/extend/plugins/visual-form-builder/faq/">FAQ</a></li>
<li><a href="http://wordpress.org/support/plugin/visual-form-builder">Support Forum</a></li>
</ol>

<ul id="promote-vfb">
<li id="twitter"><?php _e( 'Follow me on Twitter' , 'visual-form-builder'); ?>: <a href="http://twitter.com/#!/matthewmuro">@matthewmuro</a></li>
</ul>
