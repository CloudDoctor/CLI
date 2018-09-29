<?php
namespace CloudDoctor;

use CLIOpts\CLIOpts;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\CliMenuBuilder;
use PhpSchool\CliMenu\MenuItem\AsciiArtItem;
use PhpSchool\CliMenu\MenuItem\SelectableItem;
class Cli{

    /** @var CliMenuBuilder */
    protected $menu;

    /** @var CloudDoctor */
    protected $cloudDoctor;

    public function __construct(CloudDoctor $cloudDoctor = null)
    {
        if($cloudDoctor){
            $this->cloudDoctor = $cloudDoctor;
        }else{
            $cloudDoctor = new CloudDoctor();
        }

        $cloudDoctor->assertFromFile(
            "cloud-definition.yml",
            "cloud-definition.override.yml",
            "cloud-defintion.automation-override.yml"
        );
        
        $this->menu = new CliMenuBuilder();
        $this->menu->setBackgroundColour('black');
        $this->menu->setForegroundColour('blue');
        $this->menu->setTitle($this->automizeInstanceName);

        $scope = $this;
        $this->menu->addItem('Deploy', function (CliMenu $menu) use ($scope) {
            /** @var Automize $scope */
            $scope->cloudDoctor->deploy();
            $menu->redraw();
        });
    }

    private function checkForArguments()
    {
        $arguments = "
            Usage: {self} [options]
            -D --deploy Run Deployment
            --purge Purge everything deployed. Danger will robinson!
            ";
        $values = CLIOpts::run($arguments);

        return $values;
    }

    public function run(){
        $values = $this->checkForArguments();
        if ($values->count()) {
            $this->runNonInteractive();
        } else {
            $this->runInteractive();
        }
    }

    private function runInteractive()
    {
        $this->buildMenu();
        $this->menu->open();
    }

    private function runNonInteractive()
    {
        if($this->zenderator) {
            $this->zenderator->disableWaitForKeypress();
        }
        $values = $this->checkForArguments();
        // non-interactive mode
        foreach ($values as $name => $value) {
            switch ($name) {
                case 'deploy':
                    $this->cloudDoctor->deploy();
                    break;
                case 'purge':
                    $this->cloudDoctor->purge();
                    break;
                default:
                    foreach ($this->getApplicationSpecificCommands() as $command) {
                        $flag = str_replace(" ", "-", strtolower($command->getCommandName()));
                        if ($flag == $name) {
                            echo "Running {$command->getCommandName()}...\n";
                            if ($values->offsetExists($flag)) {
                                $command->action();
                            }
                            echo "Completed running {$command->getCommandName()}\n\n";
                        }
                    }
            }
        }
    }
}