<?php
/**
 * FlowPress Public Hook Reference
 *
 * This file documents every action and filter that FlowPress exposes for
 * third-party plugins to extend. It is never included at runtime — its sole
 * purpose is to serve as a readable, grep-able, IDE-indexed API reference.
 *
 * Usage pattern:
 *   add_action( 'flowpress_register_triggers', 'my_callback' );
 *   add_action( 'flowpress_register_actions',  'my_callback' );
 *
 * @package FlowPress
 * @since   0.1.0
 */

// ── Trigger registration ────────────────────────────────────────────────────

/**
 * Fires during 'init' (priority 20) to collect trigger registrations.
 *
 * Use this hook to register custom triggers. Callbacks receive the trigger
 * registry class name and should call `$registry::register( $trigger )`.
 *
 * @since 0.1.0
 *
 * @param string $registry Fully-qualified class name of FlowPress_Trigger_Registry.
 *
 * @example
 * add_action( 'flowpress_register_triggers', function ( string $registry ): void {
 *     $registry::register( new My_Custom_Trigger() );
 * } );
 */
do_action( 'flowpress_register_triggers', 'FlowPress_Trigger_Registry' );

// ── Action registration ─────────────────────────────────────────────────────

/**
 * Fires during 'init' (priority 20) to collect action registrations.
 *
 * Use this hook to register custom actions. Callbacks receive the action
 * registry class name and should call `$registry::register( $action )`.
 *
 * @since 0.1.0
 *
 * @param string $registry Fully-qualified class name of FlowPress_Action_Registry.
 *
 * @example
 * add_action( 'flowpress_register_actions', function ( string $registry ): void {
 *     $registry::register( new My_Custom_Action() );
 * } );
 */
do_action( 'flowpress_register_actions', 'FlowPress_Action_Registry' );

// ── Planned hooks (not yet implemented — listed for roadmap visibility) ──────

/**
 * Fires immediately before FlowPress executes a recipe.
 *
 * @since     0.2.0 (planned)
 * @param int    $recipe_id    The recipe being executed.
 * @param string $trigger_type The trigger type that fired.
 * @param array  $payload      The trigger payload array.
 * @param bool   $dry_run      Whether this is a test run.
 */
// do_action( 'flowpress_before_run', $recipe_id, $trigger_type, $payload, $dry_run );

/**
 * Fires immediately after FlowPress finishes executing a recipe.
 *
 * @since     0.2.0 (planned)
 * @param int    $recipe_id      The recipe that was executed.
 * @param string $trigger_type   The trigger type that fired.
 * @param array  $action_results Array of FlowPress_Action_Result::to_array() values.
 * @param bool   $dry_run        Whether this was a test run.
 */
// do_action( 'flowpress_after_run', $recipe_id, $trigger_type, $action_results, $dry_run );

/**
 * Filters the payload before FlowPress passes it to condition evaluation and actions.
 *
 * @since     0.2.0 (planned)
 * @param array  $payload      The trigger payload.
 * @param string $trigger_type The trigger type.
 * @param int    $recipe_id    The recipe ID.
 * @return array Filtered payload.
 */
// apply_filters( 'flowpress_payload', $payload, $trigger_type, $recipe_id );
