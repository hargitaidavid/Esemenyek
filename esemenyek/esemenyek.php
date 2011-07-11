<?php
/*
Plugin Name: Események
Plugin URI: http://minosegiweb.hu
Description: Esemény tartalomtípus létrehozása és kezelése
Author: Hargitai Dávid
Version: 0.1
Author URI: http://minosegiweb.hu
*/

define('ALAP_TERKEP_SZELESSEG', 250);
define('ALAP_TERKEP_MAGASSAG', 250);
define('ALAP_TERKEP_NAGYITAS', 15);

class Esemenyek {
	var $meta_fields = array(
	    'esemeny-idopont-kezdo',
	    'esemeny-idopont-befejezo',
	    'esemeny-helyszin-iranyitoszam',
	    'esemeny-helyszin-varos',
	    'esemeny-helyszin-cim',
	    'esemeny-google-terkep',
	    'esemeny-google-terkep-lng',
	    'esemeny-google-terkep-lat',
	    'esemeny-google-terkep-szelesseg',
	    'esemeny-google-terkep-magassag',
	    'esemeny-google-terkep-nagyitas',
	    'esemeny-google-terkep-mutato-felirat',
	    'esemeny-google-terkep-info-buborek-tartalom'
	);
	
	var $unescaped_meta_fields = array(
	    'esemeny-google-terkep-info-buborek-tartalom'
	);
	
	function Esemenyek()
	{
		// Register custom post types
		register_post_type('mw_esemeny', array(
			'label' => __('Események'),
			'singular_label' => __('Esemény'),
			'public' => true,
			'show_ui' => true, // UI in admin panel
			'_builtin' => false, // It's a custom post type, not built in
			'_edit_link' => 'post.php?post=%d',
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array("slug" => "esemenyek"), // Permalinks
			'query_var' => "esemeny", // This goes to the WP_Query schema
			'supports' => array('title','page_attributes','thumbnail','excerpt','editor' /*,'custom-fields'*/) // Let's use custom fields for debugging purposes only
		));
		
		flush_rewrite_rules();
		
		add_filter("manage_edit-esemeny_columns", array(&$this, "edit_columns"));
		add_action("manage_posts_custom_column", array(&$this, "custom_columns"));
		
		// Register custom taxonomy
		$labels = array(
            'name' => __( 'Események jellege' ),
            'singular_name' => __( 'Jelleg' ),
/*            'search_items' =>  __( 'Tulajdonságok keresése' ),
            'popular_items' => __( 'Népszerű tulajdonságok' ),
            'all_items' => __( 'Minden tulajdonság' ),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __( 'Tulajdonság szerkesztése' ), 
            'update_item' => __( 'Tulajdonság frissítése' ),
            'add_new_item' => __( 'Új tulajdonság felvétele' ),
            'new_item_name' => __( 'Új tulajdonság neve' ),
            'separate_items_with_commas' => __( 'Tulajdonságok vesszővel elválasztva' ),
            'add_or_remove_items' => __( 'Tulajdonságok hozzáadása vagy eltávolítása' ),
            'choose_from_most_used' => __( 'Legtöbbször használt tulajdonságok' ),
            'menu_name' => __( 'Tulajdonságok' ),*/
        );
        register_taxonomy('jelleg','mw_esemeny',array(
            'hierarchical' => false,
            'labels' => $labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'jelleg' ),
        ));

		// Admin interface init
		add_action("admin_init", array(&$this, "admin_init"));
		add_action("admin_init", array(&$this, "es_register_settings"));
		add_action("admin_menu", array(&$this, "es_menu"));
		add_action("template_redirect", array(&$this, 'template_redirect'));
		
		// Insert post hook
		add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
		
		add_shortcode('esemenyek', array(&$this, "sabloncimke_esemenyek"));
		add_shortcode('esemeny', array(&$this, "sabloncimke_esemeny"));
		
	}
	
	function es_menu()
	{ 
        add_options_page(
            __('Események beállítási oldala','es-plugin'), 
            __('Események beállításai','es-plugin'),
            'administrator', 
            __FILE__,
            array(&$this, 'es_settings_page')); 
    } 
    
    function es_register_settings()
    { 
        //register our array of settings 
        register_setting( 'es-settings-group', 'es_options' ); 
    }
	
	function es_bekapcsolas()
    {
        $oldalcim = 'Események';
        
		$esemenyek_oldal = array(
            'post_title' => $oldalcim,
            'post_type' => 'page',
            'post_content' => '[esemenyek]',
            'post_status' => 'publish',
            'post_author' => 1,
            'ping_status' => 'closed',
            'comment_status' => 'closed'
        );

        // Oldal létrehozása, amennyiben még nem hoztuk létre
        $oldal = get_page_by_title($oldalcim);
        if(empty($oldal))
        {
            wp_insert_post( $esemenyek_oldal );
        }
    }
	
	// Template selection
	function template_redirect()
	{
		global $wp;
		if ($wp->query_vars["post_type"] == "mw_esemeny")
		{
		    //$plugin_path = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
			$plugin_path = 'wp-content/plugins/esemenyek';
			$beallitasok = get_option('es_options');
			
			wp_register_style( 'esemenyekCss', $plugin_path.'/css/esemenyek.css' );
            wp_enqueue_style( 'esemenyekCss' );
			
			if(is_single())
			{
        	    $apiKulcs = $beallitasok['google_api_key'];
        	    
			    wp_register_script('googleAjaxApiJs', 'http://www.google.com/jsapi?key='.$apiKulcs);
                wp_enqueue_script( 'googleAjaxApiJs' );
                wp_register_script('googleMapApiJs', 'http://maps.google.com/maps/api/js?sensor=false');
                wp_enqueue_script( 'googleMapApiJs' );
                wp_register_script('googleMapJs', plugins_url('/js/google_map.js', __FILE__));
                wp_enqueue_script( 'googleMapJs' );
                
			    if(file_exists(TEMPLATEPATH . '/esemeny_sablon.php'))
    			{
    			    include(TEMPLATEPATH . '/esemeny_sablon.php');
    			}
    			else
    			{
    			    include($plugin_path . "/esemeny_sablon.php");
    			}
    		}
			
			die();
		}
	}
	
	// When a post is inserted or updated
	function wp_insert_post($post_id, $post = null)
	{
		if ($post->post_type == "mw_esemeny")
		{
			// Loop through the POST data
			foreach ($this->meta_fields as $key)
			{
				$value = @$_POST[$key];
				if (empty($value))
				{
					delete_post_meta($post_id, $key);
					continue;
				}

				// If value is a string it should be unique
				if (!is_array($value))
				{
				    if(in_array($key, $this->unescaped_meta_fields))
				    {
				        // Update meta
    					if (!update_post_meta($post_id, $key, $value))
    					{
    						// Or add the meta data
    						add_post_meta($post_id, $key, $value);
    					}
				    }
				    else
				    {
				        // Update meta
    					if (!update_post_meta($post_id, $key, esc_attr($value)))
    					{
    						// Or add the meta data
    						add_post_meta($post_id, $key, esc_attr($value));
    					}
				    }
				}
				else
				{
					// If passed along is an array, we should remove all previous data
					delete_post_meta($post_id, $key);
					
					// Loop through the array adding new values to the post meta as different entries with the same name
					foreach ($value as $entry)
						add_post_meta($post_id, $key, esc_attr($entry));
				}
			}
		}
	}
	
	function admin_init() 
	{
	    $beallitasok = get_option('es_options');
	    $apiKulcs = $beallitasok['google_api_key'];
	    
	    wp_register_style( 'anytimeDateTimePickerCss', WP_PLUGIN_URL.'/esemenyek/css/anytimec.css' );
        wp_enqueue_style( 'anytimeDateTimePickerCss' );
        wp_register_style( 'esemenyekCss', WP_PLUGIN_URL.'/esemenyek/css/esemenyek.css' );
        wp_enqueue_style( 'esemenyekCss' );
        wp_register_script( 'anytimeDateTimePickerJs', plugins_url('/js/anytimec.js', __FILE__) );
        wp_enqueue_script( 'anytimeDateTimePickerJs' );
        wp_register_script('googleAjaxApiJs', 'http://www.google.com/jsapi?key='.$apiKulcs);
        wp_enqueue_script( 'googleAjaxApiJs' );
        wp_register_script('googleMapApiJs', 'http://maps.google.com/maps/api/js?sensor=false');
        wp_enqueue_script( 'googleMapApiJs' );
        wp_register_script('googleMapJs', plugins_url('/js/google_map.js', __FILE__));
        wp_enqueue_script( 'googleMapJs' );
        wp_register_script('esemenyekAdminJs', plugins_url('/js/esemenyek.js', __FILE__));
        wp_enqueue_script( 'esemenyekAdminJs' );
         
		add_meta_box("esemeny-meta", "Esemény beállításai", array(&$this, "meta_options"), "mw_esemeny", "normal", "high");
	}
	
	function edit_columns($columns)
	{
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Esemény címe",
			"es_leiras" => "Leírás",
			"es_irszam" => "Irányítószám",
			"es_varos" => "Város",
			"es_cim" => "Cím",
			"es_google_terkep" => "Kell térkép?",
			"es_google_terkep_lng" => "Google térkép longitúdó",
			"es_google_terkep_lat" => "Google térkép latitúdó",
			"es_google_terkep_szelesseg" => "Térkép szélessége",
			"es_google_terkep_magassag" => "Térkép magassága",
			"es_google_terkep_nagyitas" => "Térkép nagyítása",
			"es_google_terkep_mutato_felirat" => "A helyszín nyilacskájára mutatva megjelenő felirat",
			"es_google_terkep_info_buborek_tartalom" => "A helyszín feletti buborékban megjelenő szöveg",
			"es_idopont_kezdo" => "Kezdés időpontja",
			"es_idopont_befejezo" => "Befejezés időpontja",
			"es_jelleg" => "Jelleg",
		);
		
		return $columns;
	}
	
	function custom_columns($column)
	{
		global $post;
		switch ($column)
		{
			case "es_leiras":
				the_excerpt();
				break;
			case "es_helyszin_iranyitoszam":
				$custom = get_post_custom();
				echo $custom["esemeny-helyszin-iranyitoszam"][0];
				break;
			case "es_helyszin_varos":
				$custom = get_post_custom();
				echo $custom["esemeny-helyszin-varos"][0];
				break;
			case "es_helyszin_cim":
				$custom = get_post_custom();
				echo $custom["esemeny-helyszin-cim"][0];
				break;
			case "es_google_terkep":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep"][0];
				break;
		    case "es_google_terkep_lng":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-lng"][0];
				break;
	        case "es_google_terkep_lat":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-lat"][0];
				break;
		    case "es_google_terkep_szelesseg":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-szelesseg"][0];
				break;
	        case "es_google_terkep_magassag":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-magassag"][0];
				break;
	        case "es_google_terkep_nagyitas":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-nagyitas"][0];
				break;
		    case "es_google_terkep_mutato_felirat":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-mutato-felirat"][0];
				break;
	        case "es_google_terkep_info_buborek_tartalom":
				$custom = get_post_custom();
				echo $custom["esemeny-google-terkep-info-buborek-tartalom"][0];
				break;
			case "es_idopont_kezdo":
				$custom = get_post_custom();
				echo $custom["esemeny-idopont-kezdo"][0];
				break;
			case "es_idopont_befejezo":
				$custom = get_post_custom();
				echo $custom["esemeny-idopont-befejezo"][0];
				break;
			case "es_jelleg":
				$jellegek = get_the_terms(0, "jelleg");
				$jellegek_html = array();
				foreach ($jellegek as $jelleg)
					array_push($jellegek_html, '<a href="' . get_term_link($jelleg->slug, "jelleg") . '">' . $jelleg->name . '</a>');
				
				echo implode($jellegek_html, ", ");
				break;
		}
	}
	
    function es_settings_page()
    { 
        //load our options array 
        $es_options = get_option('es_options'); 
        
        $google_api_key = $es_options['google_api_key'];
        $google_terkep_szelesseg = ($es_options['google_terkep_szelesseg'] AND is_numeric($es_options['google_terkep_szelesseg'])) ? $es_options['google_terkep_szelesseg'] : ALAP_TERKEP_SZELESSEG;
        $google_terkep_magassag = ($es_options['google_terkep_magassag'] AND is_numeric($es_options['google_terkep_magassag'])) ? $es_options['google_terkep_magassag'] : ALAP_TERKEP_MAGASSAG;
        $google_terkep_nagyitas = ($es_options['google_terkep_nagyitas'] AND is_numeric($es_options['google_terkep_nagyitas'])) ? $es_options['google_terkep_nagyitas'] : ALAP_TERKEP_NAGYITAS;
        ?> 
        
        <div class="wrap" id="esemeny-meta"> 
            <h2><?php _e('Események beállításai', 'es-plugin') ?></h2> 
        
            <form method="post" action="options.php"> 
                <?php settings_fields( 'es-settings-group' ); ?> 
                <fieldset>
                    <legend>Google térkép</legend>
                
                    <table class="form-table"> 
                        <tr valign="top"> 
                            <th scope="row"><label for="google_api_key"><?php _e('Google API kulcs', 'es-plugin') ?></th> 
                            <td><input id="google_api_key" name="es_options[google_api_key]" value="<?php echo $google_api_key; ?>" size="85" ></td> 
                        </tr>
                        <tr valign="top"> 
                            <th scope="row"><label for="google_terkep_szelesseg"><?php _e('Térkép alapértelmezett szélessége (képpontban)', 'es-plugin') ?></th> 
                            <td><input id="google_terkep_szelesseg" name="es_options[google_terkep_szelesseg]" value="<?php echo $google_terkep_szelesseg; ?>" size="3" /></td>
                        </tr>
                        <tr valign="top"> 
                            <th scope="row"><label for="google_terkep_magassag"><?php _e('Térkép alapértelmezett magassága (képpontban)', 'es-plugin') ?></th> 
                            <td><input id="google_terkep_magassag" name="es_options[google_terkep_magassag]" value="<?php echo $google_terkep_magassag; ?>" size="3" /></td> 
                        </tr>
                        <tr valign="top"> 
                            <th scope="row"><label for="google_terkep_nagyitas"><?php _e('Térkép alapértelmezett nagyítása', 'es-plugin') ?></th> 
                            <td><input id="google_terkep_nagyitas" name="es_options[google_terkep_nagyitas]" value="<?php echo $google_terkep_nagyitas; ?>" size="1" /></td> 
                        </tr>
                        <tr valign="top"> 
                            <th scope="row"><?php _e('Előnézet', 'es-plugin') ?></th> 
                            <td><div id="terkep" style="height:<?php echo $google_terkep_magassag; ?>px;width:<?php echo $google_terkep_szelesseg; ?>px;"></div></td> 
                        </tr>
                    </table>
                </fieldset>
                
                <p class="submit"> 
                    <input type="submit" class="button-primary" value="<?php _e('Beállítások mentése', 'es-plugin') ?>" /> 
                </p> 
            </form>
        </div> 
    <?php 
    }
	
	// Admin post meta contents
	function meta_options()
	{
		global $post;
		
		$es_options = get_option('es_options');
		$custom = get_post_custom($post->ID);
		
		$alap_google_terkep_szelesseg = ($es_options['google_terkep_szelesseg'] AND is_numeric($es_options['google_terkep_szelesseg'])) ? $es_options['google_terkep_szelesseg'] : ALAP_TERKEP_SZELESSEG;
        $alap_google_terkep_magassag = ($es_options['google_terkep_magassag'] AND is_numeric($es_options['google_terkep_magassag'])) ? $es_options['google_terkep_magassag'] : ALAP_TERKEP_MAGASSAG;
        $alap_google_terkep_nagyitas = ($es_options['google_terkep_nagyitas'] AND is_numeric($es_options['google_terkep_nagyitas'])) ? $es_options['google_terkep_nagyitas'] : ALAP_TERKEP_NAGYITAS;
        
		$iranyitoszam = $custom["esemeny-helyszin-iranyitoszam"][0];
		$varos = $custom["esemeny-helyszin-varos"][0];
		$cim = $custom["esemeny-helyszin-cim"][0];
		$kezdes = $custom["esemeny-idopont-kezdo"][0];
		$befejezes = $custom["esemeny-idopont-befejezo"][0];
		$terkep = $custom["esemeny-google-terkep"][0] ? 'checked="checked"' : '';
		$lng = $custom["esemeny-google-terkep-lng"][0];
		$lat = $custom["esemeny-google-terkep-lat"][0];
		$sz = $custom["esemeny-google-terkep-szelesseg"][0] ? $custom["esemeny-google-terkep-szelesseg"][0] : $alap_google_terkep_szelesseg;
		$m = $custom["esemeny-google-terkep-magassag"][0] ? $custom["esemeny-google-terkep-magassag"][0] : $alap_google_terkep_magassag;
        $nagyitas = $custom["esemeny-google-terkep-nagyitas"][0] ? $custom["esemeny-google-terkep-nagyitas"][0] : $alap_google_terkep_nagyitas;
        $infobuborek = $custom["esemeny-google-terkep-info-buborek-tartalom"][0];
        $mutato = $custom["esemeny-google-terkep-mutato-felirat"][0];
?>
    <table class="form-table">
        <tr valign="top">
        	<th scope="row"><label for="idopont-kezdo">Kezdő időpont:</label></th>
        	<td><input name="esemeny-idopont-kezdo" id="idopont-kezdo" size="16" value="<?php echo $kezdes; ?>" /></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="idopont-befejezo">Befejező időpont:</label></th>
        	<td><input name="esemeny-idopont-befejezo" id="idopont-befejezo" size="16" value="<?php echo $befejezes; ?>" /></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-helyszin-iranyitoszam">Irányítószám:</label></th>
        	<td><input name="esemeny-helyszin-iranyitoszam" id="esemeny-helyszin-iranyitoszam" size="3" value="<?php echo $iranyitoszam; ?>" /></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-helyszin-varos">Város:</label></th>
        	<td><input name="esemeny-helyszin-varos" id="esemeny-helyszin-varos" size="25" value="<?php echo $varos; ?>" /></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-helyszin-cim">Cím:</label></th>
        	<td><input name="esemeny-helyszin-cim" id="esemeny-helyszin-cim" size="25" value="<?php echo $cim; ?>" /></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep">Kell térkép az eseményhez?</label></th>
        	<td><input type="checkbox" name="esemeny-google-terkep" id="esemeny-google-terkep" <?php echo $terkep; ?> value="1" /></td>
        </tr>
    </table>


    <table class="form-table" id="terkep-adatok">
        <tr><td colspan="2"><button id="terkepBetolto" type="button">Térképadatok frissítése</button></td></tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-mutato-felirat">Mutató felirata:</label></th>
        	<td><input name="esemeny-google-terkep-mutato-felirat" id="esemeny-google-terkep-mutato-felirat" value="<?php echo $mutato; ?>" ></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-info-buborek-tartalom">Információs buborék tartalma:</label></th>
        	<td>
        	    <div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
            		<?php the_editor( ( isset($infobuborek ) ) ? $infobuborek : '', 'esemeny-google-terkep-info-buborek-tartalom' ); ?>	
            	</div>
        	</td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-lng">Szélességi koordináta:</label></th>
        	<td><input name="esemeny-google-terkep-lng" id="esemeny-google-terkep-lng" value="<?php echo $lng; ?>" size="8" ></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-lat">Hosszúsági koordináta:</label></th>
        	<td><input name="esemeny-google-terkep-lat" id="esemeny-google-terkep-lat" value="<?php echo $lat; ?>" size="8" ></td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-szelesseg">Térkép szélessége:</label></th>
        	<td><input name="esemeny-google-terkep-szelesseg" id="esemeny-google-terkep-szelesseg" value="<?php echo $sz; ?>" size="3" >px</td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-magassag">Térkép magassága:</label></th>
        	<td><input name="esemeny-google-terkep-magassag" id="esemeny-google-terkep-magassag" value="<?php echo $m; ?>" size="3" >px</td>
        </tr>
        <tr valign="top">
        	<th scope="row"><label for="esemeny-google-terkep-nagyitas">Térkép nagyítása:</label></th>
        	<td><input name="esemeny-google-terkep-nagyitas" id="esemeny-google-terkep-nagyitas" value="<?php echo $nagyitas; ?>" size="2" ></td>
        </tr>
    </table>


<?php
	}
	
	function sabloncimke_esemenyek($atts, $content = null)
	{
	    $kimenet = '';
	    
	    extract(shortcode_atts(array( 
            'rendezes' => 'DESC',
            'szures' => ''
        ), $atts));
        
        $opciok = array(
            'post_type'=>'mw_esemeny',
            'post_status'=>'publish',
            'order'=>$rendezes,
            'orderby'=>'meta_value',
            'meta_key'=>'esemeny-idopont-kezdo'
        );
        
        // Igazítások
        if(!in_array($opciok['rendezes'], array('DESC', 'ASC')))
        {
            $opciok['rendezes'] = 'DESC';
        }
        
        if($szures == 'jovobeli')
        {
            $opciok = array_merge($opciok, array('meta_compare'=>'>', 'meta_value'=>date("Y-m-d H:i:s")));
        }
        elseif($szures == 'multbeli')
        {
            $opciok = array_merge($opciok, array('meta_compare'=>'<', 'meta_value'=>date("Y-m-d H:i:s")));
        }
        
        // Esemény típusú tartalmak lekérdezése
        query_posts($opciok);


        if (have_posts())
        {
            $kimenet .= '<ul class="dbem_events_list">';
            
            while (have_posts())
            {
                the_post();
                
                $custom = get_post_custom($post->ID);
        		$iranyitoszam = $custom["esemeny-helyszin-iranyitoszam"][0];
        		$varos = $custom["esemeny-helyszin-varos"][0];
        		$cim = $custom["esemeny-helyszin-cim"][0];
        		$kezdes = $custom["esemeny-idopont-kezdo"][0];
        		if(preg_match('/(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})/',
        		    $kezdes))
        		{
        		    $kezdes_timestamp = datetime_to_timestamp($kezdes);
        		}
        		$befejezes = $custom["esemeny-idopont-befejezo"][0];
        		if(preg_match('/(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})/',
        		    $befejezes))
        		{
        		    $befejezes_timestamp = datetime_to_timestamp($befejezes);
        		}
                
                $kimenet .= '
                <li>
                    <h3 class="entry-title"><a href="'.get_permalink().'">'.get_the_title().'</a></h3>
                    <div class="entry-meta">'.
                        (!empty($varos) ? '
                        <span class="meta-prep meta-prep-entry-location">Helyszín:</span> 
                        <span class="entry-location">'.$varos.(!empty($cim) ? (', '.$cim) : '').'</span><br />' : 
                            (!empty($cim) ? '
                            <span class="meta-prep meta-prep-entry-location">Helyszín:</span> 
                            <span class="entry-location">'.$cim.'</span><br />' : '') 
                        ) .'
                        <span class="meta-prep meta-prep-entry-date">Időpont:</span> 
                        <span class="entry-date">'.date_i18n("Y. F j., l - H:i", $kezdes_timestamp).'</span>
                    </div>
                    <p style="text-align:justify;">'.get_the_excerpt().'</p>
                </li>';
            
            }
        
            $kimenet .= '</ul>';
        }
        else
        {
            $kimenet = 'Nincsenek megjeleníthető események.';
        }

        
        // Loop visszaállítása
        wp_reset_query();
        return $kimenet;
    }
	
	function sabloncimke_esemeny($atts, $content = null)
	{
	    $kimenet = '';
	    global $post;
	    
	    $custom = get_post_custom($post->ID);
		$es_options = get_option('es_options');
		$alap_google_terkep_szelesseg = ($es_options['google_terkep_szelesseg'] AND is_numeric($es_options['google_terkep_szelesseg'])) ? $es_options['google_terkep_szelesseg'] : ALAP_TERKEP_SZELESSEG;
        $alap_google_terkep_magassag = ($es_options['google_terkep_magassag'] AND is_numeric($es_options['google_terkep_magassag'])) ? $es_options['google_terkep_magassag'] : ALAP_TERKEP_MAGASSAG;
        $alap_google_terkep_nagyitas = ($es_options['google_terkep_nagyitas'] AND is_numeric($es_options['google_terkep_nagyitas'])) ? $es_options['google_terkep_nagyitas'] : ALAP_TERKEP_NAGYITAS;

        $varos = $custom["esemeny-helyszin-varos"][0];
		$cim = $custom["esemeny-helyszin-cim"][0];
		$kezdes = $custom["esemeny-idopont-kezdo"][0];
		$befejezes = $custom["esemeny-idopont-befejezo"][0];
        
	    $kell_terkep = $custom['esemeny-google-terkep'] ? $custom['esemeny-google-terkep'] : 0;
	    if($kell_terkep)
	    {
	        $sz = $custom['esemeny-google-terkep-szelesseg'][0] ? $custom['esemeny-google-terkep-szelesseg'][0] : $alap_google_terkep_szelesseg;
    	    $m = $custom['esemeny-google-terkep-magassag'][0] ? $custom['esemeny-google-terkep-magassag'][0] : $alap_google_terkep_magassag;
    	    $nagyitas = $custom['esemeny-google-terkep-nagyitas'][0] ? $custom['esemeny-google-terkep-nagyitas'][0] : $alap_google_terkep_nagyitas;
    	    $lng = $custom['esemeny-google-terkep-lng'][0];
    	    $lat = $custom['esemeny-google-terkep-lat'][0];
    	    $infobuborek = $custom["esemeny-google-terkep-info-buborek-tartalom"][0];
            $mutato = $custom["esemeny-google-terkep-mutato-felirat"][0];
	    }
	    
	    if(!empty($kezdes))
	    {
	        $kezdes = $custom["esemeny-idopont-kezdo"][0];
    		if(preg_match('/(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})/',
    		    $kezdes))
    		{
    		    $kezdes_timestamp = datetime_to_timestamp($kezdes);
    		}
    		
	        $kimenet .= '<h3>Időpont</h3>';
	        $kimenet .= '<p>'.( !empty($befejezes) ? '<strong>Keződik</strong>: ' : '') . date_i18n("Y. F j., l - H:i", $kezdes_timestamp);
	        
	        if(!empty($befejezes))
	        {
	            if(preg_match('/(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})/',
        		    $befejezes))
        		{
        		    $befejezes_timestamp = datetime_to_timestamp($befejezes);
        		}
        		
	            $kimenet .= '<br><strong>Vége</strong>: '. date_i18n("Y. F j., l - H:i", $befejezes_timestamp);
            }
            
            $kimenet .= '</p>';
	    }
	    
	    if(!empty($varos) OR !empty($cim))
	    {
	        $kimenet .= '<h3>Helyszín</h3>';
	        $kimenet .= '<p>'.( !empty($varos) ? $varos.(!empty($cim) ? ', ' : '') : '' ).( !empty($cim) ? $cim : '' ).'</p>';
	    }
	    
	    //$kimenet .= '<p class="jobbra-igazitott"><a class="vastag-link" href="/esemenyek#regebbi-esemenyek">Régebbi események</a></p>';
	    
	    if($kell_terkep AND !empty($lat) AND !empty($lng))
	    {	        
	        $kimenet .= '<h3>Térkép</h3><div id="terkep" style="height:'.$m.'px;width:'.$sz.'px;"></div>';
    	    $kimenet .= '
    	    <script>
    	        $(document).ready(function(){
    	            var myLatlng = new google.maps.LatLng('.$lat.','.$lng.');
                    var myOptions = {
                      zoom: '.$nagyitas.',
                      center: myLatlng,
                      mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    var map = new google.maps.Map(document.getElementById("terkep"), myOptions);
                    
                    var marker = new google.maps.Marker({
                        map: map,
                        '. (!empty($mutato) ? 'title: "'.$mutato.'",' : '') .'
                        position: myLatlng
                    });
                    '. (!empty($infobuborek) ? '
                    var infowindow = new google.maps.InfoWindow({
                        maxWidth: 200,
                        content: "'.str_replace(array("\r", "\r\n", "\n"), '', $infobuborek).'"
                    });
                    infowindow.open(map,marker);' : '' )
                    .'
                });
            </script>
    	    ';
	    }
	    
        
        return $kimenet;
    }
    
}


function datetime_to_timestamp($str) {

    list($date, $time) = explode(' ', $str);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);
    
    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
    
    return $timestamp;
}


add_action("widgets_init", 'es_register_widgets' );
// Widget regisztrálása
function es_register_widgets()
{ 
    register_widget( 'Esemenyek_Lista_Widget' ); 
}
//es_widget class 
class Esemenyek_Lista_Widget extends WP_Widget
{ 
    //process our new widget 
    function Esemenyek_Lista_Widget()
    {
        $widget_ops = array(
            'description' => 'Közelgő események listája.'
        );
        $this->WP_Widget('esemenyek_lista', 'Legutóbbi események', $widget_ops);
    }
    
    //build our widget settings form 
    function form($instance)
    { 
            $cim = esc_attr($instance['title']);
            $esemenyek_szama = esc_attr($instance['esemenyek_szama']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">
                Cím: <input class="widefat" type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo attribute_escape($cim); ?>" />
            </label></p>
            <p><label for="<?php echo $this->get_field_id('esemenyek_szama'); ?>">
                Megjelenő események száma: <input class="widefat" type="text" name="<?php echo $this->get_field_name('esemenyek_szama'); ?>" id="<?php echo $this->get_field_id('esemenyek_szama'); ?>" value="<?php echo attribute_escape($esemenyek_szama); ?>" />
            </label></p>
        <?php
    }
    
    //save our widget settings 
    function update($new_instance, $old_instance)
    { 
        $inst = $old_instance;
        $inst['title'] = strip_tags($new_instance['title']);
        $inst['esemenyek_szama'] = strip_tags($new_instance['esemenyek_szama']);
        return $inst;
    }
    
    //display our widget 
    function widget($args, $instance)
    {
        extract($args, EXTR_SKIP);
        
        echo $before_widget;
        
        $title = apply_filters('widget_title', $instance['title']);
        $esemenyek_szama = $instance['esemenyek_szama'];
                
        // Cím kiírása
        if(!empty($title))
        {
            echo $before_title .'<a href="/esemenyek">'.$title.'</a>'. $after_title;
        }
        
        $esemenyek = new WP_Query();
        $opciok = array(
            'post_type'=>'mw_esemeny',
            'post_status'=>'publish',
            'order'=>'ASC',
            'orderby'=>'meta_value',
            'meta_key'=>'esemeny-idopont-kezdo',
            'meta_compare'=>'>',
            'meta_value'=>date("Y-m-d H:i:s"),
            'showposts'=>$esemenyek_szama
        );
        $esemenyek->query($opciok);
        
        if($esemenyek->have_posts())
        {
            echo '<ul>';

            while($esemenyek->have_posts()) : $esemenyek->the_post();
        
                $custom = get_post_custom($post->ID);
                $kezdes = $custom["esemeny-idopont-kezdo"][0];
        		if(preg_match('/(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?([0-1]{1}\d{1}|[2]{1}[0-3]{1})(?::)?([0-5]{1}\d{1})(?::)?([0-5]{1}\d{1})/',
        		    $kezdes))
        		{
        		    $kezdes_timestamp = datetime_to_timestamp($kezdes);
        		}
        		
                ?>
                <li>
                    <span class="esemeny-cim summary">
                        <a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a>
                    </span>
                    <span class="esemeny-meta">
                        <strong>Kezdődik:</strong> <span class="dtstart"><?php echo date_i18n("Y. F j., l", $kezdes_timestamp); ?></span>
                    </span>
                </li>
                <?php
                            
            endwhile;
            
            echo '</ul>';
        }
        else
        {
            echo '<p>Jelenleg nem tervezünk semmit.</p>';
        }
        
        echo $after_widget; 
    }
}


// Initiate the plugin
register_activation_hook(__FILE__, array('Esemenyek', 'es_bekapcsolas'));
add_action("init", "EsemenyekInit");
function EsemenyekInit()
{
    global $esemenyek;
    $esemenyek = new Esemenyek();
}