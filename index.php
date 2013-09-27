<?php
/**
 * Plugin Name: Pandz Gallery
 * Plugin URI: http://zapf.se
 * Description: A brief description of the Plugin.
 * Version: 1.0
 * Author: Alexander Zeiher
 * Author URI: http://zapf.se
 */
 
 /*Javascripts and styles*/
 function pandz_gallery_scripts() {
	wp_enqueue_style('thickbox', '', '', null);
	wp_enqueue_style('pandz_gallery_css', plugins_url( '/css/pandz-gallery.css' , __FILE__ ), false, null);
	

	wp_enqueue_script('jquery');
	wp_enqueue_script( 'jquery_cycle_all', plugins_url( '/js/responsiveslides.min.js' , __FILE__ ), array('jquery'), null, true );
	wp_enqueue_script( 'jquery_collage_plus', plugins_url( '/js/jquery.collagePlus.min.js' , __FILE__ ), array('jquery'), null );
	wp_enqueue_script( 'jquery_remove_plus', plugins_url( '/js/jquery.removeWhitespace.min.js' , __FILE__ ), array('jquery'), null );
	wp_enqueue_script( 'jquery_caption_plus', plugins_url( '/js/jquery.collageCaption.min.js' , __FILE__ ), array('jquery'), null );
	wp_enqueue_script('thickbox', '', '', null);
}
add_action('wp_enqueue_scripts', 'pandz_gallery_scripts');
 
/**
 * 
 *
 * Re-create the [gallery] shortcode
 *
 * 
 */
 
function pandz_gallery($attr) {
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) )
			$attr['orderby'] = 'post__in';
		$attr['include'] = $attr['ids'];
	}

	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
		return $output;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	 extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post->ID,
		'itemtag'    => 'li',
		'captiontag' => 'p',
		'columns'    => 3,
		'size'       => 'thumbnail',
		'include'    => '',
		'exclude'    => '',
		'slideshow'	 => 'false'
	 ), $attr));

	 
	if(isset($attr['slideshow']) && 'true' == $attr['slideshow'])
		$size = 'large';
		
		
	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';

	if ( !empty($include) ) {
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty($exclude) ) {
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
		return $output;
	}

	$captiontag = tag_escape($captiontag);
	$columns    = intval($columns);
	  
	$selector   = "gallery-{$instance}";


	if (apply_filters('use_default_gallery_style', true)) {
	}

	$size_class = sanitize_html_class( $size );
	
	if(isset($attr['slideshow']) && 'true' == $attr['slideshow']) {
		$gallery_div = "<div id=\"slideshow-gallery-{$id}\" class=\"slideshow-gallery\">
			<script type=\"text/javascript\">
				jQuery(document).ready(function($){
					
                    $('#{$selector}').responsiveSlides({
                        pause: true,
                        speed: 500,
                        timeout: 3000,
                        pager: true,
                        manualControls: '#pager-{$id}',  
                        
                    });
					
				});

			</script>
			<ul id='$selector' class='gallery galleryid-{$id}'>";
			
			$pager_id = "pager-{$id}";
	} else {
		$gallery_div .= "
			<script type=\"text/javascript\">
					

    // All images need to be loaded for this plugin to work so
    // we end up waiting for the whole window to load in this example
     jQuery(window).load(function() {
        jQuery(document).ready(function(){
			collage();
            jQuery('.Collage').collageCaption();
        });
		 $('.Image_Wrapper').BlackAndWhite({
			hoverEffect : true, // default true
			// set the path to BnWWorker.js for a superfast implementation
			webworkerPath : false,
			// for the images with a fluid width and height 
			responsive:true,
			// to invert the hover effect
			invertHoverEffect: false,
			speed: { //this property could also be just speed: value for both fadeIn and fadeOut
				fadeIn: 200, // 200ms for fadeIn animations
				fadeOut: 800 // 800ms for fadeOut animations
			},
			onImageReady:function(img) {
				// this callback gets executed anytime an image is converted
			}
		});
    });


    // Here we apply the actual CollagePlus plugin
    function collage() {
        jQuery('.Collage').removeWhitespace().collagePlus(
            {
                'fadeSpeed'     : 2000,
                'targetHeight'  : 190
            }
        );
    };

    // This is just for the case that the browser window is resized
    var resizeTimer = null;
    jQuery(window).bind('resize', function() {
        // hide all the images until we resize them
        jQuery('.Collage .Image_Wrapper').css(\"opacity\", 0);
        // set a timer to re-apply the plugin
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(collage, 200);
    });

    
			</script>
			<div id='$selector' class='Collage thumbnails gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>
		";
	}
	
	$output      = apply_filters('gallery_style', $gallery_style . "\n\t\t" . $gallery_div);

	$i = 0;
	
	foreach ($attachments as $id => $attachment) {
	
		if(isset($attr['slideshow']) && 'true' == $attr['slideshow']) {
		
			$link = wp_get_attachment_image($id, $size, false);
			$output .= "
				<{$itemtag} class=\"gallery-item\">
				$link
			";
			if ($captiontag && trim($attachment->post_excerpt)) {
				$output .= "
				<{$captiontag} class=\"wp-caption-text gallery-caption\">
				" . wptexturize($attachment->post_excerpt) . "
				</{$captiontag}>";
			}
			$output .= "</{$itemtag}>";
			
		} else {
		
			$link =  wp_get_attachment_link($id, $size, false, false);
			$link = preg_replace(array('/<a class="/', '/title(.*?)\>/'), array('<a rel="'.$selector.'" class="thickbox ', '>'),  $link);

			
			$output .= "
				
				<div class=\"Image_Wrapper\" data-caption=\"" . wptexturize($attachment->post_excerpt) . "\">
					$link
				</div>
			";
		}
		
		if ($columns > 0 && ++$i % $columns == 0) {
		  $output .= '';
		}
	}

	
	if(isset($attr['slideshow']) && 'true' == $attr['slideshow']) {
		$output .= "</ul>\n<div id=\"{$pager_id}\" class=\"pagers\"></div></div>\n";
	} else {
		$output .= "</div>\n";
	}
  return $output;
}

remove_shortcode('gallery');
add_shortcode('gallery', 'pandz_gallery');

 
 ?>