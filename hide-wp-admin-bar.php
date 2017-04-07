<?php
/*
 * Plugin Name: Hide WP Admin Bar
 * Description: Hide WordPress Admin Bar, Hide admin bar for specific users role, Hide WordPress admin bar for all user role, Hide admin bar from front end, Remove admin bar
 * Version: 1.0.0
 * Author: Pradeep Singh
 * Author URI: https://github.com/pradeepsinghweb
 * Text Domain: hide-wp-admin-bar
 */

class HIDE_WP_ADMIN_BAR{

    public function __construct(){
        add_action( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links') );
        add_action('admin_menu',array($this, 'add_plugin_settings_page' ));
        add_action( 'activated_plugin', array($this, 'add_plugin_default_settings') );
        add_filter('show_admin_bar',array($this, 'hide_admin_bar_from_front_end'));
        add_action('admin_print_scripts-profile.php', array($this, 'hide_admin_bar_tr_from_profile'));
    }
    static function getInstance() {
        static $instance;

        if( empty($instance) ) {
            $instance = new HIDE_WP_ADMIN_BAR();
        }

        return $instance;
    }
    public function hide_admin_bar_from_front_end(){
            return !$this->check_admin_bar_for_curr_user();
    }
    public function hide_admin_bar_tr_from_profile(){
        if($this->check_admin_bar_for_curr_user()){
            ?>
            <style type="text/css">
                .show-admin-bar {display: none;}
            </style>
            <?php
        }
    }
    public function check_admin_bar_for_curr_user(){
        $_hide_admin_bar_settings = unserialize(get_option('_hide_admin_bar_settings'));
        if($_hide_admin_bar_settings['_hide_admin_bar']){
            switch ($_hide_admin_bar_settings['_hide_admin_bar']){
                case "ALL":{
                    return true;
                    break;
                }
                case "ROLE":{
                    $_hide_for_user_roles = $_hide_admin_bar_settings['_user_roles'];
                    if(($_hide_for_user_roles && count($_hide_for_user_roles) > 0) && ($this->get_curr_login_user_role() && in_array($this->get_curr_login_user_role(),$_hide_for_user_roles))){
                        return true;
                    }
                    break;
                }
            }
        }
        return false;
    }
    public function get_curr_login_user_role() {
        global $current_user;

        $user_roles = $current_user->roles;
        $user_role = array_shift($user_roles);

        return $user_role;
    }
    public function add_plugin_default_settings(){
        if(!get_option('_hide_admin_bar_settings')){
            $_hide_admin_bar_defalt_settings['_hide_admin_bar'] = 'ALL';
            update_option('_hide_admin_bar_settings',serialize($_hide_admin_bar_defalt_settings));
        }
    }
    public function add_plugin_settings_page(){
        add_submenu_page(
            'options-general.php',
            'Hide WP Admin Bar',
            'Hide WP Admin Bar',
            'manage_options',
            'hide-wp-admin-bar',
            array($this,'add_plugin_settings_page_callback'));
    }
    public function applyActions($action,$data){
        if(empty($action)) return array('status'=>false,'msg'=>"Error occurred! Form has no action.");
        if($action == 'hide_wp_admin_bar'){
            $is_nonce_valid = ( isset( $data['_wpnonce'] ) && wp_verify_nonce( $data['_wpnonce'], $action) ) ? true : false;
            if(!$is_nonce_valid) return array('status'=>false,'msg'=>"Error occurred! Failed security check.");
            $_hide_admin_bar_settings['_hide_admin_bar'] = $data['_hide_admin_bar'];
            $_hide_admin_bar_settings['_user_roles'] = $data['_user_roles'];
            if($_hide_admin_bar_settings['_hide_admin_bar'] == 'ALL') unset($_hide_admin_bar_settings['_user_roles']);
            if(update_option('_hide_admin_bar_settings',serialize($_hide_admin_bar_settings)))
                return array('status'=>true,'msg'=>"Admin Bar settings have been saved successfully.");
            else
                return array('status'=>false,'msg'=>"Error occurred! Admin Bar settings are not updated.");
        }
        return array('status'=>false,'msg'=>"Error occurred!");
    }
    public function add_plugin_settings_page_callback(){
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Hide WP Admin Bar</h2>';
        $response = null;
        if($_REQUEST['frm-action']){ /*Apply Form Action*/
            $response = $this->applyActions($_REQUEST['frm-action'],$_REQUEST);
        }
        $_hide_admin_bar_settings = unserialize(get_option('_hide_admin_bar_settings'));
        ?>
        <?php if($response) { ?>
            <?php $_cls = ($response['status'] !== false)?'notice-success':'notice-error error'; ?>
            <div id="message" class="updated notice <?php echo $_cls;?> is-dismissible">
                <p><?php echo $response['msg']?></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        <?php } ?>
        <?php ?>
        <div class="hide-wp-admin-bar-form">
            <form action="" method="post" id="hide-wp-admin-bar-form">
                <table class="form-table">
                    <tbody>
                    <tr class="form-field">
                        <th scope="row"><label for="_dyn_sidebar_name">Hide Admin Bar For All Users</label></th>
                        <td><input type="radio" id="_hide_admin_bar_for_all" name="_hide_admin_bar" <?php if($_hide_admin_bar_settings['_hide_admin_bar'] == 'ALL'){?>checked="checked"<?php }?> value="ALL"/></td>
                    </tr>
                    <tr class="form-field form-required">
                        <th scope="row"><label for="_dyn_sidebar_description">Hide Admin Bar For Selected User Role</th>
                        <td><input type="radio" id="_hide_admin_bar_for_selected_users" name="_hide_admin_bar" <?php if($_hide_admin_bar_settings['_hide_admin_bar'] == 'ROLE'){?>checked="checked"<?php }?> value="ROLE"/></td>
                    </tr>
                    <tr class="form-field form-required">
                        <th scope="row"><label for="_dyn_sidebar_description">Hide Admin Bar For Selected User Role</th>
                        <td>
                            <?php global $wp_roles; ?>
                            <?php $_user_roles = $_hide_admin_bar_settings['_user_roles'];?>
                            <select name="_user_roles[]" multiple style="width: 150px;height: 135px;">
                                <?php foreach ($wp_roles->roles as $key=>$value ): ?>
                                    <option value="<?php echo $key; ?>" <?php if($_user_roles && in_array($key,$_user_roles)){?>selected="selected"<?php }?>><?php echo $value['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="hidden" name="frm-action" value="hide_wp_admin_bar" />
                    <?php wp_nonce_field('hide_wp_admin_bar'); ?>
                    <input type="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        <?php
        echo '</div>';
    }
    /**
     * Add action links.
     */
    public function plugin_action_links($links){
        unset( $links['edit'] );
        $links['manage'] = '<a href="' . admin_url('options-general.php?page=hide-wp-admin-bar') . '">'.__('Settings', 'hide-wp-admin-bar').'</a>';
        return $links;
    }

}
HIDE_WP_ADMIN_BAR::getInstance();