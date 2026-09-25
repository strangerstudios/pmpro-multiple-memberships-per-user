<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_membershiplevels")))
	{
		die(esc_html__("You do not have permissions to perform this action.", 'pmpro-multiple-memberships-per-user'));
	}

	global $wpdb, $msg, $msgt;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing to the edit form.
	if(isset($_REQUEST['edit']))
		$edit = intval($_REQUEST['edit']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing to the edit form.
	else
		$edit = false;

	$levelgroup = array(
		'id' => '1',
		'name' => 'Default',
		'type' => 'multiple',
	);

	if($edit)
	{
	?>

	<h2>
		<?php
			if($edit > 0)
				echo esc_html__("Edit Level Group", 'pmpro-multiple-memberships-per-user');
			else
				echo esc_html__("Add New Level Group", 'pmpro-multiple-memberships-per-user');
		?>
	</h2>

	<div>
		<form action="" method="post" enctype="multipart/form-data">
			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php esc_html_e('ID', 'pmpro-multiple-memberships-per-user');?>:</label></th>
					<td>
						<?php echo esc_html( $levelgroup->id ); ?>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label for="name"><?php esc_html_e('Name', 'pmpro-multiple-memberships-per-user');?>:</label></th>
					<td><input name="name" type="text" size="50" value="<?php echo esc_attr($levelgroup->name);?>"></td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label for="name"><?php esc_html_e('Type', 'pmpro-multiple-memberships-per-user');?>:</label></th>
					<td>
						<select name="type" id="type">
							<option value="legacy">Users can only choose one level from this group.</option>
							<option value="multiple">Users can choose multiple levels from this group.</option>
							<!-- <option value="super">Super Levels: Users who select these levels will have all other memberships cancelled.</option> -->
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit topborder">
			<input name="save" type="submit" class="button-primary" value="<?php esc_attr_e('Save Level Group', 'pmpro-multiple-memberships-per-user'); ?>">
			<input name="cancel" type="button" value="<?php esc_attr_e('Cancel', 'pmpro-multiple-memberships-per-user'); ?>" onclick="location.href='<?php echo esc_url( add_query_arg( 'page', 'pmpro-membershiplevels', get_admin_url(NULL, 'admin.php') ) ); ?>';">
		</p>
	</form>
	</div>

	<?php
	}
