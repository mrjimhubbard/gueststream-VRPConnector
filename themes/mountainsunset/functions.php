<?php

class mountainsunset
{

    var $otheractions = array();

    function actions() {
        add_action('wp_enqueue_scripts', array($this, 'my_scripts_method'));
        add_action('wp_print_styles', array($this, 'add_my_stylesheet'));
    }

    function my_scripts_method() {
        wp_register_script(
            'VRPjQueryUI',
            '/wp-content/plugins/VRPAPI/themes/mountainsunset/css/jquery-ui-1.11.2.custom/jquery-ui.js',
            array('jquery')
        );

        wp_enqueue_script('VRPjQueryUI');

        if (isset($_GET['action'])) {

            wp_enqueue_script(
                'BootstrapJS',
                'https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js'
            );

            wp_enqueue_script(
                'themeJS', plugins_url('/mountainsunset/js/js.js', dirname(__FILE__))
            );
            
        }
    }

    function add_my_stylesheet() {
        wp_enqueue_style('BootstrapCSS', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css');
        wp_enqueue_style('VRPjQueryUISmoothness','/wp-content/plugins/VRPAPI/themes/mountainsunset/css/jquery-ui-1.11.2.custom/jquery-ui.css');

        $myStyleUrl = plugins_url(
            '/mountainsunset/css/css.css', dirname(__FILE__)
        );

        wp_register_style('themeCSS', $myStyleUrl);
        wp_enqueue_style('themeCSS');
    }
}

function vrp_pagination($totalpages, $page = 1) {
    foreach ($_GET['search'] as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v):
                $fields_string .= 'search[' . $key . '][]=' . $v . '&';
            endforeach;
        } else {
            $fields_string .= 'search[' . $key . ']=' . $value . '&';
        }
    }
    rtrim($fields_string, '&');
    $pageurl = $fields_string;
    $_SESSION['pageurl'] = $pageurl;

    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    } else {
        $show = 10;
    }

    if ($totalpages == 1) {
        return;
    }

    echo "<ul>";
    if ($page > 1) {
        $p = $page - 1;
        echo "<li><a href='?$pageurl" . "show=$show&page=$p'>Prev</a></li>";
    }

    if ($totalpages > 5) {
        $totalrange = $page + 4;
        $startrange = 1;
        if ($page > 5) {
            $startrange = $page - 4;
        }
    } else {
        $totalrange = $totalpages;
        $startrange = 1;
    }
    if ($startrange > 1) {
        echo "<li><a href='?$pageurl" . "show=$show&page=1'>1</a></li>";

    }

    foreach (range($startrange, $totalrange) as $p) {
        if ($page == $p) {
            echo "<li class='active'><a href='?$pageurl" . "show=$show&page=$p'>$p</a></li>";
        } else {
            echo "<li><a href='?$pageurl" . "show=$show&page=$p'>$p</a></li>";
        }
    }
    if ($totalpages > 5) {
        echo "<li><a href='?$pageurl" . "show=$show&page=$totalpages'>Last</a></li>";
    }

    if ($page < $totalpages) {
        $p = $page + 1;
        echo "<li><a href='?$pageurl" . "show=$show&page=$p'>Next</a></li>";
    }
    echo "</ul>";
}

function vrp_paginationmobile($totalpages, $page = 1) {
    foreach ($_GET['search'] as $key => $value) {
        $fields_string .= 'search[' . $key . ']=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $pageurl = $fields_string;
    $_SESSION['pageurl'] = $pageurl;
    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    } else {
        $show = 5;
    }
    if ($totalpages == 1) {
        return;
    }

    if ($page > 1) {
        $p = $page - 1;
        echo "<a href='?$pageurl" . "show=$show&page=$p' data-icon='arrow-l' data-role='button'>Prev</a>";
    }

    if ($page < $totalpages) {
        $p = $page + 1;
        echo "<a href='?$pageurl" . "show=$show&page=$p' data-icon='arrow-r' data-role='button'>Next</a>";
    }
}

function vrp_pagination2($totalpages, $page = 1) {
    /* foreach ($_GET['search'] as $key => $value) {
      $fields_string .= 'search[' . $key . ']=' . $value . '&';
      }
      rtrim($fields_string, '&'); */
    $beds = "";
    if (isset($_GET['beds'])) {
        $beds = (int)$_GET['beds'];
        $beds = "&beds=" . $beds;
    }

    if (isset($_GET['minbed'])) {
        $minbed = $_GET['minbed'];
        $maxbed = $_GET['maxbed'];
        $beds = "&minbed=" . $minbed . "&maxbed=" . $maxbed;
    }
    $pageurl = "";
    if (isset($_GET['search'])) {
        foreach ($_GET['search'] as $k => $v):
            $pageurl .= "search[$k]=$v&";
        endforeach;
    }
    if ($totalpages == 1) {
        return;
    }
    echo "<ul class='vrp-pagination'>";
    if ($page > 1) {
        $p = $page - 1;
        echo "<li><a href='?$pageurl" . "page=$p" . $beds . "'>Prev</a></li>";
    }
    foreach (range(1, $totalpages) as $p) {
        if ($page == $p) {
            echo "<li role='button' class='ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'><span class='ui-button-text'><b class='chosenone'>$p</b></span></li>";
        } else {
            echo "<li><a href='?$pageurl" . "page=$p" . $beds . "'>$p</a></li>";
        }
    }
    if ($page < $totalpages) {
        $p = $page + 1;
        echo "<li><a href='?$pageurl" . "page=$p" . $beds . "'>Next</a></li>";
    }
    echo "</ul>";
}

function vrpsortlinks($unit) {

    if (isset($_GET['search']['order'])) {
        $order = $_GET['search']['order'];
    } else {
        $order = "low";
    }

    foreach ($_GET['search'] as $key => $value) {
        if ($key == 'sort') {
            continue;
        }
        if ($key == 'order') {
            continue;
        }
        $fields_string .= 'search[' . $key . ']=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $pageurl = $fields_string;


    $sortoptions = array("Bedrooms");

    if (isset($unit->Rate)) {
        $sortoptions[] = "Rate";
    }
    // echo "<select class='vrpsorter ui-widget ui-state-default' style='font-size:11px;'><option></option>";
    if (isset($_GET['search']['sort'])) {
        $sort = $_GET['search']['sort'];
    } else {
        $sort = "";
    }
    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    } else {
        $show = 10;
    }
    foreach ($sortoptions as $s) {

        if ($sort == $s) {
            if ($order == "low") {
                $order = "high";
                $other = "low";
            } else {
                $order = "low";
                $other = "high";
            }

            echo "<li><a href='?$pageurl" . "search[sort]=$s&show=$show&search[order]=$order' selected='selected'>$s ($other to $order)</a></li>";
            echo "<li><a href='?$pageurl" . "search[sort]=$s&show=$show&search[order]=$order'>$s ($order to $other)</a></li>";
            continue;
        }

        echo "<li><a href='?$pageurl" . "search[sort]=$s&show=$show&search[order]=low'>$s (low to high)</a></li>";
        echo "<li><a href='?$pageurl" . "search[sort]=$s&show=$show&search[order]=high'>$s (high to low)</a></li>";
    }
    // echo "</select>";
}

function vrpsortlinks2($unit) {

    if (isset($_GET['search']['order'])) {
        $order = $_GET['search']['order'];
    } else {
        $order = "low";
    }

    foreach ($_GET['search'] as $key => $value) {
        if ($key == 'sort') {
            continue;
        }
        if ($key == 'order') {
            continue;
        }
        $fields_string .= 'search[' . $key . ']=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $pageurl = $fields_string;
    if (isset($_GET['beds'])) {
        $pageurl .= "beds=" . $_GET['beds'] . "&";
    }

    if (isset($_GET['minbed'])) {
        $pageurl .= "minbed=" . $_GET['minbed'] . "&maxbed=" . $_GET['maxbed'] . "&";
    }
    $sortoptions = array("Bedrooms" => "Bedrooms", "minrate" => "Minimum Rate", "maxrate" => "Maximum Rate");

    if (isset($unit->Rate)) {
        $sortoptions[] = "Rate";
    }
    echo "<select class='vrpsorter ui-widget ui-state-default' style='font-size:11px;'>";
    if (isset($_GET['search']['sort'])) {
        $sort = $_GET['search']['sort'];
    } else {
        $sort = "";
    }
    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    } else {
        $show = 10;
    }
    foreach ($sortoptions as $s => $val) {

        if ($sort == $s) {
            if ($order == "low") {
                $order = "high";
                $other = "low";
            } else {
                $order = "low";
                $other = "high";
            }

            echo "<option value='?$pageurl" . "search[sort]=$s&show=$show&search[order]=$order' selected='selected'>$val ($other to $order)</option>";
            echo "<option value='?$pageurl" . "search[sort]=$s&show=$show&search[order]=$order'>$val ($order to $other)</option>";
            continue;
        }

        echo "<option value='?$pageurl" . "search[sort]=$s&show=$show&search[order]=low'>$val (low to high)</option>";
        echo "<option value='?$pageurl" . "search[sort]=$s&show=$show&search[order]=high'>$val (high to low)</option>";
    }
    echo "</select>";
}

function vrp_resultsperpage() {
    foreach ($_GET['search'] as $key => $value) {
        $fields_string .= 'search[' . $key . ']=' . $value . '&';
    }

    rtrim($fields_string, '&');
    $pageurl = $fields_string;

    if (isset($_GET['show'])) {
        $show = (int)$_GET['show'];
    } else {
        $show = 10;
    }
    echo "<ul class='vrpshowing'>";
    foreach (array(10, 20, 30) as $v) {
        if ($show == $v) {
            echo "<li><a href='?$pageurl&show=10'><b>$v</b></a></li>";
        } else {
            echo "<li><a href='?$pageurl&show=$v'>$v</a></li>";
        }
    }
    echo "</ul>";
}

function dateSeries($start_date, $num) {
    $dates = array();

    $dates[0] = $start_date;
    for ($i = 0; $i < $num; $i++) {
        $start = strtotime(end($dates));
        $day = mktime(0, 0, 0, date("m", $start), date("d", $start) + 1, date("Y", $start));
        $dates[] = date('Y-m-d', $day);
    }
    return $dates;
}

function daysTo($from, $to, $round = true) {
    $from = strtotime($from);
    $to = strtotime($to);
    $diff = $to - $from;
    $days = $diff / 86400;
    return $round == true ? floor($days) : round($days, 2);
}

function vrpCalendar($r, $totalMonths = 3) {

    $datelist = array();
    $arrivals = array();
    $departs = array();
    foreach ($r as $v) {
        $from_date = $v->start_date;
        $arrivals[] = $from_date;
        $to_date = $v->end_date;
        $departs[] = $to_date;
        $num = daysTo($from_date, $to_date);
        $datelist[] = dateSeries($from_date, $num);
    }

    $final_date = array();
    foreach ($datelist as $v) {
        foreach ($v as $v2) {
            $final_date[] = $v2;
        }
    }
    //echo "<pre>";
    //print_r($final_date);
    //echo "</pre>";
    $today = strtotime(date("Y") . "-" . date("m") . "-01");
    $calendar = new Calendar(date('Y-m-d'));
    $calendar->link_days = 0;
    $calendar->highlighted_dates = $final_date;
    $calendar->arrival_dates = $arrivals;
    $calendar->depart_dates = $departs;
    if (isset($_GET["debug"])) {
    }
    /*  $nextyear = date("Y", mktime(0, 0, 0, date("m") + 1, date("d"), date("Y")));
      $nextmonth = date("m", mktime(0, 0, 0, date("m") + 1, date("d"), date("Y")));
      $nextyear2 = date("Y", mktime(0, 0, 0, date("m") + 2, date("d"), date("Y")));
      $nextmonth2 = date("m", mktime(0, 0, 0, date("m") + 2, date("d"), date("Y")));
     * 
     */
    $theKey = "<br style=\"clear:both;\" /><br/><div id=\"calKey\"><div style=\"float:left;\"><span style=\"float:left;display:block;width:15px;height:15px;background:#ffffff;border:1px solid #404040;\"> &nbsp;</span> <span style=\"float:left;\">&nbsp; Available</span> <span style=\"float:left;display:block;width:15px;height:15px;background:#BDBDBD;margin-left:10px;border:1px solid #404040;\"> &nbsp;</span> <span style=\"float:left;\">&nbsp; Unavailable</span><br style=\"clear:both;\" /></div><br style=\"clear:both;\" /></div>";
    $ret = "";
    $x = 0;
    $curYear = date('Y');
    for ($i = 0; $i < $totalMonths; $i++) {

        $nextyear = date("Y", mktime(0, 0, 0, date("m", $today) + $i, date("d", $today), date("Y", $today)));
        $nextmonth = date("m", mktime(0, 0, 0, date("m", $today) + $i, date("d", $today), date("Y", $today)));

        $ret .= $calendar->output_calendar($nextyear, $nextmonth);
        if ($x == 3) {
            // $ret.="<br style=\"clear:both;\" /><br style=\"clear:both;\" />";
            $x = -1;
        }
        $x++;
    }


    return "" . $ret . $theKey;
}

//
//  Shortcodes
//

function vrpshort($items) {
    $items['sort'] = "Name";
    $items['order'] = "low";
    if (isset($items['complexid'])) {
        $cid = $items['complexid'];
        unset($items['complexid']);
        $items['ComplexID'] = $cid;
    }
    ob_start();
    include TEMPLATEPATH . "/vrp/shortcode.php";

    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

add_shortcode("vrpshort", "vrpshort");

function vrplinks($items) {
    $items['showall'] = true;

    global $vrp;
    switch ($items['type']) {
        case "Condo";
            $url = $vrp->apiURL . $vrp->apiKey . "/allcomplexes/";
            break;
        case "Villa";
            $url = $vrp->apiURL . $vrp->apiKey . "/allunits/";
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
    $ch = curl_init();

//set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $search);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
//execute post
    $results = curl_exec($ch);

    $results = json_decode($results);

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

add_shortcode("vrplinks", "vrplinks");