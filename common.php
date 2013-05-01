<?php
    /*
    *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
    *  as-is and without warranty under the MIT License. See
    *  [root]/license.txt for more. This information must remain intact.
    */
     
    Common::startSession();
               
    //////////////////////////////////////////////////////////////////
    // Common Class
    //////////////////////////////////////////////////////////////////
    
    class Common {

        //////////////////////////////////////////////////////////////////
        // PROPERTIES
        //////////////////////////////////////////////////////////////////
        
        public static $debugMessageStack = array();
        
        //////////////////////////////////////////////////////////////////
        // METHODS
        //////////////////////////////////////////////////////////////////

        // -----------------------------||----------------------------- //

        //////////////////////////////////////////////////////////////////
        // Construct
        //////////////////////////////////////////////////////////////////

        public static function construct(){
            $path = '';
            foreach (array("components","plugins") as $folder) {
                if(strpos($_SERVER['SCRIPT_FILENAME'], $folder)) {
                    foreach(explode("/", substr($_SERVER['SCRIPT_FILENAME'],strpos($_SERVER['SCRIPT_FILENAME'], $folder) + strlen($folder)+1)) as $part) {
                        if(!isset($path)){
                            $path = '../';
                        }else{
                            $path .= '../';
                        }
                    }
                }
            }
            
            if(file_exists($path.'config.php')){ require_once($path.'config.php'); }
        
            if(!defined('BASE_PATH')) {
                define('BASE_PATH', rtrim(str_replace("index.php", "", $_SERVER['SCRIPT_FILENAME']),"/"));
            }
            
            if(!defined('COMPONENTS')) {
                define('COMPONENTS', BASE_PATH . '/components');
            }
            
            if(!defined('PLUGINS')) {
                define('PLUGINS', BASE_PATH . '/plugins');
            }
            
            if(!defined('DATA')) {
                define('DATA', BASE_PATH . '/data');
            }
            
            // Ensure theme vars are present (upgrade with legacy config.php)
            if(!defined('THEMES')){
                define("THEMES", BASE_PATH . "/themes");
            }
            
            if(!defined('THEME')){
                define("THEME", "default");
            }
        }
        
        //////////////////////////////////////////////////////////////////
        // SESSIONS
        //////////////////////////////////////////////////////////////////
        
        public static function startSession() {
            Common::construct();
            if(isset($cookie_lifetime) && $cookie_lifetime != "") {
                ini_set("session.cookie_lifetime", $cookie_lifetime);
            }
            
            //Set a Session Name
            session_name(md5(BASE_PATH));

            session_start();
        }
            
        //////////////////////////////////////////////////////////////////
        // Log debug message
        // Messages will be displayed in the console when the response is 
        // made with the formatJSEND function.
        //////////////////////////////////////////////////////////////////
        
        public static function debug($message) {
            Common::$debugMessageStack[] = $message;
        }
        
        //////////////////////////////////////////////////////////////////
        // Localization
        //////////////////////////////////////////////////////////////////
                
        public static function i18n($key) {
            echo Common::get_i18n($key);
        }
        
        public static function get_i18n($key) {
            global $lang;
            $key = ucwords(strtolower($key)); //Test, test TeSt and tESt are exacly the same
            return isset($lang[$key]) ? $lang[$key] : $key;
        }
        
        //////////////////////////////////////////////////////////////////
        // Check Session / Key
        //////////////////////////////////////////////////////////////////

        public static function checkSession(){
            // Set any API keys
            $api_keys = array();
            // Check API Key or Session Authentication
            $key = "";
            if(isset($_GET['key'])){ $key = $_GET['key']; }
            if(!isset($_SESSION['user']) && !in_array($key,$api_keys)){
                exit('{"status":"error","message":"Authentication Error"}');
            }
        }

        //////////////////////////////////////////////////////////////////
        // Get JSON
        //////////////////////////////////////////////////////////////////

        public static function getJSON($file,$namespace=""){
            $path = BASE_PATH . "/data/";
            if($namespace != ""){
                $path = $path . $namespace . "/";
                $path = preg_replace('#/+#','/',$path);
            }
            
            $json = file_get_contents($path . $file);
            $json = str_replace("|*/?>","",str_replace("<?php/*|","",$json));
            $json = json_decode($json,true);
            return $json;
        }

        //////////////////////////////////////////////////////////////////
        // Save JSON
        //////////////////////////////////////////////////////////////////

        public static function saveJSON($file,$data,$namespace=""){
            $path = BASE_PATH . "/data/";
            if($namespace != ""){
                $path = $path . $namespace . "/";
                $path = preg_replace('#/+#','/',$path);
                if(!is_dir($path)) mkdir($path);
            }
            
            $data = "<?php/*|" . json_encode($data) . "|*/?>";
            $write = fopen($path . $file, 'w') or die("can't open file");
            fwrite($write, $data);
            fclose($write);
        }

        //////////////////////////////////////////////////////////////////
        // Format JSEND Response
        //////////////////////////////////////////////////////////////////

        public static function formatJSEND($status,$data=false){

            /// Debug /////////////////////////////////////////////////
            $debug = "";
            if(count(Common::$debugMessageStack) > 0) {
                $debug .= ',"debug":';
                $debug .= json_encode(Common::$debugMessageStack);
            }

            // Success ///////////////////////////////////////////////
            if($status=="success"){
                if($data){
                    $jsend = '{"status":"success","data":'.json_encode($data).$debug.'}';
                }else{
                    $jsend = '{"status":"success","data":null'.$debug.'}';
                }

            // Error /////////////////////////////////////////////////
            }else{
                $jsend = '{"status":"error","message":"'.$data.'"'.$debug.'}';
            }

            // Return ////////////////////////////////////////////////
            return $jsend;

        }
        
        //////////////////////////////////////////////////////////////////
        // Check Function Availability
        //////////////////////////////////////////////////////////////////

        public static function checkAccess() {
            return !file_exists(BASE_PATH . "/data/" . $_SESSION['user'] . '_acl.php');
        }
        
        //////////////////////////////////////////////////////////////////
        // Check Function Availability
        //////////////////////////////////////////////////////////////////

        public static function isAvailable($func) {
            if (ini_get('safe_mode')) return false;
            $disabled = ini_get('disable_functions');
            if ($disabled) {
                $disabled = explode(',', $disabled);
                $disabled = array_map('trim', $disabled);
                return !in_array($func, $disabled);
            }
            return true;
        }
        
        //////////////////////////////////////////////////////////////////
        // Check If Path is absolute
        //////////////////////////////////////////////////////////////////
            
        public static function isAbsPath( $path ) {
            return ($path[0] === '/')?true:false;
        }
            
    }
    
    //////////////////////////////////////////////////////////////////
    // Wrapper for old method names
    //////////////////////////////////////////////////////////////////
    
    //////////////////////////////////////////////////////////////////
    // Log debug message
    // Messages will be displayed in the console when the response is 
    // made with the formatJSEND function.
    //////////////////////////////////////////////////////////////////
    
    function debug($message) {
        Common::debug($message);
    }
    
    //////////////////////////////////////////////////////////////////
    // Localization
    //////////////////////////////////////////////////////////////////
    
    if (isset($_SESSION['lang'])) {
        include BASE_PATH."/languages/{$_SESSION['lang']}.php";
    } else {  
        include BASE_PATH."/languages/en.php";
    }
    
    function i18n($key) {
        echo Common::i18n($key);
    }
    
    function get_i18n($key) {
        return Common::get_i18n($key);
    }
    
    //////////////////////////////////////////////////////////////////
    // Check Session / Key
    //////////////////////////////////////////////////////////////////

    function checkSession(){
        Common::checkSession();
    }

    //////////////////////////////////////////////////////////////////
    // Get JSON
    //////////////////////////////////////////////////////////////////

    function getJSON($file,$namespace=""){
        return Common::getJSON($file,$namespace);
    }

    //////////////////////////////////////////////////////////////////
    // Save JSON
    //////////////////////////////////////////////////////////////////

    function saveJSON($file,$data,$namespace=""){
        Common::saveJSON($file,$data,$namespace);
    }

    //////////////////////////////////////////////////////////////////
    // Format JSEND Response
    //////////////////////////////////////////////////////////////////

    function formatJSEND($status,$data=false){
        return Common::formatJSEND($status,$data);
    }
    
    //////////////////////////////////////////////////////////////////
    // Check Function Availability
    //////////////////////////////////////////////////////////////////

    function checkAccess() {
        return Common::checkAccess();
    }
    
    //////////////////////////////////////////////////////////////////
    // Check Function Availability
    //////////////////////////////////////////////////////////////////

    function isAvailable($func) {
        return Common::isAvailable($func);
    }
?>
