<?php

namespace dam1r89\AssetOptimization;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem as FileSystem;
use Illuminate\View\ViewFinderInterface as ViewFinderInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class AssetOptimizationCommand extends Command
{

    const ORIG_PREFIX = 'orig-';
    protected $name = 'asopt';
    protected $description = 'Optimize assets from specified layout.';
    protected $fs;
    protected $finder;

    public function __construct(ViewFinderInterface $finder)
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

            $backupLayoutPath = $this->finder->find($this->argument('layout'));
            $layoutPath = $this->addOrigPrefix($backupLayoutPath);
            $this->info('Using source file '. PathHelper::normalize($backupLayoutPath));


        } else {

            $layoutPath = $this->finder->find($this->argument('layout'));
            $backupLayoutPath = $this->removeOrigPrefix($layoutPath);
            $this->info('Making backup...');
            $fs->copy($layoutPath, $backupLayoutPath);

        }

        $layout = $fs->get($backupLayoutPath);

        $this->info('Processing JavaScript...');
        $jsPacker = new JavaScriptPacker($fs, $layout, $this->argument('output'));

        $jsPacker->setOptions(array(
            'minify' => $this->option('minify')
        ));

        $layout = $jsPacker->process();

        $this->info('Processing styles...');
        $stylePacker = new StylePacker($fs, $layout, $this->argument('output'));
        $layout = $stylePacker->process();

        $this->info('Replacing old layout');
        $fs->put($layoutPath, $layout);

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
        return array(
            array('minify', 'm', InputOption::VALUE_NONE, 'Should JavaScript be minified.'),
        );
    }

}
