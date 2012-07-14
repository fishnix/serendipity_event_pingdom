<?php 

/*
    Pingdom Statistics Plugin for Serendipity
    E. Camden Fisher <fishnix@gmail.com>  
*/

if (IN_serendipity != true) {
    die ("Don't hack!"); 
}
    
$time_start = microtime(true);

// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';

if (file_exists($probelang)) {
    include $probelang;
}

include_once dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_event_pingdom extends serendipity_event
{

    function example() 
    {
      echo PLUGIN_PINGDOM_INSTALL;
    }

    function introspect(&$propbag)
    {
        global $serendipity;

        $propbag->add('name',         PLUGIN_PINGDOM_NAME);
        $propbag->add('description',  PLUGIN_PINGDOM_DESC);
        $propbag->add('stackable',    false);
        $propbag->add('groups',       array('BACKEND_FEATURES'));
        $propbag->add('author',       'E Camden Fisher <fishnix@gmail.com>');
        $propbag->add('version',      '0.0.1');
        $propbag->add('requirements', array(
            'serendipity' => '1.5.0',
            'smarty'      => '2.6.7',
            'php'         => '5.2.0'
        ));
            
      $propbag->add('event_hooks',   array(
        'backend_frontpage_display' => true
        ));

      $this->markup_elements = array();

        $conf_array = array();

        foreach($this->markup_elements as $element) {
            $conf_array[] = $element['name'];
        }

        $conf_array[] = 'pd_enable';
        $conf_array[] = 'pd_username';
				$conf_array[] = 'pd_password';
        $conf_array[] = 'pd_appkey';
				

        $propbag->add('configuration', $conf_array);
    }

    function generate_content(&$title) {
      $title = $this->title;
    }

    function introspect_config_item($name, &$propbag) {
      switch($name) {
        case 'pd_enable':
          $propbag->add('name',           PLUGIN_PINGDOM_PROP_ENABLE);
          $propbag->add('description',    PLUGIN_PINGDOM_PROP_ENABLE_DESC);
          $propbag->add('default',        'true');
          $propbag->add('type',           'boolean');
        break;
        case 'pd_username':
          $propbag->add('name',           PLUGIN_PINGDOM_PROP_USERNAME);
          $propbag->add('description',    PLUGIN_PINGDOM_PROP_USERNAME_DESC);
          $propbag->add('default',        '');
          $propbag->add('type',           'string');
        break;
        case 'pd_password':
          $propbag->add('name',           PLUGIN_PINGDOM_PROP_PASSWORD);
          $propbag->add('description',    PLUGIN_PINGDOM_PROP_PASSWORD_DESC);
          $propbag->add('default',        '');
          $propbag->add('type',           'string');
        break;
        case 'pd_appkey':
          $propbag->add('name',           PLUGIN_PINGDOM_PROP_APPKEY);
          $propbag->add('description',    PLUGIN_PINGDOM_PROP_APPKEY_DESC);
          $propbag->add('default',        '');
          $propbag->add('type',           'string');
        break;
        default:
          return false;
        break;
        
      }
      
      return true;
    }
    
    /*
     * install plugin
     */
    function install() {
    }

    /*
     * uninstall plugin
     */
    function uninstall() {
    }
    
    function event_hook($event, &$bag, &$eventData) {
        global $serendipity;
        
        $hooks = &$bag->get('event_hooks');
        
        if (isset($hooks[$event])) {
          switch($event) {
              case 'backend_frontpage_display':
                // Init cURL
                $curl = curl_init();
                // Set target URL
                curl_setopt($curl, CURLOPT_URL, "https://api.pingdom.com/api/2.0/reports.shared");
                // Set the desired HTTP method (GET is default, see the documentation for each request)
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
                // Set user (email) and password
                $auth = $this->get_config('pd_username') . ':' . $this->get_config('pd_password');
                curl_setopt($curl, CURLOPT_USERPWD, $auth);
                // Add a http header containing the application key (see the Authentication section of this document)
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("App-Key: " . $this->get_config('pd_appkey')));
                // Ask cURL to return the result as a string
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                // Execute the request and decode the json result into an associative array
                $response = json_decode(curl_exec($curl),true);

                // Check for errors returned by the API
                if (isset($response['error'])) {
                  $err_msg = 'Pingdom: ' . $response['error']['statusdesc'] . ': ' . $response['error']['errormessage'];
                  $this->outputMSG('error', $err_msg);	
                }

                ?><div id="pingdom"><table class="serendipityAdminContent"><?php
                
                $bannerslist = $response['shared']['banners'];
                // Print the names and statuses of all checks in the list
                foreach ($bannerslist as $banner) {
                    ?><tr><td><img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['type']; ?> report for <?php echo $banner['name']; ?>"
                      title="<?php echo $banner['type']; ?> report for <?php echo $banner['name']; ?>" width="300" height="165" float="left" /></td></tr><?php
                }
                ?></table></div><?php
                
            break;

            default:
              return false;
            } 
        } else {
        return false;
      }
    }

    
    function outputMSG($status, $msg) {
        switch($status) {
            case 'notice':
                echo '<div class="serendipityAdminMsgNotice"><img style="width: 22px; height: 22px; border: 0px; padding-right: 4px; vertical-align: middle" src="' . serendipity_getTemplateFile('admin/img/admin_msg_note.png') . '" alt="" />' . $msg . '</div>' . "\n";
                break;

            case 'error':
                echo '<div class="serendipityAdminMsgError"><img style="width: 22px; height: 22px; border: 0px; padding-right: 4px; vertical-align: middle" src="' . serendipity_getTemplateFile('admin/img/admin_msg_error.png') . '" alt="" />' . $msg . '</div>' . "\n";
                break;

            default:
            case 'success':
                echo '<div class="serendipityAdminMsgSuccess"><img style="height: 22px; width: 22px; border: 0px; padding-right: 4px; vertical-align: middle" src="' . serendipity_getTemplateFile('admin/img/admin_msg_success.png') . '" alt="" />' . $msg . '</div>' . "\n";
                break;
        }
    }
}

?>