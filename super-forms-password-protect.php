<?php
/**
 * Super Forms - Password Protect
 *
 * @package   Super Forms - Password Protect
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Password Protect
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Password protect your forms or lock out specific user roles from submitting the form
 * Version:     1.0.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Password_Protect')) :


    /**
     * Main SUPER_Password_Protect Class
     *
     * @class SUPER_Password_Protect
     * @version	1.0.0
     */
    final class SUPER_Password_Protect {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.0';

        
        /**
         * @var SUPER_Password_Protect The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Password_Protect Instance
         *
         * Ensures only one instance of SUPER_Password_Protect is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Password_Protect()
         * @return SUPER_Password_Protect - Main instance
         *
         *	@since		1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_Password_Protect Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_password_protect_loaded');
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *	@since		1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *	@since		1.0.0
        */
        private function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Hook into actions and filters
         *
         *	@since		1.0.0
        */
        private function init_hooks() {
            
            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_form_before_do_shortcode_filter', array( $this, 'locked_out_user_msg' ), 10, 2 );

                // Actions since 1.0.0

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );

                // Actions since 1.0.0

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0

                // Actions since 1.0.0
                add_action( 'super_before_sending_email_hook', array( $this, 'before_sending_email' ) );

            }
            
        }


        /**
         * Hook into before sending email and do password protect check
         *
         *  @since      1.0.0
        */
        public static function before_sending_email( $atts ) {
            $atts = array(
                'id' => $atts['post']['form_id'],
                'data' => $atts['post']['data'],
                'settings' => $atts['settings']
            );
            SUPER_Password_Protect()->locked_out_user_msg( '', $atts );
        }


        /**
         * Display message to locked out users
         *
         *  @since      1.0.0
        */
        public static function locked_out_user_msg( $result, $atts ) {
            
            // Check if we need to hide the form
            if( !isset( $atts['settings']['password_protect_roles'] ) ) {
                $atts['settings']['password_protect_roles'] = '';
            }
            if( $atts['settings']['password_protect_roles']=='true' ) {
	            if( !isset( $atts['settings']['password_protect_hide'] ) ) {
	                $atts['settings']['password_protect_hide'] = '';
	            }
	            if( $atts['settings']['password_protect_hide']=='true' ) {
	            	$result = '';
	            }
            }
            if( !isset( $atts['settings']['password_protect_login'] ) ) {
                $atts['settings']['password_protect_login'] = '';
            }
            if( $atts['settings']['password_protect_login']=='true' ) {
            	if ( !is_user_logged_in() ) {
		            if( !isset( $atts['settings']['password_protect_login_hide'] ) ) {
		                $atts['settings']['password_protect_login_hide'] = '';
		            }
		            if( $atts['settings']['password_protect_login_hide']=='true' ) {
		            	$result = '';
		            }
	            }
	        }


            // Check if password protect is enabled
            if( !isset( $atts['settings']['password_protect'] ) ) {
                $atts['settings']['password_protect'] = '';
            }
            
            if( $atts['settings']['password_protect']=='true' ) {

                if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                    
                    // Before we proceed, lets check if we have a password field
                    if( !isset( $atts['data']['password'] ) ) {
                        $msg = __( 'We couldn\'t find the <strong>password</strong> field which is required in order to password protect the form. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['id'] ) . '">edit</a> your form and try again', 'super-forms' );
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    // Now lets check if the passwords are incorrect
                    if( $atts['data']['password']['value']!=$atts['settings']['password_protect_password'] ) {
                        if( !isset( $atts['settings']['password_protect_incorrect_msg'] ) ) {
                            $atts['settings']['password_protect_incorrect_msg'] = __( 'Incorrect password, please try again!', 'super-forms' );
                        }
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $atts['settings']['password_protect_incorrect_msg'],
                            $redirect = null
                        );               
                    }
                  
                }

                $field_found = false;
                $elements = json_decode( get_post_meta( $atts['id'], '_super_elements', true ) );
                if( $elements!=null ) {
                    foreach( $elements as $k => $v ) {
                        if($v->tag=='password'){
                            if($v->data->name=='password'){
                                $field_found = true;
                            }
                        }
                    }
                }
                if( $field_found==false ) {
                    $msg  = '<div class="super-msg error">';
                    $msg .= __( 'You have enabled password protection for this form, but we couldn\'t find a password field with the name: <strong>password</strong>. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['id'] ) . '">edit</a> your form and try again.', 'super-forms' );
                    $msg .= '<span class="close"></span>';
                    $msg .= '</div>';
                    $result = $msg.$result;
                    return $result;
                }
            }

            // Return message for non logged in users
            if( $atts['settings']['password_protect_login']=='true' ) {
                if ( !is_user_logged_in() ) {
                    if( !isset( $atts['settings']['password_protect_show_login_msg'] ) ) {
                        $atts['settings']['password_protect_show_login_msg'] = '';
                    }
                    if( $atts['settings']['password_protect_login_hide']=='true' ) {
                        $result = '';
                    }
                    if( $atts['settings']['password_protect_show_login_msg']=='true' ) {
                        if( !isset( $atts['settings']['password_protect_login_msg'] ) ) {
                            $atts['settings']['password_protect_login_msg'] = 'You do not have permission to submit this form!';
                        }
                        if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $atts['settings']['password_protect_login_msg'],
                                $redirect = null
                            );               
                        }
                        $msg  = '<div class="super-msg error">';
                        $msg .= $atts['settings']['password_protect_login_msg'];
                        $msg .= '<span class="close"></span>';
                        $msg .= '</div>';
                        $result = $msg.$result;
                        return $result;
                    }

                    return $result;
                }
            }

            // Return message for locked out users
            if( $atts['settings']['password_protect_roles']=='true' ) {
                if( !isset( $atts['settings']['password_protect_show_msg'] ) ) {
                    $atts['settings']['password_protect_show_msg'] = '';
                }
                if( !isset( $atts['settings']['password_protect_msg'] ) ) {
                    $atts['settings']['password_protect_msg'] = 'You are currently not logged in. In order to submit the form make sure you are logged in!';
                }
                // Check if the users doesn't have the propper user role
                global $current_user;
                if( (!isset( $atts['settings']['password_protect_user_roles'] )) || ($atts['settings']['password_protect_user_roles']=='') ) {
                    $atts['settings']['password_protect_user_roles'] = array();
                }
                $allowed_roles = $atts['settings']['password_protect_user_roles'];
                $allowed = false;
                foreach( $current_user->roles as $v ) {
                    if( in_array( $v, $allowed_roles ) ) {
                        $allowed = true;
                    }
                }
                if( $allowed==false ) {
                    if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $atts['settings']['password_protect_msg'],
                            $redirect = null
                        );               
                    }
                    if( $atts['settings']['password_protect_show_msg']=='true' ) {
                        $msg  = '<div class="super-msg error">';
                        $msg .= $atts['settings']['password_protect_msg'];
                        $msg .= '<span class="close"></span>';
                        $msg .= '</div>';
                        $result = $msg.$result;
                        return $result;
                    }
                }
            }
            return $result;

        }


        /**
         * Hook into settings and add Password Protect settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            global $wp_roles;
            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters( 'editable_roles', $all_roles );
            $roles = array(
                '' => __( 'All user roles', 'super-forms' )
            );
            foreach( $editable_roles as $k => $v ) {
                $roles[$k] = $v['name'];
            }
            $reg_roles = $roles;
            unset($reg_roles['']);
            $array['password_protect'] = array(        
                'name' => __( 'Password Protect', 'super-forms' ),
                'label' => __( 'Password Protect Settings', 'super-forms' ),
                'fields' => array(
                    'password_protect' => array(
                        'desc' => __( 'Use a password to protect the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Enable password protection', 'super-forms' ),
                        )
                    ),
                    'password_protect_password' => array(
                        'name' => __( 'Password', 'super-forms' ),
                        'desc' => __( 'Enter a password to protect the form', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_password', $settings['settings'], wp_generate_password( 24 ) ),
                        'filter' => true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                    ),
                    'password_protect_incorrect_msg' => array(
                        'name' => __( 'Incorrect password message', 'super-forms' ), 
                        'desc' => __( 'The message to display when an incorrect password was entered', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_incorrect_msg', $settings['settings'], __( 'Incorrect password, please try again!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                    ),

                    'password_protect_roles' => array(
                        'desc' => __( 'Allows only specific user roles to submit the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_roles', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Allow only specific user roles', 'super-forms' ),
                        )
                    ),
                    'password_protect_user_roles' => array(
                        'name' => __( 'Use CTRL or SHIFT to select multiple roles', 'super-forms' ),
                        'desc' => __( 'Select all user roles who are allowed to submit the form', 'super-forms' ),
                        'type' => 'select',
                        'multiple' => true,
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_user_roles', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                        'values' => $reg_roles,
                    ),
                    'password_protect_hide' => array(
                        'desc' => __( 'Hide the form for locked out users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_hide', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Hide form for locked out users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_msg' => array(
                        'desc' => __( 'Display a message to the locked out user', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_show_msg', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Display a message to the locked out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                    ),
                    'password_protect_msg' => array(
                        'name' => __( 'Message for locked out users', 'super-forms' ), 
                        'desc' => __( 'The message to display to locked out users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_msg', $settings['settings'], __( 'You do not have permission to submit this form!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_msg',
                        'filter_value' => 'true',
                    ),
                    'password_protect_login' => array(
                        'desc' => __( 'Allow only logged in users to submit the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Allow only logged in users', 'super-forms' ),
                        )
                    ),
                    'password_protect_login_hide' => array(
                        'desc' => __( 'Hide the form for not logged in users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login_hide', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Hide form for not logged in users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_login_msg' => array(
                        'desc' => __( 'Display a message to the logged out user', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_show_login_msg', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Display a message to the logged out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                    ),
                    'password_protect_login_msg' => array(
                        'name' => __( 'Message for not logged in users', 'super-forms' ), 
                        'desc' => __( 'The message to display to none logged in users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login_msg', $settings['settings'], __( 'You are currently not logged in. In order to submit the form make sure you are logged in!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_login_msg',
                        'filter_value' => 'true',
                    ),

                )
            );
            return $array;
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_Password_Protect to prevent the need to use globals.
 *
 * @return SUPER_Password_Protect
 */
function SUPER_Password_Protect() {
    return SUPER_Password_Protect::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Password_Protect'] = SUPER_Password_Protect();