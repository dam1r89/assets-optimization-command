<?php

use \dam1r89\AssetOptimization\PathHelper;

/**
 * Created by PhpStorm.
 * User: dam1r89
 * Date: 10/7/14
 * Time: 4:34 PM
 */
class NormalizeTest extends PHPUnit_Framework_TestCase
{

    function testNormalization()
    {
        $this->assertEquals('../../bla.css', PathHelper::normalize('../../bla.css'));

        $this->assertEquals('bla.css', PathHelper::normalize('css/.././bla.css'));
        $this->assertEquals('../bla.css', PathHelper::normalize('../css/.././bla.css'));
        $this->assertEquals('bla.css', PathHelper::normalize('./css/.././bla.css'));
    }

    function testPathDiff(){

        $from = 'library/something/img.png';
        $to = 'css/main.css';

        $this->assertEquals('../library/something/img.png', PathHelper::pathDiff($from, $to));


    }

    function testMultipleReplace()
    {
        $subj = 'url(one); url(two); url(three)';
        $out = preg_replace_callback('/url\((.*?)\)/', function($matches){
           return 'url("'.$matches[1].'")';
        }, $subj);
        $this->assertEquals('url("one"); url("two"); url("three")', $out);
    }

}
