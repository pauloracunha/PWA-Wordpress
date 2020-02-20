<?php

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/webpush/vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
if(isset($_POST['user_id'])){
	global $wpdb;
	global $publicKey;
	global $privateKey;
	foreach($_POST['user_id'] as $user_id){
		$tablename = $wpdb->prefix . 'pwa_subs';
		$res = $wpdb->get_results('SELECT * FROM '.$tablename.' WHERE user_id = '. $user_id);
		
		foreach($res as $subs){
			$subscription = array(
				"endpoint"	=> $subs->pwa_endpoint,
				"publicKey"	=> $subs->pwa_keys,
				"authToken"	=> $subs->pwa_auth,
				"contentEncoding"=>$subs->contentEncoding
			);
			$subscriptions = [
				    [
				        'subscription' => Subscription::create([
				            'endpoint' => $subscription['endpoint'], // Firefox 43+,
				            'publicKey' => $subscription['publicKey'], // base 64 encoded, should be 88 chars
				            'authToken' => $subscription['authToken'], // base 64 encoded, should be 24 chars
				        ]),
				    ], [
				        'subscription' => Subscription::create([
				            'endpoint' => $subscription['endpoint'], // Chrome
				            'contentEncoding' => $subscription['contentEncoding'],
				        ]),
				    ], [
				        'subscription' => Subscription::create([
				            'endpoint' => $subscription['endpoint'],
				            'publicKey' => $subscription['publicKey'],
				            'authToken' => $subscription['authToken'],
				            'contentEncoding' => $subscription['contentEncoding'], // one of PushManager.supportedContentEncodings
				        ]),
				    ], [
				          'subscription' => Subscription::create([ // this is the structure for the working draft from october 2018 (https://www.w3.org/TR/2018/WD-push-api-20181026/) 
				              "endpoint" => $subscription['endpoint'],
				              "keys" => [
				                  'p256dh' => $subscription['publicKey'],
				                  'auth' => $subscription['authToken']
				              ],
				          ]),
				      ],
				];
			$auth = array(
				'VAPID' => array(
					'subject' => get_home_url(),
					'publicKey' => $publicKey,
					'privateKey' => $privateKey
				),
			);
			
			$notify_id = isset($_POST['tag']) ? $_POST['tag'] : md5(uniqid(rand(), true));
			$notification = json_encode(array(
				"user"		=> $user_id,
				"private"	=> isset($_POST['private']) ? 1 : 0,
				"title"		=> isset($_POST['title']) ? $_POST['title'] : get_bloginfo('name'),
				"options"	=> array(
						"body"		=> isset($_POST['body']) ? $_POST['body'] : 'Você tem uma nova mensagem!',
						"icon"		=> isset($_POST['icon']) ? wp_get_attachment_image_src($_POST['icon'])[0] : get_site_icon_url(),
						"actions"	=> isset($_POST['actions']) ? $_POST['actions'] : array(),
						"badge"		=> isset($_POST['badge']) ? wp_get_attachment_image_src($_POST['badge'])[0] : '',
						"data"		=> array(
								"data"	=> isset($_POST['data']) ? $_POST['data'] : '',
								"url"	=> isset($_POST['url']) ? $_POST['url'] : '',
							),
						"image"		=> isset($_POST['image']) ? wp_get_attachment_image_src($_POST['image'], array(800,400))[0] : '',
						"requireInteraction"=> isset($_POST['requireInteraction']) ? 1 : 0,
						"tag"		=> $notify_id,
						"timestamp"	=> isset($_POST['timestamp']) ? $_POST['timestamp'] : (time()*1000),
						"vibrate"	=> isset($_POST['vibrate']) ? array_map('intval', explode(',',substr($_POST['vibrate'], 1, (strlen($_POST['vibrate'])-2)))) : '',
					)
			));
			$webPush = new WebPush($auth);
			foreach ($subscriptions as $subscription) {
				$res = $webPush->sendNotification(
				    $subscription['subscription'],
				    $notification
				);
			}
			// handle eventual errors here, and remove the subscription from your server if it is expired
			$user = get_userdata($user_id);
			$success = false;
			$errorCode = array();
			foreach ($webPush->flush() as $report) {
			    $endpoint = $report->getRequest()->getUri()->__toString();
			    if($report->isSuccess()){
			    	$success = true;
			    } else {
			    	$errorCode[] = $report->getReason();
			    }
			}
			if ($success) {
				echo '<div class="alert alert-success" role="alert">Notificação enviada com sucesso'. ($user ? ' para '.$user->first_name.' '.$user->last_name : '').'!</div>';
				$tablename = $wpdb->prefix.'pwa_pushs';
				$sql = array(
					'subs_id'	=> $user_id,
					'notify_id'	=> $notify_id,
					'content'	=> $notification,
					'date'		=> time(),
				);
				$type = array(
					'subs_id'	=> '%d',
					'notify_id'	=> '%s',
					'content'	=> '%s',
					'date'		=> '%s',
				);
				if(!($wpdb->insert($tablename, $sql, $type)>0)){
					exit(sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), __( 'Houve um erro: '.sw_wpdb_print_error().'  , tente novamente!', 'sleepwear' ) ));
				}
			} else {
			        echo '<div class="alert alert-danger" role="alert">Problemas ao enviar notificação'. ($user ? ' para '.$user->first_name.' '.$user->last_name : '').'!<br><code>'.implode('<br>',$errorCode).'</code>
			        	<br><button type="button" class="btn btn-danger" data-endpoit="'.$endpoint.'">Descadastrar Push</button>
			        </div>';
			}
		}
	}
}
include get_template_directory().'/includes/sw_media_enqueue.php';
?>
<link href="https://getbootstrap.com/docs/4.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<div class="col-md-8 order-md-1">
      <h4 class="mb-3">Enviar Notificações</h4>
      <form class="needs-validation" method="POST">
        <div class="row">
          <div class="col-md-9 mb-3">
		<div class="form-group">
		    	<label for="user_id">Selecione os destinatários <span style="font-weight:bold;font-size:75%">(Segure ctrl para selecionar mais de um)<span></label>
			<select multiple class="form-control" id="user_id" name="user_id[]">
				<?php 
				global $wpdb;
				$results = $wpdb->get_results("SELECT user_id FROM ".$wpdb->prefix."pwa_subs");
				$used = array();
				$desconhecido = 0;
				foreach($results as $result){
					if(in_array($result->user_id, $used)){
						continue;
					}
					if($result->user_id=='0'){
						$desconhecido += 1;
						continue;
					}
					$used[] = $result->user_id;
					if(class_exists('WC_Session_Handler')){
						$session_handler = new WC_Session_Handler();
						$session = $session_handler->get_session($result->user_id);
						$cart_items = maybe_unserialize($session['cart']);
						$hasCart = (count($cart_items)>0);
					} else {
						$hasCart = false;
					}
					$user = get_userdata($result->user_id);
					$name = $user->first_name.' '.$user->last_name;
					?>
					<option value="<?php echo $result->user_id; ?>"><?php echo ($name!=' ' ? $name : '(Desconhecido)').($hasCart ? ' ('.count($cart_items).' itens no carrinho)' : ''); ?></option>
				<?php } ?>
					<?php if($desconhecido>0){ ?><option value="0"><?php echo $desconhecido; ?> desconhecidos</option><?php } ?>
			</select>
	  	</div>
          </div>
          <div class="col-md-3 mb-3">
		<div class="form-group form-check" style="height:1.5em;" title="Mostrar notificação apenas para usuários logados.">
			<input type="checkbox" style="float:left;margin:6px;" id="private" name="private" value="private">
			<label class="form-check-label" style="float:left" for="private">Privado</label>
		</div>
		<div class="form-group form-check" style="height:1.5em;" title="Vibrar a caixa de notificação.">
			<input type="checkbox" style="float:left;margin:6px;" id="vibrate" name="vibrate" value="[300,500,100,500,200]">
			<label class="form-check-label" style="float:left" for="vibrate">Vibrar</label>
		</div>
		<div class="form-group form-check" style="height:1.5em;" title="Se desmarcado a notificação desaparece depois de 20 segundos.">
			<input type="checkbox" style="float:left;margin:6px;" id="requireInteraction" name="requireInteraction" value="requireInteraction">
			<label class="form-check-label" style="float:left" for="requireInteraction">Interação</label>
		</div>
	  </div>
        </div>

        <div class="mb-3">
          <label for="title">Título</label>
          <div class="input-group">
            <input type="text" class="form-control" id="title" name="title" placeholder="título" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="body">Mensagem</label>
          <div class="input-group">
            <input type="text" class="form-control" id="body" name="body" placeholder="Corpo da mensagem">
          </div>
        </div>
        
        <div class="mb-3">
          <label for="url">URL</label>
          <div class="input-group">
            <input type="text" class="form-control" id="url" name="url" placeholder="URL de destino ao clicar na notificação">
          </div>
        </div>
        
	<div class="mb-3">
          <label for="icon">Ícone</label>
          <div class="input-group">
          	<?php get_media_selector_button('icon','Ícone'); ?>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="badge" title="Ícone menor que aparecerá em dispositivos com pouco espaço">Mini ícone</label>
          <div class="input-group">
          	<?php get_media_selector_button('badge','Mini ícone'); ?>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="image" title="Imagem da notificação (opcional)">Imagem</label>
          <div class="input-group">
          	<?php get_media_selector_button('image', 'Imagem'); ?>
          </div>
        </div>
        
        <hr class="mb-4">
        <button class="btn btn-primary btn-lg btn-block" type="submit">Enviar Notificação</button>
      </form>
    </div>