<?php
/**
 * Created by PhpStorm.
 * User: dam1r89
 * Date: 10/8/14
 * Time: 12:31 PM
 */

namespace dam1r89\AssetOptimization;


class JavaScriptPacker {


    private $fs;
    private $target;
    private $layoutContent;
    private $options;
    private $defaults;

    function __construct($fs, $layoutContent, $output)
    {
        $this->fs = $fs;
        $this->target = $output . '.js';
        $this->layoutContent = $layoutContent;
        $this->options = $this->defaults = array(
            'minify' => false,
        );

    }

    public function setOptions($options){

        if (!is_array($options)){
            return;
        }

        $this->options = array_merge($this->defaults, $options);
    }

    private function option($option){
        if (isset($option)){
            return $this->options[$option];
        }
        return null;
    }

    public function process()
    {

        $scriptContent = $this->concatScripts();

        if ($this->option('minify')){

            $scriptContent = \JShrink\Minifier::minify($scriptContent, array('flaggedComments' => false));
        }

        $this->fs->put(public_path($this->target), $scriptContent);

        $this->cleanLayout();

        return $this->layoutContent;
    }

    private function extractScripts()
    {
        preg_match_all('/HTML::script\(\'(.*)\'\)/', $this->layoutContent, $matches);
        return $matches[1];
    }

    private function cleanLayout()
    {

        $out = preg_replace('/{{\s*HTML::script.*?}}/', '', $this->layoutContent);
        $out = preg_replace('/\n\s*\n/', "\n", $out);
        $out = str_replace('</body>', "\t{{ HTML::script('$this->target') }}\n</body>", $out);
        $this->layoutContent = $out;
    }

    /**
     * @return string
     */
    private function concatScripts()
    {
        $scripts = $this->extractScripts();

        $scriptContent = '';
        foreach ($scripts as $script) {
            $scriptContent .= $this->fs->get(public_path($script)) . ';';
        }
        return $scriptContent;
    }
}
