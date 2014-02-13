<?php

class qa_html_theme_layer extends qa_html_theme_base {

	function form($form)
	{
	    /*
	     * Obtain the plugin name from the meta data of this specific plugin
	     *
	     * NB: This approach appears to not work due the the order in which the extended theme functions are executed.
	     * Solution was to override 'tags' within qa-user-import.php.
	     */

	    /*

	     */

		require_once QA_INCLUDE_DIR.'qa-app-admin.php';

    	$plugin_contents = file_get_contents(QA_PLUGIN_DIR.'qa-user-import/qa-plugin.php');

    	$metadata = qa_admin_addon_metadata($plugin_contents, array('name' => 'Plugin Name'));

        $plugin_name = $metadata['name'];

        if( isset($form['fields'][0]['html']) ) {
          $plugin_html = $form['fields'][0]['html'];
        } else {
          $plugin_html = "";
        }

		/*
		 * Test if we are on the admin pages and the plugin name matches the html with the form data.
		 *
		 * If so, add the enctype into the form so we can upload files.
		 *
		 */

		if (     ($this->template == 'admin' )
		      && ( $this->request == 'admin/plugins' )
			  && ( strpos( $plugin_html, $plugin_name ) !== FALSE )
		)
		{
			$form['tags'] = ' method="post" action="'.qa_admin_plugin_options_path(QA_PLUGIN_DIR . "/qa-user-import/").'" '.$form['tags']; // Admin panel form tags
		}
		qa_html_theme_base::form($form);
	}

	function head_script()
	{
		qa_html_theme_base::head_script();
		$this->output('<script type="text/javascript">'.
			'var bck_t = ""; function doCheck() { if(bck_t.length==0 || confirm(\'You are going to \'+bck_t+\'. \n\nProceed?\', \'Confirmation\')) return true; return false; }'.
		'</script>');
	}
}//class