<?php
/**
Plugin Name: Custom Shortlink Structure
Plugin URI: http://yrnxt.com/wordpress/custom-shortlink-structure/
Description: Define a custom shortlink structure using your own domain.
Author: Alexander Höreth
Version: 1.0
Author URI: http://yrnxt.com
License: GPL2

    Copyright 2009-2012  Alexander Höreth (email: a.hoereth@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/
if (!defined('CSLS_VER'))
	define('CSLS_VER', '1.0');
$pathinfo = pathinfo(dirname(plugin_basename(__FILE__)));
if (!defined('CSLS_NAME'))
	define('CSLS_NAME', $pathinfo['filename']);
if (!defined('CSLS_DIR'))
	define('CSLS_DIR', plugin_dir_path(__FILE__));
if (!defined('CSLS_URL'))
	define('CSLS_URL', plugins_url(CSLS_NAME) . '/');


class custom_shortlink_structure{
	/**
	 * Contains the current active custom shortlink structure to be used with get_shortlink
	 * @var string
	 */
	private $current;

	/**
	 * Class constructor. Adds filters and actions required for the plguin.
	 */
	public function __construct(){
		// plugin activation, deactivation and uninstall
		register_activation_hook( 	__FILE__, array(&$this, 'activation'   )			);
		register_deactivation_hook( __FILE__, array(&$this, 'deactivation' ) 			);

		// everything regarding rewriting and forwarding a request
		add_action('generate_rewrite_rules',array(&$this, 'add_rewrites') 				);
		add_filter('query_vars', 						array(&$this, 'query_vars'), 10, 1 		);
		add_action('template_redirect', 		array(&$this, 'shortlink_redirect') 	);

		// getting the new shortlink for use in the themes and backend
		add_filter('get_shortlink', 				array(&$this, 'get_shortlink'), 10, 3 );

		// additions to options-permalink.php
		add_action( 'admin_init',	array(&$this, 'settings_init') );
		add_action( 'admin_menu',	array(&$this, 'settings') );

		// saving current permalink structure
		$options = get_option('csls-settings');
		$this->current = isset($options['rule'])?$this->decompose_rewrite($options['rule']):'?p=%post_id%';
	}

	/**
	 * Run on plugin activation. Saves plugin version to database.
	 */
	public static function activation(){
		$options = get_option('csls-settings');
		if (!isset($options['version'])){
			$options['version']  = CSLS_VER;
			update_option('csls-settings',$options);
		}else
			flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation to reset the shortlink structure. Does not
	 * delete any settings
	 */
	public static function deactivation(){
		flush_rewrite_rules();
	}

	/**
	 * Adds new rewrite structures.
	 *
	 * @uses $wp_rewrite
	 * @return $wp_rewrite->rules
	 */
	public function add_rewrites(){
		global $wp_rewrite;
		$new_rules = array();

		$options = get_option('csls-settings');
		if (isset($options['rules'])&&count($options['rules'])>0)
			foreach ($options['rules'] as $rule)
				$new_rules[$rule.'$'] = 'index.php?csls='.$wp_rewrite->preg_index(1);

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		return $wp_rewrite->rules;
	}

	/**
	 * Adds new 'csls' query to query_vars array so we can access it later.
	 * @param  array $vars contains current query vars
	 * @return array 			 new query vars
	 */
	public function query_vars($vars){
		$vars[] = 'csls';
		return $vars;
	}

	/**
	 * Looks for the csls query var, if available and if it contains a valid
	 * post id: Redirect to permalink of the page requested. Otherwise return
	 * 404 error.
	 */
	public function shortlink_redirect(){
		if (!get_query_var('csls')) return;
		global $wp_query;

		// get post id
		$post_id = absint(get_query_var('csls'));

		// no post id found, 404 error
		if (!$post_id){
			$wp_query->is_404 = true;
			return;
		}

		// get permalink
		$permalink = get_permalink($post_id);

		// no permalink found, 404 error
		if (!$permalink){
			$wp_query->is_404 = true;
			return;
		}

		// success, forward to permalink
		wp_redirect(esc_url($permalink), 301);
		exit();
	}

	/**
	 * Filters the shortlinks to make use of the new structure.
	 */
	public function get_shortlink($link, $post_id, $context){
		if ('query' == $context && is_single())
			$post_id = get_queried_object_id();

		$att = str_replace('%post_id%',$post_id,$this->current);
		return home_url( $att );
	}

	/**
	 * Makes additions to options-permalink.php. These additions contain new
	 * options to specify the shortlink structure and to remove old, but still
	 * active shortlink structures. Those are hold active to ensure that old
	 * links do not break.
	 */
	public function settings(){
		// does not work on options-permalink.php, using $this->save() instead.
		//register_setting('permalink', 'csls-settings', array( &$this, 'settings_save' ));

		$options = get_option('csls-settings');
		$rule = isset($options['rule'])&&!empty($options['rule'])?$this->decompose_rewrite($options['rule']):'?p=%post_id%';

		add_settings_section('shortlinks-section',__('Shortlink Structure','custom-shortlink-rule'),array(&$this,'settings_html'),'permalink');

		// common shortlink structures
		$radios = array(
			'?p=%post_id%' 	=> __('Default', 												'custom-shortlink-structure'),
			's/%post_id%' 	=> __('&quot;s&quot; for <i>short</i>', 'custom-shortlink-structure'),
			'-%post_id%' 		=> __('Leading minus', 									'custom-shortlink-structure'),
			'~%post_id%' 		=> __('Leading tilde', 									'custom-shortlink-structure')
		);

		// add all common structure settings fields
		$i=0;
		foreach ($radios as $key => $val){ $i++;
			add_settings_field('sl-rule-'.$i,
				'<label><input name="csls-settings[rule]" type="radio" value="'.$key.'" '.checked($key,$rule,false).'/>&nbsp;'.$val.'</label>',
				function() use ($key){ echo "<code>".site_url( str_replace('%post_id%',123,$key) )."</code>"; },
				'permalink', 'shortlinks-section');
		}

		// add custom structure settings field
		add_settings_field('sl-rule-0',
			'<label><input name="csls-settings[rule]" type="radio" value="custom" '.checked(false,in_array($rule,array_keys($radios)),false).'/>&nbsp;'.__('Custom', 'custom-shortlink-structure').'</label>',
			function() use ($rule){
				echo '<label for="shortlinks-custom"><code>'.site_url('/').'</code></label>';
				echo '<input type="text" name="csls-settings[custom]" id="csls-settings-custom" value="'.$rule.'" />';
			},
			'permalink', 'shortlinks-section');

		// Add "old but still active structures" section if required
		if (isset($options['rules'])&&count($options['rules'])>1){
			add_settings_section('shortlinks-active',__('Old but still active Shortlink Structures','custom-shortlink-structure'),array(&$this,'active_html'),'permalink');

			foreach ($options['rules'] as $idx => $val){
				$val = $this->decompose_rewrite($val);
				if ($val!=$rule)
					add_settings_field('sl-rules-'.$idx,
						'<label><input name="csls-settings[rules]['.$idx.']" type="checkbox" value="'.$val.'" checked="checked" />&nbsp;'.$val.'</label>',
						function() use ($val){ echo '<code>'.site_url('/'. str_replace('%post_id%',123,$val) ).'</code>';	},
						'permalink', 'shortlinks-active'
					);
			}
		}

	}

	/**
	 * Description for the shortlink structure section.
	 */
	public function settings_html(){ ?>

<p><?php _e('Here you can change your shortcode structure. It is important that you specify the position of <b>%post_id%</b>, otherwise this form will be reset.','custom-shortlink-structure'); ?><br />
<?php printf(
	__('Note: Other %sstructure tags%s from above do not work here!','custom-shortlink-structure'),
	'<a href="http://codex.wordpress.org/Using_Permalinks#Structure_Tags" target="_blank">','</a>'
); ?></p>
<p><strong><?php _e('Shortlinks should not interfere with any of your other links! Do not edit this setting thoughtless!','custom-shortlink-structure'); ?></strong></p>

<?php if (!get_option('permalink_structure')){  ?>

<p class="csls-warning" style="font-weight: bold;"><?php _e('To use custom shortlink structures you need to specify a non-default permalink structure above.','custom-shortlink-structure'); ?></p>

<?php } }


	/**
	 * Description for the still active shortlinks section.
	 */
	public function active_html(){ ?>

<p><?php _e('To prevent links using old shortlink structures break they are held active. You can remove them by deactivation.','custom-shortlink-structure'); ?></p>
<p><?php _e('WordPress default (<code>?p=%post_id%</code>) will stay active all the time.','custom-shortlink-structure'); ?></p>

<?php }

	public function settings_init(){
		// enqueue javascript for changing the custom input box onclick of radio buttons
		add_action('admin_enqueue_scripts', array( &$this, 'enqueue' ) );

		// alternative to register_setting
 		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csls-settings'])){
	 		$ref = parse_url($_SERVER['HTTP_REFERER']);
	 		$now = parse_url('http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
	 		if (($ref['host']==$now['host']) && ($ref['path']==$now['path']))
	 			update_option('csls-settings',$this->settings_save($_POST['csls-settings']));
	 	}
	}

	/**
	 * Function called when the settings need to be saved
	 * @param array new options to be saved
	 * @return whole $options array to be written saved using update_option.
	 */
	public function settings_save($post){
		$options = get_option('csls-settings');
		if (!isset($options['version']))
			$options['version'] = CSLS_VER;

		$options['rules'] = array();
		if (isset($options['rule']))
			$options['rules'][] = $options['rule'];
		if (isset($post['rules']))
			foreach ($post['rules'] as $rule)
				$options['rules'][] = $this->compose_rewrite($rule);

		if (isset($post['rule'])&&!empty($post['rule'])&&('?p=%post_id%'!=$post['rule'])){
			if ($post[ 'rule']!= 'custom')
				$options['rule'] = $this->compose_rewrite($post['rule']);
			else
				$options['rule'] = $this->compose_rewrite($post['custom']);

			if (!isset($options['rules'])||!in_array($options['rule'],$options['rules']))
				$options['rules'][] = $options['rule'];
		}

		return $options;
	}

	/**
	 * Enqueue's script for changing custom input value on click of radio buttons.
	 * Only enqueue's on options-permalink.php
	 */
	public function enqueue($hook){
		if ('options-permalink.php' == $hook)
			wp_enqueue_script( 'custom-shortlink-structure', CSLS_URL . 'script.js', array( 'jquery' ), CSLS_VER );
	}

	/**
	 * Compose a rewrite rule from a given structure. Replaces %post_id% with (\d+),
	 * cuts of everything afterwards and preg_quotes everything before.
	 * @param  string $structure ideally containing %post_id%
	 * @return string            usable as rewrite rule
	 */
	public function compose_rewrite($structure){
		$rule = str_replace('%post_id%','(\d+)',$structure);
		$pos  = strpos($rule,'(\d+)');
		if ($pos !== false)
			return preg_quote(substr($rule,0,$pos)).'(\d+)';
		else
			return '?p=(\d+)';
	}

	/**
	 * Gets a rewrite rule and creates a structure from it. Replaces (\d+) with
	 * %post_id%.
	 * @param  string $rule rewrite rule containing (\d+)
	 * @return string       structure to be displayed on options-permalink.php
	 */
	public function decompose_rewrite($rule){
		$structure = str_replace('(\d+)','%post_id%',$rule);
		$pos  		 = strpos($structure,'%post_id%');
		if ($pos !== false)
			return stripslashes(substr($structure,0,$pos)).'%post_id%';
		else
			return '?p=%post_id%';
	}

}

new custom_shortlink_structure();
