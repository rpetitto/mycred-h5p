<?php
/**
 * @wordpress-plugin
 * Plugin Name:       myCRED H5P
 * Plugin URI:        http://h5p.org/
 * Description:       Adds a myCRED hook for tracking points scored in H5P content.
 * Version:           0.4
 * Author URI:        http://joubel.com
 * Forked:	      rpetitto
 * Text Domain:       mycredh5p
 * License:           MIT
 * License URI:       http://opensource.org/licenses/MIT
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/rpetitto/mycred-h5p
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}
add_filter('mycred_setup_hooks', 'mycredh5p_register');
function mycredh5p_register($installed) {
	$installed['mycredh5p'] = array(
		'title'       => __('myCRED H5P', 'mycred'),
		'description' => __('Adds a myCRED hook for tracking points scored in H5P content.', 'mycred'),
		'callback'    => array('myCRED_Hook_H5P')
	);
	return $installed;
}
/**
 *
 */
function mycredh5p_badge($references) {
	$references['completing_h5p'] = __('Completing H5P', 'mycred');
	return $references;
}
add_filter('mycred_all_references', 'mycredh5p_badge');
/**
 *
 */
function mycredh5p_init() {
  /**
   * Class
   */
  class myCRED_Hook_H5P extends myCRED_Hook {
    /**
  	 * Construct
  	 */
  	function __construct($hook_prefs, $type = 'mycred_default') {
  		parent::__construct(array(
  			'id'       => 'mycredh5p',
  			'defaults' => array(
  			   'completing_h5p' => array(
  					'max_required' => 0,
					'random_points_block' => 0,
					'creds'   => 0,
  					'log'     => '%plural% for Completing an H5P Activity',
  					'dynamic_points_block' => 0  
  			)
  		  )
  		), $hook_prefs, $type);
  	}
  	/**
  	 * Hook into H5P
  	 */
    public function run() {
      	// H5P Completed
	  	if ( $this->prefs['completing_h5p']['creds'] != 0 )
		add_action('h5p_alter_user_result',  array($this, 'h5p_result'), 10, 4);
    }
    /**
     * Give points for H5P result
     */
    public function h5p_result($data, $result_id, $content_id, $user_id) {
    //set random points max
    $randMax = $this->prefs['completing_h5p']['creds'];
		
	// Check if full score or max_required is checked
      if ($data['score'] !== $data['max_score'] && isset( $this->prefs['completing_h5p']['max_required'] ) && $this->prefs['completing_h5p']['max_required'] == 1 ) return;

	// Make sure this is the first result for this content.
	if ($result_id) return; // (result_id is only used when updating an old score)
      
	// Make sure this is a unique event
	if ($this->has_entry('completing_h5p', $content_id, $user_id)) return;
      
	// Check if random points box is checked
	if ( isset( $this->prefs['completing_h5p']['random_points_block'] ) && $this->prefs['completing_h5p']['random_points_block'] == 1 ) {
		$this->prefs['completing_h5p']['creds'] = rand( 1 , $randMax );
			}
	    
	// Check if dynamic box is checked
	if ( isset( $this->prefs['completing_h5p']['dynamic_points_block'] ) && $this->prefs['completing_h5p']['dynamic_points_block'] == 1 ) {
		$this->prefs['completing_h5p']['creds']= $data['score'];
			}
      // Execute
      $this->core->add_creds(
        'completing_h5p',
        $user_id,
        $this->prefs['completing_h5p']['creds'],
        $this->prefs['completing_h5p']['log'],
        $content_id,
        array( 'ref_type' => 'post' ),
			$this->mycred_type
      );
    }
    public function preferences() {
		$prefs = $this->prefs;
	   	$h5p_max_required = 0;
			if ( isset( $prefs['completing_h5p']['max_required'] ) )
				$h5p_max_required = $prefs['completing_h5p']['max_required'];
		$h5p_dynamic_points_block = 0;
			if ( isset( $prefs['completing_h5p']['dynamic_points_block'] ) )
				$h5p_dynamic_points_block = $prefs['completing_h5p']['dynamic_points_block'];
	  	$h5p_random_points_block = 0;
			if ( isset( $prefs['completing_h5p']['random_points_block'] ) )
				$h5p_random_points_block = $prefs['completing_h5p']['random_points_block'];
	?>
<label class="subheader" for="<?php echo $this->field_id( array( 'completing_h5p' => 'creds' ) ); ?>"><?php _e( 'Completing an H5P Activity', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'completing_h5p' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'completing_h5p' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['completing_h5p']['creds'] ); ?>" size="8" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'completing_h5p' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'completing_h5p' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'completing_h5p' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['completing_h5p']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'completing_h5p', 'random_points_block' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'completing_h5p', 'random_points_block' ) ); ?>"<?php checked( $h5p_random_points_block, 1 ); ?> id="<?php echo $this->field_id( array( 'completing_h5p', 'random_points_block' ) ); ?>" value="1" /> <?php echo $this->core->template_tags_general( __( 'User earns random %_plural where the minimum reward is 1 and the maximum reward is the # above', 'mycred' ) ); ?>
<label class="subheader" for="<?php echo $this->field_id( array( 'completing_h5p', 'dynamic_points_block' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'completing_h5p', 'dynamic_points_block' ) ); ?>"<?php checked( $h5p_dynamic_points_block, 1 ); ?> id="<?php echo $this->field_id( array( 'completing_h5p', 'dynamic_points_block' ) ); ?>" value="1" /> <?php echo $this->core->template_tags_general( __( 'User earns %_plural equal to points earned in activity (overrides # above)', 'mycred' ) ); ?>
<label class="subheader" for="<?php echo $this->field_id( array( 'completing_h5p', 'max_required' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'completing_h5p', 'max_required' ) ); ?>"<?php checked( $h5p_max_required, 1 ); ?> id="<?php echo $this->field_id( array( 'completing_h5p', 'max_required' ) ); ?>" value="1" /> <?php echo $this->core->template_tags_general( __( 'User must earn 100% in activity to earn %_plural', 'mycred' ) ); ?>
<?php
    }
  /**
   * Sanitize Preferences
   */
	public function sanitise_preferences( $data ) {
		$new_data = $data;
		// Apply defaults if any field is left empty
		$new_data['creds'] = ( !empty( $data['creds'] ) ) ? $data['creds'] : $this->defaults['creds'];
		$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];
		return $new_data;
	}
  }
}
add_action('mycred_pre_init', 'mycredh5p_init');
