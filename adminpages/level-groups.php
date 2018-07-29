<?php
	//only admins can get this
	if(!function_exists("current_user_can") || (!current_user_can("manage_options") && !current_user_can("pmpro_membershiplevels")))
	{
		die(__("You do not have permissions to perform this action.", 'pmprommpu'));
	}

	global $wpdb, $msg, $msgt;

	if(isset($_REQUEST['edit']))
		$edit = intval($_REQUEST['edit']);
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
				echo __("Edit Level Group", 'pmprommpu');
			else
				echo __("Add New Level Group", 'pmprommpu');
		?>
	</h2>

	<div>
		<form action="" method="post" enctype="multipart/form-data">
			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label><?php _e('ID', 'pmprommpu');?>:</label></th>
					<td>
						<?php echo $levelgroup->id?>
					</td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label for="name"><?php _e('Name', 'pmprommpu');?>:</label></th>
					<td><input name="name" type="text" size="50" value="<?php echo esc_attr($levelgroup->name);?>"></td>
				</tr>

				<tr>
					<th scope="row" valign="top"><label for="name"><?php _e('Type', 'pmprommpu');?>:</label></th>
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
			<input name="save" type="submit" class="button-primary" value="<?php _e('Save Level Group', 'pmprommpu'); ?>">
			<input name="cancel" type="button" value="<?php _e('Cancel', 'pmprommpu'); ?>" onclick="location.href='<?php echo add_query_arg( 'page', 'pmpro-membershiplevels', get_admin_url(NULL, 'admin.php') ); ?>';">
		</p>
	</form>
	</div>

	<?php
	}
