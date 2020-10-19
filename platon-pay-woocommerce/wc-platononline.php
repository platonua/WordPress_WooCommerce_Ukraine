<?php
/*
* Plugin Name: Platon Pay WooCommerce
* Description: «Platon Pay WooCommerce» is perfect for both online stores operating on the WooCommerce platform.
* Author: udjin
* Version: 1.7
* Requires at least: 4.7
* Requires PHP: 5.2
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;
add_action("admin_menu", "ppw_admin_add_menu_custom" ,20);
function ppw_admin_add_menu_custom(){
    /**Проверка активности плагина Platon Pay.*/
    require_once(ABSPATH.'wp-admin/includes/plugin.php');
    if  (is_plugin_active('platon-pay/index.php')){
        add_submenu_page(
            'platon-pay/inc/settings.php',
            'Настройки Platon Pay',
            'Настройки Platon Pay',
            'manage_options',
            'admin.php?page=wc-settings&tab=checkout&section=platononline'
        );
    }
    else {
        add_menu_page(
            'Настройки плагина Platon',
            'Platon Pay',
            'manage_options',
            'admin.php?page=wc-settings&tab=checkout&section=platononline',
            '',
            plugin_dir_url( __FILE__ )."images/icon.png"
        );
    }
}

add_filter( 'woocommerce_gateway_icon', 'ppw_custom_gateway_icon', 10, 2 );
function ppw_custom_gateway_icon( $icon, $id ) {
	$plugin_dir = plugin_dir_url(__FILE__).'images/';
	$visaLogo = '<img src="'.$plugin_dir.'visa.svg" alt="visa">';
	$mastercardLogo = '<img src="'.$plugin_dir.'mastercard.svg" alt="mastercard">';
	$prostirLogo = '<img src="'.$plugin_dir.'prostir.svg" alt="простір">';
	
	$allIcons = $visaLogo . $mastercardLogo . $prostirLogo.'<img class="doneby" alt="PSP Platon" src="'.plugin_dir_url(__FILE__).'images/platondone.svg">';
	if ( $id === 'platononline' ) {
		return $allIcons;
	} else {
		return $icon;
	}
}
add_action('plugins_loaded', 'ppw_platononline_init', 0);
function ppw_platononline_init(){

    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Platononline extends WC_Payment_Gateway {
        public function __construct() {
            global $woocommerce;
            $this->id = 'platononline';
            $this->has_fields = false;
            $this->method_title = __('Platon 1.7', 'woocommerce');
            $this->method_description = __('Platon 1.7', 'woocommerce');
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->password = $this->get_option('password');
            $this->secret = $this->get_option('secret');
			$this->url = $this->get_option('url');
            $this->language = $this->get_option('language');
            $this->paymenttime = $this->get_option('paymenttime');
            $this->payment_method = $this->get_option('payment_method');

            $if_test = $this->test_mode = $this->get_option('test_mode'); 
             if ($if_test=='yes') {
                $this->password = 'TaHycyY5z7PeZsX4fpuQcXusX5JHjmLy';
                $this->secret = 'F5QQ6NQS64';
                add_action( 'template_redirect', 'after_platon_pay' );            
                    if( is_wc_endpoint_url( 'order-received' ) ) {
                        $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
                        $order = wc_get_order( $order_id );
                        $order->update_status('wc-test-pay', __('Тестовая оплата прошла успешно', 'woocommerce'));
                        add_filter( 'woocommerce_endpoint_order-received_title', 'platon_new_title' );
                        function platon_new_title( $old_title ){ 
                            return 'Тестовый платеж';
                        }
                        add_filter( 'woocommerce_thankyou_order_received_text', 'platon_thank_you_title', 20, 2 ); 
                        function platon_thank_you_title( $thank_you_title, $order ){
                            return 'Тестовый платеж прошел успешно';
                        }  
                    }
             }

            
            // Actions
            add_action('woocommerce_receipt_platononline', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_platononline', array($this, 'check_ipn_response'));
            add_action('woocommerce_if_paton_pay_succ', array($this, 'if_platon_pay_succ'));
	
	        wp_enqueue_style( 'platononline', plugin_dir_url( __FILE__ ) . 'platononline.css');

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }

        } 

            
        public function admin_options(){ ?>
            <?php  
            $if_test = $this->test_mode = $this->get_option('test_mode'); 
             if ($if_test=='no') {?>
            <style type="text/css">
                .form-table tr:nth-of-type(3){
                    display: none;
                }
            </style>
        <?php } ?>
            

            <h3><?php _e('Platon Pay 1.7', 'woocommerce'); ?></h3>
            <?php if ($this->is_valid_for_use()) : ?>
                <button class="platon_connect"><?php _e('Подключить PSP Platon', 'woocommerce'); ?></button>
               <?php ?>
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                    <p>
                        <strong><?php _e('Сообщите в тех. поддержку platon.ua что ваш Callback Url: ') ?></strong><?php echo add_query_arg('wc-api', 'WC_Gateway_Platononline', home_url('/')); ?>
                    </p>
                </table>
            <?php else : ?>
                <div class="inline error">
                    <p>
                        <strong><?php _e('Шлюз отключен', 'woocommerce'); ?></strong>: <?php _e('Platon Online не поддерживает валюты Вашего магазина.', 'woocommerce'); ?>
                    </p>
                </div>
            <?php
            endif;
        }

        public function init_form_fields(){?>
            <?php if (is_admin()){ ?>
            <div class="platon-popup">
                <div class="closer"><span>✕</span></div>
                <form action="https://platon.ua/wp-content/themes/platon/ajax_platon_bitrix_lead.php" id="platon-form" class="popup-body" method="POST">
                    <h3><?php _e('Заявка на подключение', 'woocommerce'); ?></h3>
                    <br>
                    <input type="hidden" id="inp3" name="theme_name" value="Подключение интернет-эквайринга">
                    <input type="email" required="" name="your-email" placeholder="<?php _e('E-mail пользователя', 'woocommerce'); ?>">
                    <input type="text" required="" name="your-name" placeholder="<?php _e('Имя пользователя', 'woocommerce'); ?>"> 
                    <input type="text" class="phone_num" required="" name="your-tel" placeholder="<?php _e('Телефон', 'woocommerce'); ?>">
                    <input type="hidden" required="" value="<?php echo get_site_url(); ?>" name="your-site" placeholder="Сайт или мобильное приложение">
                    <input type="hidden" value="<?php echo add_query_arg('wc-api', 'WC_Gateway_Platononline', home_url('/')); ?>" name="your-callback-url">
                    <input type="hidden" name="your-topic" value="866">
                    <input type="hidden" name="description" value="from_module">
                    <input type="hidden" name="action" value="myaction" />
                    <button><?php _e('Отправить заявку', 'woocommerce'); ?></button>
                    <p id="succ"><?php _e('Спасибо! Ваша заявка получена. В скором времени с Вами свяжется наш менеджер.', 'woocommerce'); ?></p>
                </form>
            </div>
        <?php } ?>
            <?php $if_test = $this->test_mode = $this->get_option('test_mode'); 
             if ($if_test=='yes') {
                $readonly = array('readonly' => 'readonly'); 
             }
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Включить/Отключить', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включить', 'woocommerce'),
                    'default' => 'yes'
                ),
                'test_mode' => array(
                    'title' => __('Тестовый режим', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Включить', 'woocommerce'),
                    'default' => 'no'
                ),
                'test_mode_info' => array(
                    'title' => __('Описание', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => __('Вы можете протестировать процесс работы модуля без проведения оплат.Для включения реальных платежей – вам необходимы выключить «Режим тестирования» - и связаться с нашими специалистами для получения необходимых данных.
Для тестирования успешной оплаты картой, введите следующие реквизиты:
№ карты: 4111 1111 1111 1111
Срок: 01/22, CVV: 123', 'woocommerce'),
                    'custom_attributes' => $readonly,
                ),
                'title' => array(
                    'title' => __('Заголовок', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Заголовок, который отображается на странице оформления заказа', 'woocommerce'),
                    'default' => 'Platon Pay',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Описание', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Описание, которое отображается в процессе выбора формы оплаты', 'woocommerce'),
                    'default' => __('Оплатить через электронную платежную систему PSP Platon (<a href="https://platon.ua/" target="_blank">platon.ua</a>)', 'woocommerce'),
                ),
				'url' => array(
                    'title' => __('Url', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Url выданный platon.ua для отправки платежного POST запроса', 'woocommerce'),
                    'custom_attributes' => $readonly,
                ),
				'secret' => array(
                    'title' => __('Секретный ключ', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Ключ выданный platon.ua для идентификации Клиента', 'woocommerce'),
                    'custom_attributes' => $readonly,
                ),
                'password' => array(
                    'title' => __('Пароль', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Пароль выданный platon.ua участвующий в формировании MD5 подписи.', 'woocommerce'),
                    'custom_attributes' => $readonly,
                )
                
            );

        }

        function is_valid_for_use(){
            if (!in_array(get_option('woocommerce_currency'), array('RUB', 'UAH', 'USD', 'EUR'))) {
                return false;
            }
            return true;
        }

        function process_payment($order_id){
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order', $order->get_id(), add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );
        }

        public function receipt_page($order){
            echo '<p>' . __('Спасибо за Ваш заказ, пожалуйста, нажмите кнопку ниже, чтобы заплатить.', 'woocommerce') . '</p>';
            echo $this->generate_form($order);
        }

        public function generate_form($order_id){?>
            <?php global $woocommerce;
	        $customer = $woocommerce->customer;
	        $phoneCustomer = $customer->get_billing_phone();
	        $emailCustomer = $customer->get_billing_email();
	        $firstNameCustomer = $customer->get_billing_first_name();
	        $lastNameCustomer = $customer->get_billing_last_name();

            $order = new WC_Order($order_id);
            $action_adr = $this->url;

            $result_url = add_query_arg('wc-api', 'WC_Gateway_Platononline', home_url('/'));
            $description = '';
            foreach ($order->get_items() as $i) {
                if (version_compare( WOOCOMMERCE_VERSION , '3.0.0', '>=')) {
                    $description .= $i->get_name().', '.__('кол-во').': '. $i->get_quantity() . '; ';
                } else {
                    $description .= '( '.$i["item_meta"]["_product_id"][0].' ) ( '. $i["item_meta"]["_qty"][0] . ' )';
                }
            }
			$data= base64_encode(serialize(array('amount' => $order->order_total, 'description' => $description, 'currency' => get_woocommerce_currency())));
	
	        $sign = md5(strtoupper(
		        strrev($this->secret).
		        strrev('CC').
		        strrev($data).
		        strrev($result_url).
		        strrev($this->password)));
			
            $args = array(
                'key' => $this->secret,
                'payment' => 'CC',
                'order' => $order_id,
                'data' => $data,
                'ext1' => '',
				'ext2' => $_SERVER['REMOTE_ADDR'],
				'ext3' => '',
				'ext4' => '',
				'first_name' => $firstNameCustomer,
				'last_name' => $lastNameCustomer,
				'email' => $emailCustomer,
				'phone' => $phoneCustomer,
                'url' => $result_url,
                'sign' => $sign
            );
            
			
            $args_array = array();
            foreach ($args as $key => $value) {
                $args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }

            return
                '<form action="' . esc_url($action_adr) . '" method="POST" name="platononline_form">' .
                '<input type="submit" class="button alt" id="submit_platononline_button" value="' . __('Оплатить', 'woocommerce') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Отказаться от оплаты & вернуться в корзину', 'woocommerce') . '</a>' . "\n" .
                implode("\n", $args_array) .
                '</form>';
        }

        
        function check_ipn_response(){
			global $woocommerce;
			

	        if (sanitize_key(isset($_GET["order"]))){
		        $woocommerce->cart->empty_cart();
		        $order_id = sanitize_key($_GET["order"]);
		        $order = new WC_Order($order_id);
		        wp_redirect( $this->get_return_url( $order ));
            }
            
			if (sanitize_text_field($_REQUEST['sign'])){
				$order_id = sanitize_key($_REQUEST['order']);
				$sign = sanitize_text_field($_REQUEST['sign']);
				$ip = $_SERVER['REMOTE_ADDR'];
				
				if (isset($order_id)&& isset($sign)){
					$order = new WC_Order($order_id);
					if (isset($order)){
						$status = sanitize_text_field($_REQUEST['status']);
						$card = sanitize_text_field(isset($_REQUEST['card']) ? $_REQUEST['card'] : '');
						$email = sanitize_email(isset($_REQUEST['email']) ? $_REQUEST['email'] : '');
						$self_md5 = md5(strtoupper(strrev($email).$this->password.$order_id.strrev(substr($card,0,6).substr($card,-4))));
						$self_md5_P24 =	md5(strtoupper($this->password . $order_id ));
						if ($status == 'SALE' && $self_md5 == $sign || $status == 'SALE' && $self_md5_P24 == $sign) {
                            $if_test = $this->test_mode = $this->get_option('test_mode'); 
                             if ($if_test=='yes') {
                                $order->update_status('wc-test-pay', __('Тестовая оплата прошла успешно', 'woocommerce'));
                             }else{
    							$order->update_status('completed', __('Платеж успешно оплачен', 'woocommerce'));
                            }
						}
						else {
							$order->update_status('failed', __('Платеж не оплачен', 'woocommerce'));
						}
					}
				}
				echo '1';
			}
			exit;
		}



    }
	
    function ppw_woocommerce_add_platononline_gateway($methods) {
        $methods[] = 'WC_Gateway_Platononline';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'ppw_woocommerce_add_platononline_gateway');


    
	
}

// custom css and js
add_action('admin_enqueue_scripts', 'cstm_css_and_js');
 
function cstm_css_and_js($hook) {
if (is_admin()){
    wp_enqueue_style('platon-admin-css', plugins_url('platon-admin.css',__FILE__ ));
    wp_enqueue_script('platon-mask', plugins_url('jquery.maskedinput.js',__FILE__ ),array('jquery'), '1.0', true );
   wp_enqueue_script('platon-scripts', plugins_url('common.js',__FILE__ ),array('jquery'), '1.0', true );
}
 
    
}
add_action( 'init', 'register_my_new_order_statuses' );

function register_my_new_order_statuses() {
    register_post_status( 'wc-test-pay', array(
        'label'                     => _x( 'Тестовый платеж', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Тестовый платеж <span class="count">(%s)</span>', 'Тестовый платеж<span class="count">(%s)</span>', 'woocommerce' )
    ) );
}

add_filter( 'wc_order_statuses', 'my_new_wc_order_statuses' );

// Register in wc_order_statuses.
function my_new_wc_order_statuses( $order_statuses ) {
    $order_statuses['wc-test-pay'] = _x( 'Тестовый платеж', 'Order status', 'woocommerce' );

    return $order_statuses;
}
function custom_bulk_admin_footer() {
            global $post_type;

            if ( $post_type == 'shop_order' ) {
                ?>
                    <script type="text/javascript">
                        jQuery(document).ready(function() {
                            jQuery('<option>').val('mark_test-pay').text('<?php _e( 'Mark test-pay', 'textdomain' ); ?>').appendTo("select[name='action']");
                            jQuery('<option>').val('mark_test-pay').text('<?php _e( 'Mark test-pay', 'textdomain' ); ?>').appendTo("select[name='action2']");   
                        });
                    </script>
                <?php
            }
        }
?>