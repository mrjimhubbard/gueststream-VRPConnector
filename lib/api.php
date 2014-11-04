<?php
/**
 * Class vrpapi
 */
class vrpapi
{
    var $apiKey;
    var $apiURL = "http://vrp.dev/api/v1/";
    var $allowCache = true;
    var $theme = "";
    var $themename = "";
    var $otheractions = array();

    /**
     * Class Construct
     */
    function __construct() {
        $this->apiKey = get_option('vrpAPI');
        if ($this->apiKey == '') {
            add_action('admin_notices', array($this, 'notice'));
        }

        $this->setTheme();
        $this->actions();
        $this->themeActions();
    }

    /**
     * Class Destruct w/basic debugging.
     */
    function __destruct() {
        if (!isset($_GET['showdebug'])) {
            return false;
        }
        if (!isset($_GET['action'])) {
            return false;
        }
        echo "<div style='position:absolute;left:0;width:100%;background:white;color:black;'>";
        echo "API Time Spent: " . $this->time . "<br/>";
        echo "GET VARIABLES:<br><pre>";
        print_r($_GET);
        echo "</pre>";
        echo "Debug VARIABLES:<br><pre>";
        print_r($this->debug);
        echo "</pre>";
        echo "Post Type: " . $wp->query_vars["post_type"];
        echo "</div>";
    }

    /**
     * init WordPress Actions, Filters & shortcodes
     */
    function actions() {
        // Actions
        add_action("init", array($this, "ajax"));
        add_action("init", array($this, "sitemap"));
        add_action('init', array($this, 'featuredunit'));
        add_action("init", array($this, "otheractions"));
        add_action('init', array($this, 'do_rewrite11'));
        add_action('init', array($this, 'villafilter'));

        add_action('cacheClear', array($this, "clearCache"), 1, 3);
        add_action('admin_menu', array($this, 'setupPage'));
        add_action('parse_request', array($this, 'router'));

        // Filters
        add_filter('robots_txt', array($this, 'robots_mod'), 10, 2);
        add_filter('query_vars', array($this, 'wp_insertMyRewriteQueryVars'));
        remove_filter('template_redirect', 'redirect_canonical');

        if (isset($_GET['action'])) {
            remove_filter('the_content', 'wptexturize');
            remove_filter('the_content', 'wpautop');
        }

        // Shortcodes
        add_shortcode("vrpComplexes", array($this, "vrpComplexes"));
        add_shortcode("vrpUnits", array($this, "vrpUnits"));
        add_shortcode("vrpAdvancedSearch", array($this, "vrpAdvancedSearch"));
        add_shortcode("vrpSearch", array($this, "vrpSearch"));
        add_shortcode("vrpComplexSearch", array($this, "vrpComplexSearch"));
        add_shortcode("vrpAreaList", array($this, "vrpAreaList"));
        add_shortcode("vrpSpecials", array($this, "vrpSpecials"));
        add_shortcode("vrpLinks", array($this, "vrpLinks"));
        add_shortcode("vrpCompare", array($this, "vrpCompare"));
    }

    function villafilter() {
        if (!isset($_GET['action'])) {
            return;
        }

        if ($_GET['action'] == 'complexsearch') {
            if ($_GET['search']['type'] == 'Villa') {
                $_GET['action'] = 'search';
            }
        }
    }

    function usecustom() {
        include(get_template_directory() . "/page.php");
        exit();
    }

    function themeActions() {
        $theme = new $this->themename;
        if (method_exists($theme, "actions")) {
            $theme->actions();
        }
    }

    function setTheme() {
        $themeFolder = ABSPATH . "/wp-content/plugins/VRPAPI/themes/";
        $theme = get_option('vrptheme');
        if (!$theme) {
            $theme = "mountainsunset";
            $this->theme = $themeFolder . "mountainsunset";
        } else {
            $this->theme = $themeFolder . $theme;
        }
        $this->themename = $theme;
        include $this->theme . "/functions.php";
    }

    function otheractions() {
        if (isset($_GET['otherslug']) && $_GET['otherslug'] != '') {
            $theme = $this->themename;
            $theme = new $theme;
            $func = $theme->otheractions;

            $func2 = $func[$_GET['otherslug']];

            call_user_method($func2, $theme);
        }
    }

    /**
     * Uses built-in rewrite rules to get pretty URL. (/vrp/)
     */
    function do_rewrite11() {
        add_rewrite_rule('vrp/([^/]+)/?([^/]+)/?$', '?action=$1&slug=$2', 'top');
    }

    /**
     * Sets up action and slug as query variable.
     *
     * @param $vars [] $vars Query String Variables.
     *
     * @return $vars[]
     */
    function wp_insertMyRewriteQueryVars($vars) {
        array_push($vars, 'action', 'slug', 'other');
        return $vars;
    }

    /**
     * Checks to see if dws_slug is active, if so, sets up a page.
     *
     * @return bool
     */
    function router() {

        if (!isset($_GET['action'])) {
            return false;
        }
        if ($_GET['action'] == 'xml') {
            $this->xmlexport();
        }

        if ($_GET['action'] == 'flipkey') {
            $this->getflipkey();
        }

        add_filter('the_posts', array($this, "filterPosts"));
    }

    /**
     * @param $posts
     * @return array
     */
    function filterPosts($posts) {
        if (!isset($_GET['action'])) {
            return false;
        }
        $pagetitle = "";
        $action = $_GET['action'];

        switch ($action) {
            case "unit": // If Unit Page.
                $this->time = microtime(true);
                $slug = $_GET['slug'];
                $data2 = $this->call("getunit/" . $slug);
                $data = json_decode($data2);

                if (!isset($data->id)) {
                    global $wp_query;
                    $wp_query->is_404 = true;
                }
                $_GET['API'] = "YES";

                $this->time = round((microtime(true) - $this->time), 4);
                $pagetitle = $data->Name;

                if (isset($data->Error)) {
                    $content = $this->loadTheme("error", $data);
                } else {
                    $content = $this->loadTheme("unit", $data);
                }
                break;
            case "complex": // If Complex Page.
                $this->time = microtime(true);
                $slug = $_GET['slug'];
                $data2 = $this->call("getcomplex/" . $slug);
                $data = json_decode($data2);

                $_GET['API'] = "YES";

                $this->time = round((microtime(true) - $this->time), 4);
                $pagetitle = $data->name;

                if (isset($data->Error)) {
                    $content = $this->loadTheme("error", $data);
                } else {
                    $content = $this->loadTheme("complex", $data);
                }
                break;
            case "special": // If Special Page.

                $slug = $_GET['slug'];

                $data2 = $this->call("getspecial/" . $slug);
                $data = json_decode($data2);
                if (!isset($data->Error)) {
                    $this->cache($action, $slug, $data2);

                    $_GET['API'] = "YES";
                }

                $pagetitle = $data->title;
                $content = $this->loadTheme("specials", $data);

                break;
            case "search": // If Search Page.

                $this->time = microtime(true);
                $data = $this->search();
                //print_r($data);
                $this->time = round((microtime(true) - $this->time), 4);
                $time1 = microtime(true);
                $data = json_decode($data);
                //print_r($data);
                $time2 = round((microtime(true) - $time1), 4);
                if (isset($_GET['showdebug'])) {
                    /* echo "<pre>";
                      print_r($data->results);
                      echo "</pre>"; */
                    echo " <!-- Vacation Rental Platform : $time2 -->. ";
                }

                if (isset($data->type)) {
                    $content = $this->loadTheme($data->type, $data);
                } else {
                    $content = $this->loadTheme("results", $data);

                }

                $pagetitle = "Search Results";
                break;

            case "complexsearch": // If Search Page.
                $this->time = microtime(true);
                $data = $this->complexsearch();

                $this->time = round((microtime(true) - $this->time), 4);
                $data = json_decode($data);
                if (isset($data->type)) {
                    $content = $this->loadTheme($data->type, $data);
                } else {
                    $content = $this->loadTheme("complexresults", $data);
                }
                $pagetitle = "Search Results";
                break;

            case "book":

                if ($_GET['slug'] == 'dobooking') {
                    if (isset($_SESSION['package'])) {
                        $_POST['booking']['packages'] = $_SESSION['package'];
                    }
                }

                if (isset($_POST['email'])) {
                    $userinfo = $this->doLogin($_POST['email'], $_POST['password']);
                    $_SESSION['userinfo'] = $userinfo;
                    if (!isset($userinfo->Error)) {
                        $_GET['slug'] = "step3";
                    }
                }
                if (isset($_POST['booking'])) {
                    $_SESSION['userinfo'] = $_POST['booking'];
                }

                $data = json_decode($_SESSION['bookingresults']);
                if ($data->ID != $_GET['obj']['PropID']) {
                    $data = json_decode($this->checkavailability(false, true));
                    $data->new = true;
                }

                $data->thetime = time() - $data->TimeStamp;
                if ($data->thetime < 500 && $_GET['slug'] != 'confirm') {
                    $data = json_decode($this->checkavailability(false, true));
                    $data->new = true;
                }

                $data->PropID = $_GET['obj']['PropID'];
                //if ($_GET['slug']=='step2'){
                $data->booksettings = $this->bookSettings($data->PropID);
                if ($_GET['slug'] == 'step1') {
                    unset($_SESSION['package']);
                }
                $data->package = new stdClass();
                $data->package->packagecost = "0.00";
                $data->package->items = array();
                if (isset($_SESSION['package'])) {
                    $data->package = $_SESSION['package'];
                }
                //$data->TotalCost=$data->TotalCost + $data->package->packagecost;
                if ($_GET['slug'] == 'step1a') {
                    if (isset($data->booksettings->HasPackages)) {
                        $a = date("Y-m-d", strtotime($data->Arrival));
                        $d = date("Y-m-d", strtotime($data->Departure));
                        $data->packages = json_decode($this->call("getpackages/$a/$d/"));
                    } else {
                        $_GET['slug'] = 'step2';
                    }
                }

                if ($_GET['slug'] == 'step3') {
                    $data->form = json_decode($this->call("bookingform/"));
                }
                //}

                if ($_GET['slug'] == 'confirm') {
                    $data->thebooking = json_decode($_SESSION['bresults']);
                    $pagetitle = "Reservations";
                    $content = $this->loadTheme("confirm", $data);
                } else {

                    $pagetitle = "Reservations";
                    $content = $this->loadTheme("booking", $data);
                }
                break;

            case "xml":

                $content = "";
                $pagetitle = "";
                break;
        }

        return array(new DummyResult(0, $pagetitle, $content));
    }

    /**
     * Get available search options.
     *
     * Example: minbeds, maxbeds, minbaths, maxbaths, minsleeps, maxsleeps, types (hotel, villa), cities, areas, views, attributes, locations
     *
     * @return mixed
     */
    function searchoptions() {
        return json_decode($this->call("searchoptions"));
    }

    function featuredunit() {
        if (isset($_GET['featuredunit'])) {
            echo $this->call("featuredunit");
            die();
        }
    }

    function allSpecials() {
        return json_decode($this->call("allspecials"));
    }


    function searchjax() {
        if (isset($_GET['search']['arrival'])) {
            $_SESSION['arrival'] = $_GET['search']['arrival'];
        }

        if (isset($_GET['search']['departure'])) {
            $_SESSION['depart'] = $_GET['search']['departure'];
        }

        ob_start();
        $results = json_decode($this->search());

        $units = $results->results;

        include TEMPLATEPATH . "/vrp/unitsresults.php";
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;
    }

    function search() {
        $obj = new stdClass();

        foreach ($_GET['search'] as $k => $v) {
            $obj->$k = $v;
        }

        if (isset($_GET['page'])) {
            $obj->page = (int)$_GET['page'];
        } else {
            $obj->page = 1;
        }

        if (isset($_GET['show'])) {
            $obj->limit = (int)$_GET['show'];
        } else {
            $obj->limit = 10;
        }

        if ($obj->arrival == 'Not Sure') {
            $obj->arrival = '';
            $obj->depart = '';
        } else {
            //$obj->arrival = date("m/d/Y", strtotime($obj->arrival));
        }

        $search['search'] = json_encode($obj);

        return $this->call('search', $search);
    }

    function complexsearch() {
        $url = $this->apiURL . $this->apiKey . "/complexsearch3/";

        $obj = new stdClass();
        foreach ($_GET['search'] as $k => $v) {
            $obj->$k = $v;
        }
        if (isset($_GET['page'])) {
            $obj->page = (int)$_GET['page'];
        } else {
            $obj->page = 1;
        }
        if (isset($_GET['show'])) {
            $obj->limit = (int)$_GET['show'];
        } else {
            $obj->limit = 10;
        }
        if ($obj->arrival == 'Not Sure') {
            $obj->arrival = '';
            $obj->depart = '';
        }

        $search['search'] = json_encode($obj);
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $search);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //execute post
        $results = curl_exec($ch);
        //echo $results;
        if (!$ch) {
            echo 'Curl error: ' . curl_error($ch);
        }
        //close connection
        curl_close($ch);

        return $results;
    }

    function savecompare() {
        $obj = new stdClass();
        $obj->compare = $_SESSION['compare'];
        $obj->arrival = $_SESSION['arrival'];
        $obj->depart = $_SESSION['depart'];
        $search['search'] = json_encode($obj);
        $results = $this->call('savecompare',$search);
        return $results;
    }

    function compare() {

        //print_r($_GET['c']);

        if (isset($_GET['shared'])) {
            $_SESSION['cp'] = 1;
            $id = (int)$_GET['shared'];
            $source = "";
            if (isset($_GET['source'])) {
                $source = $_GET['source'];
            }
            $data = json_decode($this->call("getshared/" . $id . "/" . $source));
            $_SESSION['compare'] = $data->compare;
            $_SESSION['arrival'] = $data->arrival;
            $_SESSION['depart'] = $data->depart;
        }

        $obj = new stdClass();

        if (isset($_GET['c']['compare'])) {
            $compare = $_GET['c']['compare'];
            $_SESSION['compare'] = $compare;
            if (!is_array($compare)) {
                return;
            }
        } else {
            $compare = $_SESSION['compare'];
            if (!is_array($compare)) {
                return;
            }
        }

        if (isset($_GET['c']['arrival'])) {
            $obj->arrival = $_GET['c']['arrival'];
            $obj->departure = $_GET['c']['depart'];
            $_SESSION['arrival'] = $obj->arrival;
            $_SESSION['depart'] = $obj->departure;
        } else {
            if (isset($_SESSION['arrival'])) {
                $obj->arrival = $_SESSION['arrival'];
                $obj->departure = $_SESSION['depart'];
            }
        }
        $obj->items = $compare;
        sort($obj->items);

        $url = $this->apiURL . $this->apiKey . "/compare/";

        $search['search'] = json_encode($obj);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $search);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $results = curl_exec($ch);

        $results = json_decode($results);
        $contents = $this->loadTheme('vrpCompare',$results);

        return $contents;
    }

    function loadcompare() {

        $obj = new stdClass();

        if (isset($_GET['c']['compare'])) {

            $compare = $_GET['c']['compare'];

            if (!is_array($compare)) {
                return;
            }
        } else {

            $compare = $_SESSION['compare'];
            if (!is_array($compare)) {
                return;
            }
        }


        if (isset($_GET['c']['arrival'])) {
            $obj->arrival = $_GET['c']['arrival'];
            $obj->departure = $_GET['c']['depart'];
            $_SESSION['arrival'] = $obj->arrival;
            $_SESSION['depart'] = $obj->departure;
        } else {
            if (isset($_SESSION['arrival'])) {
                $obj->arrival = $_SESSION['arrival'];
                $obj->departure = $_SESSION['depart'];
            }
        }
        $obj->arrival = date("Y-m-d", strtotime($obj->arrival));
        $obj->departure = date("Y-m-d", strtotime($obj->departure));
        foreach ($compare as $v):
            $arr[] = $v;
        endforeach;
        $obj->items = $arr;


        $url = $this->apiURL . $this->apiKey . "/compare/";
        $search['search'] = json_encode($obj);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $search);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $results = curl_exec($ch);

        $results = json_decode($results);

        return $results;
    }

    /**
     * Loads the VRP Theme.
     *
     * @param string $section
     * @param        $data [] $data
     *
     * @return string
     */
    function loadTheme($section, $data) {
        $wptheme = get_stylesheet_directory() . "/vrp/$section.php";

        if (file_exists($wptheme)) {
            $load = $wptheme;
        } else {
            $load = $this->theme . "/" . $section . ".php";
        }

        if (isset($_GET['printme'])) {
            include $this->theme . "/print.php";
            die();
        }

        $this->debug['data'] = $data;
        $this->debug['theme_file'] = $load;

        ob_start();
        include $load;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function ajax() {
        if (!isset($_GET['vrpjax'])) {
            return false;
        }
        $act = $_GET['act'];
        $par = $_GET['par'];
        if (method_exists($this, $act)) {
            $this->$act($par);
        }
        die();
    }

    function checkavailability($par = false, $ret = false) {
        set_time_limit(50);

        $url = $this->apiURL . $this->apiKey . "/checkavail/";
        $fields_string = "obj=" . json_encode($_GET['obj']);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        $results = curl_exec($ch);
        if (!$ch) {

            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);
        $this->debug['results'] = $results;
        if ($ret == true) {
            $_SESSION['bookingresults'] = $results;
            return $results;
        }
        if ($par != false) {

            $_SESSION['bookingresults'] = $results;
            echo $results;
            return false;
        }


        $res = json_decode($results);

        if (isset($res->Error)) {
            echo $res->Error;
        } else {
            $_SESSION['bookingresults'] = $results;
            echo "1";
        }
    }

    function processbooking($par = false, $ret = false) {
        // $fields=$_GET['obj'];
        $url = $this->apiURL . $this->apiKey . "/processbooking/";
        //foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        //rtrim($fields_string,'&');
        $fields_string = "obj=" . json_encode($_POST['booking']);
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        //execute post
        $results = curl_exec($ch);
        if (!$ch) {

            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);
        $res = json_decode($results);
        if (isset($res->Results)) {
            $_SESSION['bresults'] = json_encode($res->Results);
        }
        echo $results;
    }

    function addtopackage() {
        $TotalCost = $_GET['TotalCost'];
        if (!isset($_GET['package'])) {
            unset($_SESSION['package']);
            $obj = new stdClass();
            $obj->packagecost = "$0.00";

            $obj->TotalCost = "$" . number_format($TotalCost, 2);
            echo json_encode($obj);
            return false;
        }

        $currentpackage = new stdClass();
        $currentpackage->items = array();
        $grandtotal = 0;
        // ID & QTY
        $package = $_GET['package'];
        $qty = $_GET['qty'];
        $cost = $_GET['cost'];
        $name = $_GET['name'];
        foreach ($package as $v):
            $amount = $qty[$v]; // Qty of item.
            $obj = new stdClass();
            $obj->name = $name[$v];
            $obj->qty = $amount;
            $obj->total = $cost[$v] * $amount;
            $grandtotal = $grandtotal + $obj->total;
            $currentpackage->items[$v] = $obj;
        endforeach;

        $TotalCost = $TotalCost + $grandtotal;
        $obj = new stdClass();
        $obj->packagecost = "$" . number_format($grandtotal, 2);

        $obj->TotalCost = "$" . number_format($TotalCost, 2);
        echo json_encode($obj);
        $currentpackage->packagecost = $grandtotal;
        $currentpackage->TotalCost = $TotalCost;
        $_SESSION['package'] = $currentpackage;
    }

    function getspecial() {
        return json_decode($this->call("getonespecial"));
    }

    function getTheSpecial($id) {
        $data = json_decode($this->call("getspecialbyid/" . $id));
        return $data;
    }

    function sitemap() {
        if (!isset($_GET['vrpsitemap'])) {
            return false;
        }
        $data = json_decode($this->call("allvrppages"));
        ob_start();
        include "xml.php";
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;
        die();
    }

    function xmlexport() {
        header("Content-type: text/xml");
        echo $this->customcall("generatexml");
        die();
    }

    function robots_mod($output, $public) {
        $siteurl = get_option("siteurl");
        $output .= "Sitemap: " . $siteurl . "/?vrpsitemap=1 \n";
        return $output;
    }

    function getflipkey() {
        $id = $_GET['slug'];
        $call = "getflipkey/?unit_id=$id";
        $data = $this->customcall($call);
        echo "<!DOCTYPE html><html>";
        echo "<body>";
        echo $data;
        echo "</body></html>";
        die();
    }

    //
    //  API Calls
    //

    /**
     * Make a call to the VRPc API
     *
     * @param $call
     * @param array $params
     * @return string
     */
    function call($call, $params = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiURL . $this->apiKey . "/" . $call);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $results = curl_exec($ch);

        if (!$ch) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);
        return $results;
    }

    function customcall($call) {
        echo $this->call("customcall/$call");
    }

    function custompost($call) {
        $obj = new stdClass();
        foreach ($_POST['obj'] as $k => $v) {
            $obj->$k = $v;
        }

        $search['search'] = json_encode($obj);
        $results = $this->call($call, $search);
        $this->debug['results'] = $results;
        echo $results;
    }

    function bookSettings($propID) {
        return json_decode($this->call("booksettings/" . $propID));
    }

    //
    //  Shortcode methods
    //

    /**
     * [vrpComplexes] Shortcode
     *
     * @param array $items
     * @return string
     */
    function vrpComplexes($items = array()) {
        $items['page'] = 1;

        if (isset($_GET['page'])) {
            $items['page'] = (int)$_GET['page'];
        }

        if (isset($_GET['beds'])) {
            $items['beds'] = (int)$_GET['beds'];
        }
        if (isset($_GET['minbed'])) {
            $items['minbed'] = (int)$_GET['minbed'];
            $items['maxbed'] = (int)$_GET['maxbed'];
        }

        $obj = new stdClass();
        $obj->okay = 1;
        if (count($items) != 0) {
            foreach ($items as $k => $v) {
                $obj->$k = $v;
            }
        }

        $search['search'] = json_encode($obj);
        $results = $this->call('allcomplexes', $search);
        $results = json_decode($results);
        $content = $this->loadTheme('vrpComplexes', $results);

        return $content;
    }

    /**
     * [vrpUnits] Shortcode
     *
     * @param array $items
     * @return string
     */
    function vrpUnits($items = array()) {
        $items['page'] = 1;

        if (isset($_GET['page'])) {
            $items['page'] = (int)$_GET['page'];
        }
        if (isset($_GET['beds'])) {
            $items['beds'] = (int)$_GET['beds'];
        }

        if (isset($_GET['search'])) {
            foreach ($_GET['search'] as $k => $v):
                $items[$k] = $v;
            endforeach;
        }
        if (isset($_GET['minbed'])) {
            $items['minbed'] = (int)$_GET['minbed'];
            $items['maxbed'] = (int)$_GET['maxbed'];
        }

        $obj = new stdClass();
        $obj->okay = 1;
        if (count($items) != 0) {
            foreach ($items as $k => $v) {
                $obj->$k = $v;
            }
        }

        $search['search'] = json_encode($obj);
        $results = $this->call('allunits', $search);
        $results = json_decode($results);
        $content = $this->loadTheme('vrpUnits', $results);
        return $content;
    }

    /**
     * [vrpAdvancedSearch] Shortcode
     *
     * @return string
     */
    function vrpAdvancedSearch() {
        $data = "";
        $page = $this->loadTheme("vrpAdvancedSearch", $data);
        return $page;
    }

    /**
     * [vrpSearch] Shortcode
     *
     * @param array $arr
     * @return string
     */
    function vrpSearch($arr = array()) {
        $_GET['search'] = $arr;
        $_GET['search']['showall'] = 1;
        $data = $this->search();
        $data = json_decode($data);

        if (isset($data->type)) {
            $content = $this->loadTheme($data->type, $data);
        } else {
            $content = $this->loadTheme("results", $data);
        }
        return $content;
    }

    /**
     * [vrpComplexSearch]
     *
     * @param array $arr
     * @return string
     */
    function vrpcomplexsearch($arr = array()) {
        foreach ($arr as $k => $v):
            if (stristr($v, "|")) {
                $arr[$k] = explode("|", $v);
            }
        endforeach;
        $_GET['search'] = $arr;
        $_GET['search']['showall'] = 1;

        $this->time = microtime(true);
        $data = $this->complexsearch();

        $this->time = round((microtime(true) - $this->time), 4);
        $data = json_decode($data);
        if (isset($data->type)) {
            $content = $this->loadTheme($data->type, $data);
        } else {
            $content = $this->loadTheme("complexresults", $data);
        }
        return $content;
    }

    /**
     * [vrpAreaList] Shortcode
     *
     * @param $arr
     * @return string
     */
    function vrpAreaList($arr) {
        $area = $arr['area'];
        $r = $this->call("areabymainlocation/$area");
        $data = json_decode($r);
        $content = $this->loadTheme("arealist", $data);
        return $content;
    }

    /**
     * [vrpSpecials] Shortcode
     *
     * @param array $items
     * @return string
     *
     * @todo support getOneSpecial
     */
    function vrpSpecials($items = array()) {
        if (!isset($items['cat'])) {
            $items['cat'] = 1;
        }

        if (isset($items['special_id'])) {
            $data = json_decode($this->call("getspecialbyid/" . $id));
        } else {
            $data = json_decode($this->call("getspecialsbycat/" . $items['cat']));
        }

        return $this->loadTheme("vrpSpecials", $data);
    }

    /**
     * [vrpLinks] Shortcode
     *
     * @param $items
     * @return string
     */
    function vrpLinks($items) {
        $items['showall'] = true;

        switch ($items['type']) {
            case "Condo";
                $call = "/allcomplexes/";
                break;
            case "Villa";
                $call = "/allunits/";
                break;
        }

        $obj = new stdClass();
        $obj->okay = 1;
        if (count($items) != 0) {
            foreach ($items as $k => $v) {
                $obj->$k = $v;
            }
        }

        $search['search'] = json_encode($obj);
        $results = json_decode($this->call($call,$search));

        $ret = "<ul style='list-style:none'>";
        if ($items['type'] == 'Villa') {
            foreach ($results->results as $v):
                $ret .= "<li><a href='/vrp/unit/$v->page_slug'>$v->Name</a></li>";
            endforeach;
        } else {
            foreach ($results as $v):
                $ret .= "<li><a href='/vrp/complex/$v->page_slug'>$v->name</a></li>";
            endforeach;
        }
        $ret .= "</ul>";
        return $ret;
    }

    /**
     * [vrpCompare] Shortcode
     *
     * @return string
     */
    function vrpCompare() {
        return $this->compare();
    }

    //
    //  Wordpress Admin Methods
    //

    /**
     * Display notice for user to enter their VRPc API key.
     */
    function notice() {
        $siteurl = get_option('siteurl');
        $siteurl .= "/wp-admin/admin.php?page=vrp-api";
        echo "<div class=\"updated fade\"><b>Vacation Rental Platform</b>: <a href=\"$siteurl\">Please enter your API key.</a></div>";
    }

    /**
     * Admin nav menu items
     */
    function setupPage() {
        add_menu_page(
            'VRP', 'VRP', 7, "vrpmain", array($this, 'loadVRP'), plugin_dir_url(__FILE__) . "../themes/mountainsunset/images/shack.png"
        );
        add_submenu_page("vrpmain", 'Manage Units', 'Manage Units', 7, "vrpmain", array($this, 'loadVRP'));
        add_submenu_page("vrpmain", 'API Key', 'API Key', 8, "vrp-api", array($this, 'settingsPage'));
        // add_options_page('VRP', 'VRP', 'manage_options', 'vrp-api', array($this, 'settingsPage'));
    }

    /**
     * Displays the 'VRP Login' admin page.
     */
    function loadVRP() {
        include WP_PLUGIN_DIR . "/VRPAPI/views/login.php";
    }

    /**
     * Displays the 'VRP API Code Entry' admin page
     */
    function settingsPage() {
        include WP_PLUGIN_DIR . "/VRPAPI/views/settings.php";
    }

    /**
     * Checks if API Key is good and API is available.
     *
     * @return mixed
     */
    function testAPI() {
        return json_decode($this->call("testAPI"));
    }

    /**
     * Generates the admin automatic login url.
     *
     * @param $email
     * @param $password
     * @return array|mixed
     */
    function doLogin($email, $password) {
        $url = $this->apiURL . $this->apiKey . "/userlogin/?email=$email&password=$password";
        return json_decode(file_get_contents($url));
    }

    //
    // File Caching Methods
    //

    /**
     * store cache item.
     *
     * @param string $action
     * @param string $slug
     * @param        $object [] $object
     */
    function cache($action, $slug, $object) {
        $folder = ABSPATH . "wp-content/vrpcache/";
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $myFile = $action . $slug . ".txt";
        $fh = fopen($folder . $myFile, 'w') or die("can't open file");
        fwrite($fh, $object);
        fclose($fh);
        date_default_timezone_set('UTC');
        $obj = json_decode($object);
        if (!isset($obj->Error)) {
            wp_schedule_single_event(
                time() + 800, 'cacheClear', array('file' => $folder . $myFile, 'action' => $action, 'slug' => $slug)
            );
        }
    }

    /**
     * get Cached item
     *
     * @param $action
     * @param $slug
     * @return bool
     */
    function getCache($action, $slug) {
        $folder = ABSPATH . "wp-content/vrpcache/";
        $myFile = $action . $slug . ".txt";

        if (file_exists($folder . $myFile)) {
            $_GET['file'] = $folder . $myFile;
            $object = file_get_contents($folder . $myFile);

            if ($object) {
                $this->data = json_decode($object);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Removes cached file.
     *
     * @param $param [] $param
     */
    function clearCache($file, $action, $slug) {
        @unlink($param['file']);
        $data = $this->call("getunit/" . $slug);
        $this->cache($action, $slug, $data);
    }

}