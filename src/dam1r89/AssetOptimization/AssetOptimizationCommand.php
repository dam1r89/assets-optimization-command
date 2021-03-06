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

        $original = $this->argument('layout');

        $withPrefix = $this->getLayoutWithPrefix($original);

        try {
            $sourceLayoutPath = $this->finder->find($withPrefix);
            $this->comment('Orig file foud, using it as a source.');
            $destLayoutPath = $this->removeOrigPrefix($sourceLayoutPath);

        } catch (\InvalidArgumentException $e) {

            $this->info('Making backup of the source files...');
            $sourceLayoutPath = $this->finder->find($original);
            $fs->copy($sourceLayoutPath, $this->addOrigPrefix($sourceLayoutPath));
            $destLayoutPath = $sourceLayoutPath;
            $sourceLayoutPath = $this->addOrigPrefix($sourceLayoutPath);

        }

        if ($this->option('reset')) {
            $fs->copy($sourceLayoutPath, $destLayoutPath);
            $fs->delete($sourceLayoutPath);
            $this->info('Everything is back to normal.');
            return;
        }


        $layout = $fs->get($sourceLayoutPath);

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
        $fs->put($destLayoutPath, $layout);

        $this->info('Done!');

    }

    /**
     * @param $original
     * @return string
     */
    private function getLayoutWithPrefix($original)
    {
        $layoutParts = explode('.', $original);
        $lastIndex = count($layoutParts) - 1;
        $layoutParts[$lastIndex] = 'orig-' . $layoutParts[$lastIndex];

        $withPrefix = implode('.', $layoutParts);
        return $withPrefix;
    }

    /**
     * @param $backupLayoutPath
     * @return string
     */
    private function removeOrigPrefix($backupLayoutPath)
    {
        return pathinfo($backupLayoutPath, PATHINFO_DIRNAME) . '/' . substr(pathinfo($backupLayoutPath, PATHINFO_BASENAME), strlen(self::ORIG_PREFIX));
    }

    /**
     * @param $layoutPath
     * @return string
     */
    private function addOrigPrefix($layoutPath)
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
            array('reset', 'r', InputOption::VALUE_NONE, 'If layout should be reverted. Enter name of the original layout.'),
        );
    }

}
