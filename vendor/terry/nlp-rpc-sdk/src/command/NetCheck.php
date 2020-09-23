<?php
/**
 * Author: huanw2010@gmail.com
 * Date: 2020/2/5 15:34
 */

namespace terry\nlp;
require_once __DIR__ . '/../../../../autoload.php';

class NetCheck
{
    protected function check($point)
    {
        list($host, $port) = explode(":", $point);
        $fp = @fsockopen($host, $port, $err_no, $err_str, 3);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * @param $prefix
     * @param $status
     * @param $length
     * @return string
     */
    private function formatMessage($prefix, $status, $length)
    {
        $prefix_len = strlen($prefix);
        $prefix = $prefix_len < 21 ? $prefix . str_repeat(" ", 21 - $prefix_len) : $prefix;
        $repeat_len = $length - (strlen($prefix . $status));
        $repeat_len = $repeat_len < 0 ? 0 : $repeat_len;
        return $prefix . str_repeat('.', $repeat_len) . $status;
    }

    /**
     * get module short name
     * @param $modules
     * @return array
     */
    private function getShortName($modules)
    {
        $short_names = array_values($modules);
        array_walk($short_names, function (&$name) {
            $name = str_replace("nlp-", "", $name);
        });
        return $short_names;
    }

    public function run($module = null)
    {
        $spec_module = !empty($module);
        $module = 'nlp-' . $module;
        $modules = RpcService::getModules();
//        print_r($modules);

        if ($spec_module && !in_array($module, $modules)) {
            printf("Only support services [%s]", implode(",", $this->getShortName($modules)));
            exit;
        }
        if ($spec_module) {
            $modules = [$module];
        }

        $total_points = 0;
        $total_passed = 0;
        $total_failed = 0;
        $all_failed_points = [];
        $t = microtime(true);
        foreach ($modules as $module) {
            printf("check module [%s] %s\n", $module, str_repeat('-', 80));
            $points = RpcService::getServiceHostByModule($module);
            $passed = 0;
            $failed = 0;
            $failed_points = [];
            $total_points += count($points);
            foreach ($points as $point) {
                $status = $this->check($point);
                if ($status) {
                    echo $this->formatMessage($point, '[passed]', 80) . PHP_EOL;
                    $passed++;
                    $total_passed++;
                } else {
                    echo $this->formatMessage($point, '[failed]', 80) . PHP_EOL;
                    $failed++;
                    $failed_points[] = $point;
                    $total_failed++;
                    if (!array_key_exists($module, $all_failed_points)) {
                        $all_failed_points[$module] = [];
                    }
                    $all_failed_points[$module][] = $point;
                }
            }
            printf("total points [%s],passed [%s],failed [%s] %s\n",
                count($points), $passed, $failed, str_repeat('-', 80));
            if (!empty($failed_points)) {
                printf("failed points [%s]\n", implode(",", array_unique($failed_points)));
            }
        }
        if (!$spec_module) {
            echo str_repeat("-", 100) . PHP_EOL;
            printf("total module [%s],total points [%s],passed [%s],failed [%s]\n",
                count($modules), $total_points, $total_passed, $total_failed);
            foreach ($all_failed_points as $module => $points) {
                if (count($points) > 0) {
                    printf("rpc module [%s] failed points[%s]\n", $module, implode(",", $points));
                }
            }
        }
        printf("cost:%.2fs\n", microtime(true) - $t);
    }

    public function showUsage()
    {
        printf("Usage:\n\tphp NetCheck.php -s [serviceName] \n\tOnly support [%s]\n",
            implode(",", $this->getShortName(RpcService::getModules())));
    }
}

$app = new NetCheck();
$params = getopt("s:h::");
$service = empty($params['s']) ? null : $params['s'];
if (isset($params['h'])) {
    $app->showUsage();
    exit;
}
$app->run($service);