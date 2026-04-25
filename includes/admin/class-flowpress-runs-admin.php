<?php
/**
 * Runs dashboard — global view of all recipe run logs.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes/admin
 * @since      0.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Runs_Admin
 *
 * Provides the "Runs" admin submenu page listing all runs across all recipes
 * with status filters and a one-click re-run button for failed entries.
 *
 * @since 0.5.0
 */
class FlowPress_Runs_Admin {

	/**
	 * Register hooks.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_flowpress_rerun', array( $this, 'ajax_rerun' ) );
	}

	/**
	 * Render the Runs dashboard page.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'flowpress' ) );
		}

		$status_filter = isset( $_GET['run_status'] ) ? sanitize_key( $_GET['run_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$runs          = $this->get_runs( $status_filter );

		echo '<div class="wrap flowpress-wrap">';

		require_once FLOWPRESS_PLUGIN_DIR . 'includes/admin/views/promo-banner.php';

		echo '<h1>' . esc_html__( 'FlowPress Runs', 'flowpress' ) . '</h1>';

		// Status filter links.
		$this->render_status_filters( $status_filter );

		if ( $runs ) {
			echo '<table class="widefat striped fp-run-log-table">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Date', 'flowpress' ) . '</th>';
			echo '<th>' . esc_html__( 'Recipe', 'flowpress' ) . '</th>';
			echo '<th>' . esc_html__( 'Trigger', 'flowpress' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'flowpress' ) . '</th>';
			echo '<th>' . esc_html__( 'Details', 'flowpress' ) . '</th>';
			echo '<th>' . esc_html__( 'Actions', 'flowpress' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $runs as $run ) {
				$this->render_run_row( $run );
			}

			echo '</tbody></table>';
		} else {
			echo '<p class="fp-no-runs">' . esc_html__( 'No runs found.', 'flowpress' ) . '</p>';
		}

		// Retry policy note.
		echo '<div class="fp-retry-policy-note">';
		echo '<span class="dashicons dashicons-info" aria-hidden="true"></span> ';
		echo '<strong>' . esc_html__( 'Retry policy:', 'flowpress' ) . '</strong> ';
		echo esc_html( FlowPress_Retry_Queue::get_policy_description() );
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render status filter view links.
	 *
	 * @since  0.5.0
	 * @param  string $current Current status filter.
	 * @return void
	 */
	private function render_status_filters( $current ) {
		$base     = admin_url( 'admin.php?page=flowpress-runs' );
		$statuses = array(
			''        => __( 'All', 'flowpress' ),
			'success' => __( 'Succeeded', 'flowpress' ),
			'failed'  => __( 'Failed', 'flowpress' ),
			'skipped' => __( 'Skipped', 'flowpress' ),
			'dry_run' => __( 'Test Runs', 'flowpress' ),
		);

		echo '<ul class="subsubsub">';
		$links = array();
		foreach ( $statuses as $status => $label ) {
			$url     = $status ? add_query_arg( 'run_status', $status, $base ) : $base;
			$active  = $status === $current ? ' class="current"' : '';
			$count   = $this->count_runs( $status );
			$links[] = '<li><a href="' . esc_url( $url ) . '"' . $active . '>' .
				esc_html( $label ) . ' <span class="count">(' . absint( $count ) . ')</span></a></li>';
		}
		echo implode( ' | ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all parts escaped above.
		echo '</ul>';
	}

	/**
	 * Render a single run row.
	 *
	 * @since  0.5.0
	 * @param  object $run Run log row object.
	 * @return void
	 */
	private function render_run_row( $run ) {
		$results    = json_decode( $run->action_results, true );
		$status_map = array(
			'success' => 'enabled',
			'failed'  => 'disabled',
			'skipped' => 'draft',
			'dry_run' => 'draft',
		);
		$status_css = $status_map[ $run->status ] ?? 'draft';

		$recipe_title = get_the_title( (int) $run->recipe_id ) ?: sprintf( __( 'Recipe #%d', 'flowpress' ), $run->recipe_id );
		$recipe_link  = admin_url( 'admin.php?page=flowpress-edit&recipe_id=' . absint( $run->recipe_id ) );
		$date         = get_date_from_gmt( $run->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		echo '<tr>';
		echo '<td>' . esc_html( $date ) . '</td>';
		echo '<td><a href="' . esc_url( $recipe_link ) . '">' . esc_html( $recipe_title ) . '</a></td>';
		echo '<td>' . esc_html( $run->trigger_type ) . '</td>';
		echo '<td><span class="fp-status fp-status--' . esc_attr( $status_css ) . '">' .
			esc_html( $run->is_dry_run ? __( 'Test', 'flowpress' ) : ucfirst( $run->status ) ) . '</span></td>';
		echo '<td>';
		if ( $run->error_message ) {
			echo '<span class="fp-error">' . esc_html( $run->error_message ) . '</span>';
		} elseif ( is_array( $results ) ) {
			foreach ( $results as $r ) {
				echo '<div>' . esc_html( ( $r['type'] ?? '' ) . ': ' . ( $r['message'] ?? '' ) ) . '</div>';
			}
		} else {
			echo '&mdash;';
		}
		echo '</td>';
		echo '<td>';
		if ( 'failed' === $run->status || 'skipped' === $run->status ) {
			echo '<button type="button" class="button button-small fp-rerun-btn" data-run-id="' . absint( $run->id ) . '">' .
				esc_html__( 'Re-run', 'flowpress' ) . '</button>';
		}
		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Fetch run log rows filtered by status.
	 *
	 * @since  0.5.0
	 * @param  string $status Optional status filter.
	 * @return array
	 * @global wpdb   $wpdb
	 */
	private function get_runs( $status = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . FlowPress_Run_Log::TABLE_NAME;

		if ( $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM `{$table}` WHERE status = %s ORDER BY created_at DESC LIMIT 200", $status )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT 200" );
	}

	/**
	 * Count run log rows by status.
	 *
	 * @since  0.5.0
	 * @param  string $status Status filter (empty = all).
	 * @return int
	 * @global wpdb   $wpdb
	 */
	private function count_runs( $status = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . FlowPress_Run_Log::TABLE_NAME;

		if ( $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", $status ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}

	/**
	 * AJAX: re-run a failed run using its original payload.
	 *
	 * @since  0.5.0
	 * @return void
	 */
	public function ajax_rerun() {
		check_ajax_referer( 'flowpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowpress' ) ), 403 );
		}

		$run_log_id = isset( $_POST['run_id'] ) ? absint( $_POST['run_id'] ) : 0;

		if ( ! $run_log_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid run ID.', 'flowpress' ) ) );
		}

		$results = FlowPress_Runner::rerun( $run_log_id );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}

		$any_failed = array_filter( $results, static function ( $r ) {
			return isset( $r['status'] ) && FlowPress_Action_Result::STATUS_FAILED === $r['status'];
		} );

		wp_send_json_success( array(
			'message' => empty( $any_failed )
				? __( 'Re-run succeeded.', 'flowpress' )
				: __( 'Re-run completed with errors — check the log.', 'flowpress' ),
			'results' => $results,
		) );
	}
}
