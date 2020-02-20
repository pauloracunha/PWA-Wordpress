<?php 
echo '{
	"name": "'.get_bloginfo('name').' - '.get_bloginfo('description').'",
	"short_name": "'.substr(get_bloginfo('name'), 0, 11).'",
	"theme_color": "#0e0ea0",
	"background_color": "#0e0ea0",
	"display": "standalone",
	"orientation": "any",
	"start_url": "/",
	"scope": "/",
	"icons": [
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(72,72)).'",
	      "sizes": "72x72",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(96,96)).'",
	      "sizes": "96x96",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(128,128)).'",
	      "sizes": "128x128",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(144,144)).'",
	      "sizes": "144x144",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(152,152)).'",
	      "sizes": "152x152",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(192,192)).'",
	      "sizes": "192x192",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(384,384)).'",
	      "sizes": "384x384",
	      "type": "image/png"
	    },
	    {
	      "src": "'.wp_get_attachment_image_url(get_theme_mod( 'custom_logo' ), array(512,512)).'",
	      "sizes": "512x512",
	      "type": "image/png"
	    }
	],
	"splash_pages": null
	}';