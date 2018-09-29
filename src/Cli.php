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

    private function checkForArguments()
    {
        $arguments = "
            Usage: {self} [options]
            -s --show Show the stack as CloudDoctor understands it
            -D --deploy Run Deployment
            --purge Purge everything deployed. Danger will robinson!
            ";
        $arguments.="-h --help Show this help\n";

        $values = CLIOpts::run($arguments);

        return $values;
    }

    public function __construct(CloudDoctor $cloudDoctor = null)
    {
        if($cloudDoctor){
            $this->cloudDoctor = $cloudDoctor;
        }else{
            $this->cloudDoctor = new CloudDoctor();
        }

        $this->cloudDoctor->assertFromFile(
            "cloud-definition.yml",
            "cloud-definition.override.yml",
            "cloud-defintion.automation-override.yml"
        );
        
        $this->menu = new CliMenuBuilder();
        $this->menu->setBackgroundColour('black');
        $this->menu->setForegroundColour('blue');
        $this->menu->setTitle($this->cloudDoctor->getName());

        $scope = $this;
        $this->menu->addItem('Deploy', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->deploy();
            $menu->redraw();
        });
        $this->menu->addItem('Show', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->show();
            $menu->redraw();
        });
        $this->menu->addItem('Purge', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->purge();
            $menu->redraw();
        });
    }

    public function run()
    {
        $values = $this->checkForArguments();
        
        if ($values->count()) {
            $this->runNonInteractive();
        } else {
            $this->runInteractive();
        }
    }

    private function runInteractive()
    {
        $this->menu->build()->open();
    }

    private function runNonInteractive()
    {
        $values = $this->checkForArguments();
        // non-interactive mode
        foreach ($values as $name => $value) {
            switch ($name) {
                case 'show':
                    $this->cloudDoctor->show();
                    break;
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