<?php
/*
 * Init PWA in Wordpress theme
 * 
 * Include 'pwa.php' file in your function theme
 * @author PauloCunha
 * @version 1.0
 **/

// Generate VAPID in https://tools.reactpwa.com/vapid
$publicKey = 'YOUR-PUBLIC-KEY';
$privateKey= 'YOUR-PRIVATE-KEY';

if(strpos($_SERVER['REQUEST_URI'],'worker.js') !== false){
	header('Content-Disposition: attachment; filename=worker.js'); 
	header('Content-Type: text/javascript');
	include get_template_directory().'/pwa/worker.php';
	exit;
} else if(strpos($_SERVER['REQUEST_URI'],'manifest.json') !== false){
	include 'manifest.php';
	exit;
}
add_action( 'after_switch_theme', 'pwa_installation', 10, 2 );
function pwa_installation($oldname, $oldtheme=false){
	if(!class_exists( 'WooCommerce' )){
		if(get_page_by_title('Configurações de Notificação')!=null){
			wp_delete_post(get_page_by_title('Configurações de Notificação')->ID);
		}
		$new_page_id = wp_insert_post( array(
				'post_title'     => 'Configurações de Notificação',
				'post_type'      => 'page',
				'post_name'      => 'client-notifications',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_content'   => '',
				'post_status'    => 'publish',
				'post_author'    => get_user_by( 'id', 1 )->user_id,
				'menu_order'     => 0,
				'page_template'  => 'pwa/client_dashboard.php'
			) );
		update_option( 'pwa_client_config-page-id', $new_page_id );
	}
	global $wpdb;
	$tables = array(
		"pwa_subs" => array(
			"user_id"	=> "INT",
			"pwa_endpoint"	=> "TEXT",
			"pwa_keys"	=> "TEXT",
			"pwa_auth"	=> "TEXT",
			"contentEncoding" => "TEXT"
			),
		"pwa_pushs"=> array(
			"subs_id"	=> "INT",
			"notify_id"	=> "TEXT",
			"content"	=> "TEXT",
			"date"		=> "TEXT",
			),
	);
	$charset_collate = $wpdb->get_charset_collate();
	foreach($tables as $table=>$columns){
		$tablename = $wpdb->prefix . $table;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '".$tablename."'" ) != $tablename ) {
			$sql = "CREATE TABLE $tablename 
				( id INT NOT NULL AUTO_INCREMENT, ";
			foreach($columns as $column=>$type){
				$sql.= $column.' '.$type.' NOT NULL, ';
			}
			$sql.= "PRIMARY KEY  (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
}

function pwa_config_panel(){
	$icon_svg = 'data:image/png;base64,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';
	add_menu_page('Send News', 'Send News', 'manage_options', 'pwa', 'pwa_module_dashboard', $icon_svg, 7);
	add_submenu_page('pwa', 'Painel de Configurações', 'Painel', 'manage_options', 'pwa', 'pwa_module_dashboard');
	add_submenu_page('pwa', 'Enviar Notificação', 'Enviar', 'manage_options', 'pwa-send', 'pwa_module_send_notifications');
}
add_action('admin_menu', 'pwa_config_panel');

function pwa_module_dashboard(){
	get_template_part('pwa/admin/pwa_dashboard');
}
function pwa_module_send_notifications(){
	get_template_part('pwa/admin/pwa_send_notifications');
}

if(class_exists( 'WooCommerce' ) ? true : false){
	$end_point = 'clientnotifications';
	function pwa_add_client_notification_configs_endpoint() {
		global $end_point;
	    add_rewrite_endpoint( $end_point, EP_ROOT | EP_PAGES );
	}
	add_action( 'init', 'pwa_add_client_notification_configs_endpoint' );
	
	function pwa_client_notification_configs_query_vars( $vars ) {
	    global $end_point;
	    $vars[] = $end_point;
	    return $vars;
	}
	add_filter( 'query_vars', 'pwa_client_notification_configs_query_vars', 0 );
	  
	function pwa_add_client_notification_configs_link_my_account( $items ) {
	    global $end_point;
	    $aux_items = $items;
	    $length = count($items);
	    $i = 0;
	    foreach($items as $key=>$item){
	    	$i++;
	    	if($i==$length){
	    		$aux_items[$end_point] = 'Configurações';
	    	}
	    	$aux_items[$key] = $item;
	    }
	    return $aux_items;
	}
	add_filter( 'woocommerce_account_menu_items', 'pwa_add_client_notification_configs_link_my_account' );
	
	function endpoint_title( $title ) {
		global $wp_query;
		global $end_point;
		$is_endpoint = isset( $wp_query->query_vars[ $end_point ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'Configurações da conta', 'woocommerce' );
			remove_filter( 'the_title', 'endpoint_title' );
		}
		return $title;
	}
	add_filter( 'the_title', 'endpoint_title' );
	
	function pwa_client_notification_configs_content() {
		include __DIR__.'/client_dashboard.php';
	}
	add_action( 'woocommerce_account_'.$end_point.'_endpoint', 'pwa_client_notification_configs_content' );
}
add_action( 'wp_loaded', 'pwa_scripts');
if(!function_exists('pwa_scripts')){
	function pwa_scripts(){
		global $publicKey;
		$nonce = wp_create_nonce('pwa_subscription');
		$variaveis_javascript = array(
			'pwa_subscription'	=> $nonce,
			'xhr_url'		=> admin_url('admin-ajax.php')
		);
		wp_enqueue_script('main-progressiveweb', get_template_directory_uri().'/pwa/main.js', 'jquery', '1.0.0', false);
		wp_localize_script( 'main-progressiveweb',
				'js_global',
				array(
					'user_id' => get_current_user_id(),
					'pwa_subscription' => $nonce,
					'xhr_url' => admin_url('admin-ajax.php'),
					'publicKey' => $publicKey
				)
			);
	}
}
if(!function_exists('pwa_set_service_worker')){
	add_action('wp_head', 'pwa_set_service_worker');
	function pwa_set_service_worker(){
		?>
		<link rel="manifest" href="<?php echo get_home_url(); ?>/manifest.json">
		<script>
			if ('serviceWorker' in navigator && 'PushManager' in window) {
				navigator.serviceWorker.register('<?php echo get_home_url(); ?>/worker.js')
				.then(function (swReg) {
					console.log('service worker registered');
					swRegistration = swReg;
					query = window.location.href.split('?');
					if(Notification.permission !== "granted") {
						if(query[1]!=undefined){
							partes = query[1].split('&');
							partes.forEach(function (parte) {
							    var chaveValor = parte.split('=');
							    var chave = chaveValor[0];
							    var valor = decodeURI(chaveValor[1]);
							    if(chave=='msg'){
								alert(valor);
							    	return;
							    }
							});
						}
					}
					window.history.pushState({push:true}, '', query[0]);
				})
				.catch(function (e) {
					console.warn('service worker failed: '+e);
				});
			}
		</script>
		<?php
	}
}
if(!function_exists('pwa_btn_action')){
	add_action('wp_footer', 'pwa_btn_action');
	function pwa_btn_action(){
		?>
		<style>
			#resolve-notification{
				position: fixed;
				background-color: white;
				bottom: 1vh;
				left: 1vw;
				width: 3vw;
				min-width: 35px;
				height: 3vw;
				min-height: 35px;
				border-radius: 100vh;
				opacity: 0.5;
				z-index: 99999999999999999999;
			}
			#resolve-notification:hover{
				opacity: 1;
			}
			#resolve-notification>img{
				width: 100%;
				height: auto;
				cursor: pointer;
			}
			#resolve-notification>span {
			    position: absolute;
			    bottom: -.5em;
			    left: 3vw;
			    width: 17vw;
			    min-width: 200px;
			    padding: .5em;
			    display: none;
			    background-color: white;
			    border-radius: .2em;
			    border: 1px solid black;
			    margin-left: .4em;
			}
			#resolve-notification:hover>span{
				display:block;
			}
			#resolve-notification>span>i {
			    position: absolute;
			    left: -.4em;
			    top: calc(50% - .5em);
			    height: 100%;
			}
			#resolve-notification>span>button{
				padding: .5em;
				border-radius: .1em;
				background-color: var(--sleep-bg-third)
			}
		</style>
		<span id="resolve-notification">
			<img src="<?php echo get_template_directory_uri(); ?>/pwa/assets/img/notification-no.png">
			<span>
				<i class="fas fa-caret-left"></i>
			</span>
		</span>
		<script>
		$(window).on('load', function(){
			swRegistration.pushManager.getSubscription()
			  .then(function(subscription) {
			    if(!(subscription === null)){
			    	initialiseUI();
			    } else {
			    	setTimeout(function(){ initialiseUI(); }, 7000);
			    }
			  });
		});
		</script>
		<?php
	}
}
if(!function_exists('pwa_metas')){
	add_action('wp_head', 'pwa_metas');
	function pwa_metas(){
		?>
		<meta name="theme-color" content="<?php echo get_theme_mod('sleep_text_first_color', '#0e3f77'); ?>">
		<meta name="background-color" content="<?php echo get_theme_mod('sleep_bg_first_color', '#0e3f77'); ?>">
		<?php
	}
}

add_action( 'wp_ajax_pwa_subscribe_client', 'pwa_subscribe_client' );
add_action( 'wp_ajax_nopriv_pwa_subscribe_client', 'pwa_subscribe_client' );
function pwa_subscribe_client(){
	if( ! wp_verify_nonce( $_POST['pwa_subscription'], 'pwa_subscription' ) ) {
		echo '401';
		wp_die();
	}
	if(!isset($_POST['endpoint'])){
		exit('404');
	}
	if(!isset($_POST['user_id'])){
		$user_id = 0;
	} else {
		$user_id = $_POST['user_id'];
	}
	
	global $wpdb;
	$tablename = $wpdb->prefix."pwa_subs";
	$endpoint = $_POST['endpoint'];
	if($wpdb->get_var("SELECT COUNT(*) FROM $tablename WHERE pwa_endpoint = '".$endpoint."'")>0){
		$wpdb->update($tablename, array("user_id"=>$user_id), array("pwa_endpoint" => $endpoint), array("user_id" => "%s"), array("pwa_endpoint" => "%s"));
		echo "User subscribed!";
		wp_die();
	}
	
	$keys = $_POST['publicKey'];
	$auth = $_POST['authToken'];
	$contentEncoding = $_POST['contentEncoding'];
	
	$sql = array(
		'user_id'	=> $user_id,
		'pwa_endpoint'	=> $endpoint,
		'pwa_keys'	=> $keys,
		'pwa_auth'	=> $auth,
		'contentEncoding' => $contentEncoding,
	);
	$type = array(
		'user_id'	=> '%s',
		'pwa_endpoint'	=> '%s',
		'pwa_keys'	=> '%s',
		'pwa_auth'	=> '%s',
		'contentEncoding' => '%s',
	);
	if(!($wpdb->insert($tablename, $sql, $type)>0)){
		exit(sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), __( 'Houve um erro: '.sw_wpdb_print_error().'  , tente novamente!', 'resolve' ) ));
	}
	echo "User subscribed!";
	wp_die();
	exit();
}
