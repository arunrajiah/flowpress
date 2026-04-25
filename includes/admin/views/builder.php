<?php
/**
 * Template: Visual Recipe Builder.
 *
 * Variables available:
 *   $recipe  FlowPress_Recipe|null
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin/views
 * @since      0.4.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$is_new      = null === $recipe;
$page_title  = $is_new ? __( 'New Recipe', 'flowpress' ) : __( 'Edit Recipe', 'flowpress' );
$recipe_id   = $is_new ? 0 : $recipe->get_id();

$saved_trigger        = $is_new ? '' : (string) $recipe->get_trigger();
$saved_trigger_config = $is_new ? array() : $recipe->get_trigger_config();
$saved_actions        = $is_new ? array() : $recipe->get_actions();
$saved_conditions     = $is_new ? array( 'logic' => 'AND', 'items' => array() ) : $recipe->get_conditions();
$operators        = FlowPress_Condition_Evaluator::get_operators();
$valueless_ops    = FlowPress_Condition_Evaluator::get_valueless_operators();

$run_entries   = $is_new ? array() : FlowPress_Run_Log::get_for_recipe( $recipe_id, 30 );
$audit_entries = $is_new ? array() : FlowPress_Audit_Log::get_for_recipe( $recipe_id, 20 );

$notice = isset( $_GET['fp_notice'] ) ? sanitize_key( $_GET['fp_notice'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$form_action = admin_url(
	'admin.php?page=' . ( $is_new ? 'flowpress-new' : 'flowpress-edit' ) . ( $recipe_id ? '&recipe_id=' . $recipe_id : '' )
);
?>
<div class="wrap flowpress-wrap fp-builder-wrap">

	<?php require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/promo-banner.php'; ?>

	<div class="fp-builder-header">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=flowpress' ) ); ?>" class="fp-back-link">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php esc_html_e( 'All Recipes', 'flowpress' ); ?>
		</a>
		<h1 class="fp-builder-title">
			<?php echo esc_html( $page_title ); ?>
			<?php if ( ! $is_new ) : ?>
				<span class="fp-status fp-status--<?php echo esc_attr( str_replace( 'fp_', '', $recipe->get_status() ) ); ?> fp-title-badge">
					<?php echo esc_html( $recipe->get_status_label() ); ?>
				</span>
			<?php endif; ?>
		</h1>
	</div>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Recipe saved.', 'flowpress' ); ?></p></div>
	<?php endif; ?>

	<form
		method="post"
		action="<?php echo esc_url( $form_action ); ?>"
		id="fp-builder-form"
		novalidate
	>
		<?php wp_nonce_field( 'flowpress_save_recipe' ); ?>
		<input type="hidden" name="recipe_id" value="<?php echo absint( $recipe_id ); ?>">
		<input type="hidden" name="fp_trigger" id="fp-trigger-value" value="<?php echo esc_attr( $saved_trigger ); ?>">

		<!-- Saved actions are serialised here at submit time by JS; pre-populated on load. -->
		<div id="fp-actions-hidden-inputs" aria-hidden="true"></div>

		<div class="fp-builder-layout">

			<!-- ══ LEFT PANEL ══════════════════════════════════════════════ -->
			<div class="fp-builder-main">

				<!-- Recipe name & description -->
				<section class="fp-card fp-recipe-meta-card" aria-label="<?php esc_attr_e( 'Recipe details', 'flowpress' ); ?>">
					<div class="fp-card-body">
						<div class="fp-field">
							<label for="fp_title" class="fp-label">
								<?php esc_html_e( 'Recipe name', 'flowpress' ); ?>
								<span class="fp-required" aria-hidden="true">*</span>
							</label>
							<input
								type="text"
								id="fp_title"
								name="fp_title"
								class="fp-input"
								value="<?php echo esc_attr( $is_new ? '' : $recipe->get_title() ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. Email me when a post is published', 'flowpress' ); ?>"
								required
								aria-required="true"
							>
							<div class="fp-field-error" id="fp-error-title" role="alert" aria-live="polite"></div>
						</div>

						<div class="fp-field">
							<label for="fp_description" class="fp-label">
								<?php esc_html_e( 'Description', 'flowpress' ); ?>
								<span class="fp-optional"><?php esc_html_e( '(optional)', 'flowpress' ); ?></span>
							</label>
							<textarea
								id="fp_description"
								name="fp_description"
								class="fp-input fp-textarea"
								rows="2"
								placeholder="<?php esc_attr_e( 'What does this recipe do?', 'flowpress' ); ?>"
							><?php echo esc_textarea( $is_new ? '' : $recipe->get_description() ); ?></textarea>
						</div>
					</div>
				</section>

				<!-- Step 1: Trigger -->
				<section class="fp-card fp-step-card" id="fp-trigger-section" aria-label="<?php esc_attr_e( 'Step 1: Trigger', 'flowpress' ); ?>">
					<div class="fp-step-header">
						<span class="fp-step-number" aria-hidden="true">1</span>
						<div class="fp-step-heading">
							<h2 class="fp-step-title"><?php esc_html_e( 'When this happens…', 'flowpress' ); ?></h2>
							<p class="fp-step-subtitle"><?php esc_html_e( 'Choose the event that starts this recipe.', 'flowpress' ); ?></p>
						</div>
					</div>

					<div class="fp-card-body">
						<!-- Selected trigger display (visible once chosen) -->
						<div id="fp-selected-trigger" class="fp-selected-item" style="display:none;" role="region" aria-label="<?php esc_attr_e( 'Selected trigger', 'flowpress' ); ?>">
							<div class="fp-selected-item-inner">
								<span class="fp-item-icon dashicons" id="fp-selected-trigger-icon" aria-hidden="true"></span>
								<div class="fp-item-text">
									<strong id="fp-selected-trigger-label"></strong>
									<span id="fp-selected-trigger-desc" class="fp-item-desc"></span>
								</div>
								<button type="button" class="fp-change-btn" id="fp-change-trigger" aria-label="<?php esc_attr_e( 'Change trigger', 'flowpress' ); ?>">
									<?php esc_html_e( 'Change', 'flowpress' ); ?>
								</button>
							</div>
						</div>

						<!-- Incoming webhook config (shown only when that trigger is selected) -->
						<div id="fp-webhook-config" style="display:none;" class="fp-webhook-config-panel">
							<div class="fp-field">
								<label for="fp_trigger_config_slug" class="fp-label">
									<?php esc_html_e( 'Webhook Slug', 'flowpress' ); ?>
									<span class="fp-required" aria-hidden="true">*</span>
								</label>
								<input
									type="text"
									id="fp_trigger_config_slug"
									name="fp_trigger_config_slug"
									class="fp-input"
									value="<?php echo esc_attr( $saved_trigger_config['slug'] ?? '' ); ?>"
									placeholder="my-recipe-slug"
									pattern="[a-z0-9\-]+"
								>
								<p class="description">
									<?php
									$webhook_base = rest_url( 'flowpress/v1/webhook/' );
									printf(
										/* translators: %s: webhook base URL */
										esc_html__( 'Your webhook URL: %s{slug}', 'flowpress' ),
										'<code>' . esc_html( $webhook_base ) . '</code>'
									);
									?>
								</p>
							</div>
						</div>

						<!-- Trigger catalogue (hidden once one is chosen) -->
						<div id="fp-trigger-catalogue" class="fp-catalogue" role="listbox" aria-label="<?php esc_attr_e( 'Trigger catalogue', 'flowpress' ); ?>">
							<div class="fp-search-wrap">
								<label for="fp-trigger-search" class="screen-reader-text"><?php esc_html_e( 'Search triggers', 'flowpress' ); ?></label>
								<span class="dashicons dashicons-search fp-search-icon" aria-hidden="true"></span>
								<input
									type="search"
									id="fp-trigger-search"
									class="fp-search-input"
									placeholder="<?php esc_attr_e( 'Search triggers…', 'flowpress' ); ?>"
									autocomplete="off"
								>
							</div>
							<div id="fp-trigger-list" class="fp-catalogue-list" role="presentation">
								<!-- Trigger cards injected by JS -->
							</div>
							<p id="fp-trigger-no-results" class="fp-no-results" style="display:none;" aria-live="polite">
								<?php esc_html_e( 'No triggers match your search.', 'flowpress' ); ?>
							</p>
						</div>
						<div class="fp-field-error" id="fp-error-trigger" role="alert" aria-live="polite"></div>
					</div>
				</section>

				<!-- Step 1.5: Conditions (optional) -->
				<section class="fp-card fp-step-card fp-conditions-card" id="fp-conditions-section" aria-label="<?php esc_attr_e( 'Conditions (optional)', 'flowpress' ); ?>">
					<div class="fp-step-header">
						<span class="fp-step-number fp-step-number--optional" aria-hidden="true"><span class="dashicons dashicons-filter"></span></span>
						<div class="fp-step-heading">
							<h2 class="fp-step-title"><?php esc_html_e( 'Only run if…', 'flowpress' ); ?> <span class="fp-optional"><?php esc_html_e( '(optional)', 'flowpress' ); ?></span></h2>
							<p class="fp-step-subtitle"><?php esc_html_e( 'Add conditions to restrict when this recipe runs. Leave empty to always run.', 'flowpress' ); ?></p>
						</div>
					</div>

					<div class="fp-card-body">
						<!-- Logic selector (AND / OR) -->
						<div class="fp-conditions-logic-row" id="fp-conditions-logic-row" style="<?php echo empty( $saved_conditions['items'] ) ? 'display:none;' : ''; ?>">
							<span><?php esc_html_e( 'Match', 'flowpress' ); ?></span>
							<select name="fp_conditions_logic" id="fp-conditions-logic" aria-label="<?php esc_attr_e( 'Logic operator', 'flowpress' ); ?>">
								<option value="AND" <?php selected( strtoupper( $saved_conditions['logic'] ?? 'AND' ), 'AND' ); ?>><?php esc_html_e( 'ALL conditions (AND)', 'flowpress' ); ?></option>
								<option value="OR"  <?php selected( strtoupper( $saved_conditions['logic'] ?? 'AND' ), 'OR' ); ?>><?php esc_html_e( 'ANY condition (OR)', 'flowpress' ); ?></option>
							</select>
						</div>

						<!-- Condition rows -->
						<div id="fp-conditions-list" role="list">
							<?php foreach ( $saved_conditions['items'] as $cond_idx => $cond_item ) : ?>
								<div class="fp-condition-row" role="listitem">
									<select
										name="fp_conditions[<?php echo absint( $cond_idx ); ?>][field]"
										class="fp-cond-field"
										aria-label="<?php esc_attr_e( 'Condition field', 'flowpress' ); ?>"
									>
										<option value=""><?php esc_html_e( '— select field —', 'flowpress' ); ?></option>
										<?php
										// Populate tokens from saved trigger if available.
										// get_tokens() returns [['token'=>'slug','label'=>'Label'], ...].
										$builder_trigger_obj = $saved_trigger ? FlowPress_Trigger_Registry::get( $saved_trigger ) : null;
										$builder_tokens      = $builder_trigger_obj ? $builder_trigger_obj->get_tokens() : array();
										foreach ( $builder_tokens as $tok ) :
											?>
											<option value="<?php echo esc_attr( $tok['token'] ); ?>" <?php selected( $cond_item['field'], $tok['token'] ); ?>>
												<?php echo esc_html( $tok['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>

									<select
										name="fp_conditions[<?php echo absint( $cond_idx ); ?>][operator]"
										class="fp-cond-operator"
										aria-label="<?php esc_attr_e( 'Condition operator', 'flowpress' ); ?>"
									>
										<?php foreach ( $operators as $op_key => $op_label ) : ?>
											<option value="<?php echo esc_attr( $op_key ); ?>" <?php selected( $cond_item['operator'], $op_key ); ?>>
												<?php echo esc_html( $op_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

									<input
										type="text"
										name="fp_conditions[<?php echo absint( $cond_idx ); ?>][value]"
										class="fp-cond-value fp-input"
										value="<?php echo esc_attr( $cond_item['value'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'value', 'flowpress' ); ?>"
										aria-label="<?php esc_attr_e( 'Condition value', 'flowpress' ); ?>"
										style="<?php echo in_array( $cond_item['operator'], $valueless_ops, true ) ? 'display:none;' : ''; ?>"
									>

									<button type="button" class="fp-remove-condition" aria-label="<?php esc_attr_e( 'Remove condition', 'flowpress' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						</div>

						<button type="button" id="fp-add-condition" class="fp-add-condition-btn">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Add condition', 'flowpress' ); ?>
						</button>
					</div>
				</section>

			<!-- Step 2: Actions -->
				<section class="fp-card fp-step-card" id="fp-actions-section" aria-label="<?php esc_attr_e( 'Step 2: Actions', 'flowpress' ); ?>">
					<div class="fp-step-header">
						<span class="fp-step-number" aria-hidden="true">2</span>
						<div class="fp-step-heading">
							<h2 class="fp-step-title"><?php esc_html_e( 'Do this…', 'flowpress' ); ?></h2>
							<p class="fp-step-subtitle"><?php esc_html_e( 'Add one or more actions to run when the trigger fires.', 'flowpress' ); ?></p>
						</div>
					</div>

					<div class="fp-card-body">
						<!-- Actions list — JS renders action blocks here -->
						<div id="fp-actions-list" role="list" aria-label="<?php esc_attr_e( 'Actions', 'flowpress' ); ?>"></div>

						<div class="fp-field-error" id="fp-error-actions" role="alert" aria-live="polite"></div>

						<button type="button" id="fp-add-action" class="fp-add-action-btn" aria-label="<?php esc_attr_e( 'Add another action', 'flowpress' ); ?>">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Add action', 'flowpress' ); ?>
						</button>
					</div>
				</section>

			</div><!-- /.fp-builder-main -->

			<!-- ══ RIGHT SIDEBAR ══════════════════════════════════════════ -->
			<div class="fp-builder-sidebar">

				<!-- Summary card -->
				<div class="fp-card fp-summary-card" id="fp-summary-card" aria-label="<?php esc_attr_e( 'Recipe summary', 'flowpress' ); ?>">
					<div class="fp-card-header"><h3><?php esc_html_e( 'Summary', 'flowpress' ); ?></h3></div>
					<div class="fp-card-body">
						<p id="fp-summary-text" class="fp-summary-text fp-summary-empty">
							<?php esc_html_e( 'Choose a trigger to see a summary of your recipe.', 'flowpress' ); ?>
						</p>
					</div>
				</div>

				<!-- Actions card -->
				<div class="fp-card fp-save-card">
					<div class="fp-card-body">
						<button type="submit" class="button button-primary fp-save-btn" id="fp-save-btn">
							<?php echo $is_new ? esc_html__( 'Create Recipe', 'flowpress' ) : esc_html__( 'Save Recipe', 'flowpress' ); ?>
						</button>

						<?php if ( ! $is_new ) : ?>
							<button type="button" class="button button-secondary fp-full-width" id="fp-test-recipe" data-recipe-id="<?php echo absint( $recipe_id ); ?>">
								<?php esc_html_e( 'Test Recipe', 'flowpress' ); ?>
							</button>

							<hr class="fp-divider">

							<?php if ( $recipe->is_enabled() ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'disable', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary fp-full-width">
									<?php esc_html_e( 'Disable Recipe', 'flowpress' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'enable', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary fp-full-width">
									<?php esc_html_e( 'Enable Recipe', 'flowpress' ); ?>
								</a>
							<?php endif; ?>

							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'duplicate', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary fp-full-width">
								<?php esc_html_e( 'Duplicate', 'flowpress' ); ?>
							</a>

							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'flowpress', 'fp_action' => 'delete', 'recipe_id' => $recipe_id, '_wpnonce' => wp_create_nonce( 'flowpress_recipe_action_' . $recipe_id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-link-delete fp-full-width fp-delete-link">
								<?php esc_html_e( 'Delete Recipe', 'flowpress' ); ?>
							</a>

							<?php if ( ! $recipe->is_complete() ) : ?>
								<p class="fp-incomplete-warning">
									<span class="dashicons dashicons-warning" aria-hidden="true"></span>
									<?php esc_html_e( 'Incomplete — add a trigger and at least one action.', 'flowpress' ); ?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

			</div><!-- /.fp-builder-sidebar -->

		</div><!-- /.fp-builder-layout -->
	</form>

	<!-- Test run output -->
	<?php if ( ! $is_new ) : ?>
		<div id="fp-test-result" class="fp-test-result-box notice" style="display:none;" role="region" aria-live="polite" aria-label="<?php esc_attr_e( 'Test run result', 'flowpress' ); ?>">
			<strong><?php esc_html_e( 'Test Run Result', 'flowpress' ); ?></strong>
			<div id="fp-test-result-content"></div>
		</div>
	<?php endif; ?>

	<!-- Run Log -->
	<?php if ( ! $is_new ) : ?>
		<section class="fp-card fp-log-card" aria-label="<?php esc_attr_e( 'Recent runs', 'flowpress' ); ?>">
			<div class="fp-card-header">
				<h2><?php esc_html_e( 'Recent Runs', 'flowpress' ); ?></h2>
			</div>
			<div class="fp-card-body">
				<?php if ( $run_entries ) : ?>
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
												<div><?php echo esc_html( ( $r['type'] ?? '' ) . ': ' . ( $r['message'] ?? '' ) ); ?></div>
											<?php endforeach; ?>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="fp-no-runs"><?php esc_html_e( 'No runs yet. Enable the recipe and trigger its event, or use the Test button above.', 'flowpress' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- Audit History -->
	<?php if ( ! $is_new && $audit_entries ) : ?>
		<section class="fp-card fp-log-card" aria-label="<?php esc_attr_e( 'Audit history', 'flowpress' ); ?>">
			<div class="fp-card-header">
				<h2><?php esc_html_e( 'Audit History', 'flowpress' ); ?></h2>
			</div>
			<div class="fp-card-body">
				<table class="widefat striped fp-audit-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'flowpress' ); ?></th>
							<th><?php esc_html_e( 'Author', 'flowpress' ); ?></th>
							<th><?php esc_html_e( 'Event', 'flowpress' ); ?></th>
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
			</div>
		</section>
	<?php endif; ?>

</div><!-- /.fp-builder-wrap -->

<!-- Inline data for JS builder -->
<script>
// Convert operators assoc array to indexed [{value, label}] for JS .forEach() compatibility.
<?php
$operators_for_js = array();
foreach ( $operators as $op_value => $op_label ) {
	$operators_for_js[] = array( 'value' => $op_value, 'label' => $op_label );
}
?>
window.flowpressBuilderData = <?php echo wp_json_encode( array(
	'savedTrigger'       => $saved_trigger,
	'savedTriggerConfig' => $saved_trigger_config,
	'savedActions'       => $saved_actions,
	'savedConditions'    => $saved_conditions,
	'operators'          => $operators_for_js,
	'valuelessOps'       => $valueless_ops,
) ); ?>;
</script>
