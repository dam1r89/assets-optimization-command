<?php
/**
 * Created by PhpStorm.
 * User: dam1r89
 * Date: 10/8/14
 * Time: 11:42 AM
 */

namespace dam1r89\AssetOptimization;


class StylePacker
{

    const REGEXP_CSS_COMMENTS = "/\\/\\*(.|\n|\r)*?\\*\\//";
    const REGEXP_MULTILINE_SPACE = "/(\\s|\n|\r)/";
    const REGEXP_BLADE_STYLE_TAG = '/{{\s*HTML::style.*?}}/';
    const REGEXP_BLADE_STYLE_SOURCE = '/HTML::style\(\'(.*)\'\)/';
    const REGEXP_CSS_URL = '/url\((.*?)\)/i';

    private $fs;
    private $cssContent = '';
    private $layoutContent;
    private $target;

    function __construct($fs, $layoutContent, $output)
    {
        $this->layoutContent = $layoutContent;
        $this->fs = $fs;
        $this->target = $output . '.css';
    }

    public function process()
    {

        $this->extractStyleLinks();
        $this->pullImportsUp();
        $this->cleanComments();
        $this->saveStyleToTarget();

        $this->cleanLayout();

        return $this->layoutContent;
    }

    private function extractStyleLinks()
    {
        $links = $this->getStyleLinks();

        foreach ($links as $link) {

            $this->info($link);

            $file = public_path($link);
            $source = pathinfo($file, PATHINFO_DIRNAME);
            $style = $this->fs->get($file);

            $style = preg_replace_callback(self::REGEXP_CSS_URL, function ($matches) use ($source) {

                return $this->resolveLinks($matches, $source);

            }, $style);

            $this->cssContent .= $style;
        }
    }

    private function getStyleLinks()
    {
        preg_match_all(self::REGEXP_BLADE_STYLE_SOURCE, $this->layoutContent, $matches);
        return $matches[1];
    }

    private function info($message)
    {

    }

    public function resolveLinks($matches, $source)
    {

        $filename = $this->filenameFromUrl($matches[1]);

        if ($this->fs->exists("$source/$filename")) {

            $backCount = substr_count($this->target, '/');
            $back = str_repeat('../', $backCount);

            $urlBase = substr($source, strlen(public_path()) + 1);
            $filename = PathHelper::normalize("$back$urlBase/{$filename}");

        }
        return "url($filename)";
    }

    function filenameFromUrl($url)
    {
        $filename = trim($url, ' "\'');
        if (strpos($filename, '?') !== false && strpos($filename, 'http') === false) {
            $filename = substr($filename, 0, strpos($filename, '?'));
        }
        return $filename;
    }

    private function pullImportsUp()
    {
        preg_match_all('/@import.*/', $this->cssContent, $matches);
        $this->cssContent = implode("\n", $matches[0]) . "\n" . preg_replace('/@import.*/', '', $this->cssContent);
    }

    private function cleanComments()
    {
        $this->cssContent = preg_replace(self::REGEXP_CSS_COMMENTS, '', $this->cssContent);
        $this->cssContent = preg_replace(self::REGEXP_MULTILINE_SPACE, ' ', $this->cssContent);
    }

    private function saveStyleToTarget()
    {
        $this->fs->put(public_path($this->target), $this->cssContent);
    }


    private function cleanLayout()
    {
        $lc = preg_replace(self::REGEXP_BLADE_STYLE_TAG, '', $this->layoutContent);
        $lc = preg_replace('/\n\s*\n/', "\n", $lc);
        $targetStyle = $this->target.'?time='.time();
        $lc = str_replace('</head>', "\t{{ HTML::style('$targetStyle') }}\n</head>", $lc);
        $this->layoutContent = $lc;
    }
}
