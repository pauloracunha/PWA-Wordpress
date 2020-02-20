# PWA-Wordpress
Module PWA for Wordpress theme
Um módulo PWA para temas do Wordpress!

Os Progressive Web Apps fornecem uma experiência instalável, semelhante a um aplicativo, em computadores e dispositivos móveis que são criados e entregues diretamente pela Web. Eles são aplicativos da web que são rápidos e confiáveis. E o mais importante, são aplicativos da web que funcionam em qualquer navegador. Se você está construindo um aplicativo da Web hoje, já está no caminho da criação de um aplicativo da Web progressivo.

# Install

 1. Clone o projeto dentro da pasta do seu tema;
 2. Gere as Chaves da API (VAPID) no site https://tools.reactpwa.com/vapid
 3. Abra o arquivo [pwa.php](https://github.com/pauloresolve/PWA-Wordpress/blob/master/pwa.php) e insira as referidas chaves da API;
 4. Inclua o arquivo [pwa.php](https://github.com/pauloresolve/PWA-Wordpress/blob/master/pwa.php) no início do arquivo [functions.php](https://github.com/WordPress/WordPress/blob/master/wp-content/themes/twentytwenty/functions.php) de seu tema.

# Stylization
Estilize seu [Progressive Web App](https://developers.google.com/web/fundamentals/codelabs/your-first-pwapp?hl=pt-br) mudando cores, urls e ícones no arquivo manifest.php.

# Push Notification
Envie Push Notifications através do painel administrativo do Wordpress no menu lateral Send News->Enviar.

# Pretensões

 - Desenvolver o painel administrativo deste módulo para que mostre as notificações enviadas e possibilite cancelar ou duplicar notificações;
 - Refatorar o código para orientação à objetos e melhor divisão em arquivos, além de limpar o código;
 - Identificação automática de client tokens inválidos ou inexistentes.
