<?php
/**
 * Created by PhpStorm.
 * User: dam1r89
 * Date: 10/8/14
 * Time: 11:42 AM
 */

namespace dam1r89\AssetOptimization;


class StylePacker {

    private $fs;

    function __construct($fs)
    {
        $this->fs = $fs;
    }

    public function processStyles($layoutContent, $output)
    {

        $target = $output . '.css';

        // get all used scripts from layout
        $links = $this->getStyleLinks($layoutContent);

        $cssContent = '';
        foreach ($links as $link) {
            $this->info($link);
            $file = public_path($link);
            $source = pathinfo($file, PATHINFO_DIRNAME);
            $style = $this->fs->get($file) . "\n";

            $style = preg_replace_callback('/url\((.*?)\)/i', function ($matches) use ($source, $target) {

                return $this->resolveLinks($matches, $source, $target);

            }, $style);

            $cssContent .= $style;
        }


        $cssContent = $this->pullImportsUp($cssContent);
        $cssContent = $this->cleanComments($cssContent);

        $this->fs->put(public_path($target), $cssContent);

        $layoutContent = preg_replace('/{{\s*HTML::style.*?}}/', '', $layoutContent);
        $layoutContent = preg_replace('/\n\s*\n/', "\n", $layoutContent);

        $layoutContent = str_replace('</head>', "\t{{ HTML::style('$target') }}\n</head>", $layoutContent);
        return $layoutContent;
    }

    public function resolveLinks($matches, $source, $target){

        $filename = $this->filenameFromUrl($matches[1]);

        if ($this->fs->exists("$source/$filename")) {

            $backCount = substr_count($target, '/');
            $back = str_repeat('../', $backCount);

            $urlBase = substr($source, strlen(public_path()) + 1);
            $filename = PathHelper::normalize("$back$urlBase/{$filename}");

            return "url($filename)";
        }
        return "url($filename)";
    }

    /**
     * @param $url
     * @return string
     */
    function filenameFromUrl($url)
    {
        $filename = trim($url, ' "\'');
        if (strpos($filename, '?') !== false && strpos($filename, 'http') === false) {
            $filename = substr($filename, 0, strpos($filename, '?'));
        }
        return $filename;
    }

    /**
     * @param $cssContent
     * @return string
     */
    private function pullImportsUp($cssContent)
    {
        preg_match_all('/@import.*/', $cssContent, $matches);
        $cssContent = implode("\n", $matches[0]) . "\n" . preg_replace('/@import.*/', '', $cssContent);

        return $cssContent;
    }

    private function cleanComments($cssContent)
    {
        $cssContent = preg_replace("/\\/\\*(.|\n|\r)*?\\*\\//", '', $cssContent);
        return preg_replace("/(\\s|\n|\r)/", ' ', $cssContent);
    }

    private function info($message){

    }

    /**
     * @param $layoutContent
     * @return mixed
     */
    private function getStyleLinks($layoutContent)
    {
        preg_match_all('/HTML::style\(\'(.*)\'\)/', $layoutContent, $matches);
        return $matches[1];
    }
}
