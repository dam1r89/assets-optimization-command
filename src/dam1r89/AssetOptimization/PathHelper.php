<?php
/**
 * Created by PhpStorm.
 * User: dam1r89
 * Date: 10/7/14
 * Time: 4:42 PM
 */

namespace dam1r89\AssetOptimization;


class PathHelper {

    public static function normalize($path)
    {

        $parts = (explode('/', $path) );
        $collector = array();

        foreach ($parts as $part) {
            if ($part === '.') continue;
            if ($part === '..' && count($collector) !== 0 && $collector[count($collector) - 1] !== '..') {
                array_pop($collector);
                continue;
            }
            array_push($collector, $part);

        }

        return implode('/', $collector);
    }

    public static function pathDiff($from, $to){

        $backCount = substr_count($to, '/');

        $back = str_repeat('../',$backCount);

        return "$back$from";


    }
}
