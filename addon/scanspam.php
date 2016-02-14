#!/usr/bin/php
<?php
/*
 * Title: ScanSpam plugin.
 * Version: 1.0.1 (15/Feb/2016)
 * Author: Denis.
 * License: GPL.
 * Site: https://montenegro-it.com
 * Email: contact@montenegro-it.com
 */
@set_time_limit(0);
@error_reporting(E_NONE);
@ini_set('display_errors', 0);
$xml_string = file_get_contents("php://stdin");
$doc = simplexml_load_string($xml_string);
$func = $doc->params->func;
$sok = $doc->params->sok;
$elid = $doc->params->elid;
$user = $doc["user"];
$level = $doc["level"];
define("PLUGIN_PATH", "/usr/local/ispmgr/var/.plugin_scanspam/");

switch ($func) {
    case "scanspam.setting";
        if ($sok == "ok") {
            $data['scan_enable'] = $doc->params->scan_enable;
            $data['delete_enable'] = $doc->params->delete_enable;
            $data['time'] = intval($doc->params->time);
            file_put_contents(PLUGIN_PATH . "setting.txt", json_encode($data));
            chmod(PLUGIN_PATH . "setting.txt", 0600);

            $task = "#!/bin/bash\n\nPATH=/usr/bin:/usr/local/bin:/sbin:/bin:/usr/sbin\n\n";
            if ($data['scan_enable'] == "on") {
                $task.= "if [ -f /usr/bin/sa-learn ]; then\nsa-learn --no-sync --spam /var/www/*/data/email/*/*/.maildir/{.Junk,.SPAM}/{cur,new}\nsa-learn --sync\nfi\n\n";
            }
            if ($data['delete_enable'] == "on") {
                $task.= "find /var/www  -regextype posix-extended  -iregex \".*/.maildir/(.junk|.spam)/(new|cur).*\" -type f -delete  -mtime +" . $data['time'] . "\n\n";
            }
            file_put_contents("/usr/local/ispmgr/addon/scanspam.task.sh", $task);
            chmod("/usr/local/ispmgr/addon/scanspam.task.sh", 0770);
            $doc->addChild("ok", "ok");
            break;
        }

        if (is_file(PLUGIN_PATH . "setting.txt")) {
            $data = json_decode(file_get_contents(PLUGIN_PATH . "setting.txt"));
            $scan_enable = $data->scan_enable->{0};
            $delete_enable = $data->delete_enable->{0};
            $time_select = $data->time;
        } else {
            $time_select = 30;
            $scan_enable = "";
            $delete_enable = "";
        }


        $time = array(5 => 5, 10 => 10, 15 => 15, 30 => 30, 60 => 60, 180 => 180, 365 => 365);
        $slist = $doc->addChild('slist');
        $slist->addAttribute('name', 'time');
        foreach ($time AS $key => $row) {
            $val = $slist->addChild('val', $row);
            $val->addAttribute('key', $key);
        }
        $doc->addChild("time", $time_select);
        
        if ($scan_enable) {
            $doc->addChild("scan_enable", $scan_enable);
        }
        if ($delete_enable) {
            $doc->addChild("delete_enable", $delete_enable);
        }
        break;
        
 }
echo $doc->asXML();
