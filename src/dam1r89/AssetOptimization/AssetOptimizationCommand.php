<?php

namespace dam1r89\AssetOptimization;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem as FileSystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class AssetOptimizationCommand extends Command
{

    const ORIG_PREFIX = 'orig-';
    protected $name = 'asopt';
    protected $description = 'Optimize assets from specified layout.';
    protected $fs;
    protected $finder;

    public function __construct(\Illuminate\View\ViewFinderInterface $finder)
    {
        $this->fs = new Filesystem();
        $this->finder = $finder;
        parent::__construct();
    }

    public function fire()
    {
        /**
         * @var $fs \Illuminate\Filesystem\Filesystem
         */

        $fs = $this->fs;

        if ($this->isUsingOrigFile()) {

            $this->info('Using source file...');
            $backupLayoutPath = $this->finder->find($this->argument('layout'));
            $layoutPath = $this->addOrigPrefix($backupLayoutPath);

        } else {

            $layoutPath = $this->finder->find($this->argument('layout'));
            $backupLayoutPath = $this->removeOrigPrefix($layoutPath);
            $this->info('Making backup...');
            $fs->copy($layoutPath, $backupLayoutPath);

        }

        $out = $fs->get($backupLayoutPath);

//        $out = $this->processScripts($out);
        $stylePacker = new StylePacker($fs);

        $out = $stylePacker->processStyles($out, $this->argument('output'));

        $this->info('Replacing old layout');
        $fs->put($layoutPath, $out);

        $this->info('Done!');


    }

    /**
     * @return bool
     */
    private function isUsingOrigFile()
    {
        return strpos($this->argument('layout'), self::ORIG_PREFIX) !== -1;
    }

    /**
     * @param $backupLayoutPath
     * @return string
     */
    private function addOrigPrefix($backupLayoutPath)
    {
        return pathinfo($backupLayoutPath, PATHINFO_DIRNAME) . '/' . substr(pathinfo($backupLayoutPath, PATHINFO_BASENAME), strlen(self::ORIG_PREFIX));
    }

    /**
     * @param $layoutPath
     * @return string
     */
    private function removeOrigPrefix($layoutPath)
    {
        return pathinfo($layoutPath, PATHINFO_DIRNAME) . '/' . self::ORIG_PREFIX . pathinfo($layoutPath, PATHINFO_BASENAME);
    }

    protected function getArguments()
    {
        return array(
            array('layout', InputArgument::REQUIRED, 'Layout name (like in the View::make function) eg. layout.site'),
            array('output', InputArgument::REQUIRED, 'Output path'),
        );
    }

    protected function getOptions()
    {
        return array(//            array('orig', 'o', InputOption::VALUE_OPTIONAL, 'Are you using orig file?', false),
        );
    }

    /**
     * @param $layoutContent string
     * @return string
     */
    private function processScripts($layoutContent)
    {

        $matches = array();

        preg_match_all('/HTML::script\(\'(.*)\'\)/', $layoutContent, $matches);

        $all = '';
        foreach ($matches[1] as $asset) {
            $this->info($asset);
            $all .= $this->fs->get(public_path($asset)) . ';';
        }

        $this->info('Minifying JavaScript...');

        $all = \JShrink\Minifier::minify($all, array('flaggedComments' => false));

        $concat = $this->argument('output') . '.js';

        $this->fs->put(public_path($concat), $all);


        $out = preg_replace('/{{\s*HTML::script.*?}}/', '', $layoutContent);
        $out = preg_replace('/\n\s*\n/', "\n", $out);

        $out = str_replace('</body>', "\t{{ HTML::script('$concat') }}\n</body>", $out);
        return $out;
    }

}
