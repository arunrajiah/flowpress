<?php
/**
 * Template: Add / Edit Recipe admin screen.
 *
 * Variables available:
 *   $recipe  FlowPress_Recipe|null  — null when creating a new recipe.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin/views
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$is_new      = null === $recipe;
$page_title  = $is_new ? __( 'Add Recipe', 'flowpress' ) : __( 'Edit Recipe', 'flowpress' );
$title       = $is_new ? '' : $recipe->get_title();
$description = $is_new ? '' : $recipe->get_description();
$recipe_id   = $is_new ? 0 : $recipe->get_id();

// Trigger / action state.
$saved_trigger      = $is_new ? '' : (string) $recipe->get_trigger();
$saved_actions      = $is_new ? array() : $recipe->get_actions();
$first_action       = $saved_actions[0] ?? array();
$saved_action_type  = $first_action['type'] ?? '';
$saved_action_cfg   = $first_action['config'] ?? array();

$all_triggers = FlowPress_Trigger_Registry::all();
$all_actions  = FlowPress_Action_Registry::all();

// Audit log.
$audit_entries = $is_new ? array() : FlowPress_Audit_Log::get_for_recipe( $recipe_id, 20 );

// Run log.
$run_entries = $is_new ? array() : FlowPress_Run_Log::get_for_recipe( $recipe_id, 30 );

// Notice.
$notice = isset( $_GET['fp_notice'] ) ? sanitize_key( $_GET['fp_notice'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap flowpress-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Recipe saved.', 'flowpress' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . ( $is_new ? 'flowpress-new' : 'flowpress-edit' ) . ( $recipe_id ? '&recipe_id=' . $recipe_id : '' ) ) ); ?>" id="fp-edit-recipe-form">
		<?php wp_nonce_field( 'flowpress_save_recipe' ); ?>
		<input type="hidden" name="recipe_id" value="<?php echo absint( $recipe_id ); ?>">

		<!-- ── Recipe basics ──────────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Recipe Details', 'flowpress' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="fp_title"><?php esc_html_e( 'Name', 'flowpress' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input
							type="text"
							id="fp_title"
							name="fp_title"
							class="regular-text"
							value="<?php echo esc_attr( $title ); ?>"
							required
							placeholder="<?php esc_attr_e( 'e.g. Notify me on new post', 'flowpress' ); ?>"
						>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fp_description"><?php esc_html_e( 'Description', 'flowpress' ); ?></label>
					</th>
					<td>
						<textarea
							id="fp_description"
							name="fp_description"
							class="large-text"
							rows="2"
							placeholder="<?php esc_attr_e( 'Optional — helps you remember what this recipe does.', 'flowpress' ); ?>"
						><?php echo esc_textarea( $description ); ?></textarea>
					</td>
				</tr>

				<?php if ( ! $is_new ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'flowpress' ); ?></th>
						<td>
							<span class="fp-status fp-status--<?php echo esc_attr( str_replace( 'fp_', '', $recipe->get_status() ) ); ?>">
								<?php echo esc_html( $recipe->get_status_label() ); ?>
							</span>

							<?php if ( $recipe->is_enabled() ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'disable', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary" style="margin-left:8px;">
									<?php esc_html_e( 'Disable', 'flowpress' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'enable', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary" style="margin-left:8px;">
									<?php esc_html_e( 'Enable', 'flowpress' ); ?>
								</a>
							<?php endif; ?>

							<?php if ( ! $recipe->is_complete() ) : ?>
								<p class="description fp-incomplete-warning">
									<?php esc_html_e( 'This recipe is incomplete — it has no trigger or actions. It will not run until configured.', 'flowpress' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- ── Trigger ────────────────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Trigger', 'flowpress' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Choose the event that starts this recipe.', 'flowpress' ); ?></p>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="fp_trigger"><?php esc_html_e( 'When…', 'flowpress' ); ?></label>
					</th>
					<td>
						<select id="fp_trigger" name="fp_trigger">
							<option value=""><?php esc_html_e( '— Select a trigger —', 'flowpress' ); ?></option>
							<?php foreach ( $all_triggers as $trigger_obj ) : ?>
								<option
									value="<?php echo esc_attr( $trigger_obj->get_type() ); ?>"
									<?php selected( $saved_trigger, $trigger_obj->get_type() ); ?>
								>
									<?php echo esc_html( $trigger_obj->get_label() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php if ( $saved_trigger && isset( $all_triggers[ $saved_trigger ] ) ) : ?>
							<p class="description"><?php echo esc_html( $all_triggers[ $saved_trigger ]->get_description() ); ?></p>

							<details style="margin-top:8px;">
								<summary><?php esc_html_e( 'Available tokens', 'flowpress' ); ?></summary>
								<table class="fp-token-table widefat striped" style="max-width:500px;margin-top:8px;">
									<thead><tr><th><?php esc_html_e( 'Token', 'flowpress' ); ?></th><th><?php esc_html_e( 'Description', 'flowpress' ); ?></th></tr></thead>
									<tbody>
										<?php foreach ( $all_triggers[ $saved_trigger ]->get_tokens() as $token => $label ) : ?>
											<tr>
												<td><code>{{<?php echo esc_html( $token ); ?>}}</code></td>
												<td><?php echo esc_html( $label ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</details>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- ── Actions ────────────────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Action', 'flowpress' ); ?></h2>
		<p class="description"><?php esc_html_e( 'What should happen when the trigger fires?', 'flowpress' ); ?></p>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="fp_action_type"><?php esc_html_e( 'Do this…', 'flowpress' ); ?></label>
					</th>
					<td>
						<select id="fp_action_type" name="fp_action_type">
							<option value=""><?php esc_html_e( '— Select an action —', 'flowpress' ); ?></option>
							<?php foreach ( $all_actions as $action_obj ) : ?>
								<option
									value="<?php echo esc_attr( $action_obj->get_type() ); ?>"
									<?php selected( $saved_action_type, $action_obj->get_type() ); ?>
								>
									<?php echo esc_html( $action_obj->get_label() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<?php if ( $saved_action_type && isset( $all_actions[ $saved_action_type ] ) ) : ?>
					<?php foreach ( $all_actions[ $saved_action_type ]->get_fields() as $field ) : ?>
						<tr>
							<th scope="row">
								<label for="fp_action_cfg_<?php echo esc_attr( $field['key'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php if ( ! empty( $field['required'] ) ) : ?>
										<span class="required">*</span>
									<?php endif; ?>
								</label>
							</th>
							<td>
								<?php if ( 'textarea' === $field['type'] ) : ?>
									<textarea
										id="fp_action_cfg_<?php echo esc_attr( $field['key'] ); ?>"
										name="fp_action_cfg[<?php echo esc_attr( $field['key'] ); ?>]"
										class="large-text"
										rows="5"
										placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
										<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
									><?php echo esc_textarea( $saved_action_cfg[ $field['key'] ] ?? '' ); ?></textarea>
								<?php else : ?>
									<input
										type="<?php echo esc_attr( $field['type'] ); ?>"
										id="fp_action_cfg_<?php echo esc_attr( $field['key'] ); ?>"
										name="fp_action_cfg[<?php echo esc_attr( $field['key'] ); ?>]"
										class="regular-text"
										value="<?php echo esc_attr( $saved_action_cfg[ $field['key'] ] ?? '' ); ?>"
										placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
										<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
									>
								<?php endif; ?>
								<?php if ( ! empty( $field['help'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['help'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- ── Submit ─────────────────────────────────────────────────────── -->
		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_new ? esc_html__( 'Create Recipe', 'flowpress' ) : esc_html__( 'Save Recipe', 'flowpress' ); ?>
			</button>

			<?php if ( ! $is_new ) : ?>
				<button type="button" class="button button-secondary" id="fp-test-recipe" data-recipe-id="<?php echo absint( $recipe_id ); ?>">
					<?php esc_html_e( 'Test Recipe', 'flowpress' ); ?>
				</button>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'duplicate', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Duplicate', 'flowpress' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'delete', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-link-delete fp-delete-link">
					<?php esc_html_e( 'Delete Recipe', 'flowpress' ); ?>
				</a>
			<?php endif; ?>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=flowpress' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Cancel', 'flowpress' ); ?>
			</a>
		</p>
	</form>

	<!-- ── Test run result area ───────────────────────────────────────────── -->
	<?php if ( ! $is_new ) : ?>
		<div id="fp-test-result" style="display:none;" class="notice fp-test-result-box">
			<strong><?php esc_html_e( 'Test Run Result', 'flowpress' ); ?></strong>
			<div id="fp-test-result-content"></div>
		</div>
	<?php endif; ?>

	<!-- ── Run Log ────────────────────────────────────────────────────────── -->
	<?php if ( ! $is_new && $run_entries ) : ?>
		<hr>
		<h2><?php esc_html_e( 'Recent Runs', 'flowpress' ); ?></h2>
		<table class="widefat striped fp-run-log-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Status', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Trigger', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Details', 'flowpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $run_entries as $run ) : ?>
					<?php
					$results    = json_decode( $run->action_results, true );
					$status_map = array(
						'success' => 'enabled',
						'failed'  => 'disabled',
						'skipped' => 'draft',
						'dry_run' => 'draft',
					);
					$status_css = $status_map[ $run->status ] ?? 'draft';
					?>
					<tr>
						<td><?php echo esc_html( get_date_from_gmt( $run->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
						<td>
							<span class="fp-status fp-status--<?php echo esc_attr( $status_css ); ?>">
								<?php echo esc_html( $run->is_dry_run ? __( 'Test', 'flowpress' ) : ucfirst( $run->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $run->trigger_type ); ?></td>
						<td>
							<?php if ( $run->error_message ) : ?>
								<span class="fp-error"><?php echo esc_html( $run->error_message ); ?></span>
							<?php elseif ( is_array( $results ) ) : ?>
								<?php foreach ( $results as $r ) : ?>
									<div><?php echo esc_html( $r['type'] . ': ' . ( $r['message'] ?? '' ) ); ?></div>
								<?php endforeach; ?>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php elseif ( ! $is_new ) : ?>
		<hr>
		<h2><?php esc_html_e( 'Recent Runs', 'flowpress' ); ?></h2>
		<p><?php esc_html_e( 'No runs yet. Enable the recipe and trigger its event, or use the Test button above.', 'flowpress' ); ?></p>
	<?php endif; ?>

	<!-- ── Audit History ──────────────────────────────────────────────────── -->
	<?php if ( ! $is_new && $audit_entries ) : ?>
		<hr>
		<h2><?php esc_html_e( 'Audit History', 'flowpress' ); ?></h2>
		<table class="widefat striped fp-audit-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Author', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Action', 'flowpress' ); ?></th>
					<th><?php esc_html_e( 'Details', 'flowpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $audit_entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( get_date_from_gmt( $entry->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
						<td>
							<?php
							$user = get_user_by( 'id', $entry->user_id );
							echo $user ? esc_html( $user->display_name ) : esc_html__( 'System', 'flowpress' );
							?>
						</td>
						<td><?php echo esc_html( $entry->action ); ?></td>
						<td><?php echo esc_html( $entry->summary ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
