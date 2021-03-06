<?php
namespace CloudDoctor;

use CLIOpts\CLIOpts;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\CliMenuBuilder;

class Cli
{

    /** @var CliMenuBuilder */
    protected $menu;

    /** @var CloudDoctor */
    protected $cloudDoctor;

    public function __construct(CloudDoctor $cloudDoctor = null)
    {
        if ($cloudDoctor) {
            $this->cloudDoctor = $cloudDoctor;
        } else {
            $this->cloudDoctor = new CloudDoctor();
        }

        $this->assertFromFiles();
        
        $this->menu = new CliMenuBuilder();
        $this->menu->setBackgroundColour('black');
        $this->menu->setForegroundColour('blue');
        $this->menu->setTitle($this->cloudDoctor->getName());

        $scope = $this;

        $this->menu->addItem('Deploy (Assert what we want in the cloud)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->deploy();
            $menu->redraw();
        });

        $this->menu->addItem('Show (Show the environment installed)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->show();
            $menu->redraw();
        });

        $this->menu->addItem('Purge (Destroy Everything!)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->purge();
            $menu->redraw();
        });

        $this->menu->addItem('DNS (Update DNS Records)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->deploy_dnsEnforce();
            $menu->redraw();
        });

        $this->menu->addItem('Reassert Swarm', function(CliMenu $menu) use ($scope) {
           /** @var CloudDoctor $scope */
           $scope->cloudDoctor->deploy_swarmify();
           $menu->redraw();
        });

        $this->menu->addItem('Download Certificates (Force-pull the Swarm Certs)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->downloadCerts();
            $menu->redraw();
        });

        $this->menu->addItem('Update Metadata (Tags, Groups, etc)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->updateMetaData();
            $menu->redraw();
        });

        $this->menu->addItem('Update Stacks (Docker Swarm Stacks!)', function (CliMenu $menu) use ($scope) {
            /** @var CloudDoctor $scope */
            $scope->cloudDoctor->updateStacks();
            $menu->redraw();
        });
    }

    public function assertFromFiles()
    {
        $this->cloudDoctor->assertFromFile(
            "cloud-definition.yml",
            "cloud-definition.override.yml",
            "cloud-definition.automation-override.yml"
        );
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

    private function checkForArguments()
    {
        $arguments = "
            Usage: {self} [options]
            -s --show Show the stack as CloudDoctor understands it
            -D --deploy Run Deployment
            --download-certs Download SSL certificates from master
            --update-stacks Update Docker Swarm stackfiles
            --update-meta Update metadata stored with providers
            --dns Update DNS Records
            --swarm (Re-)Assert Docker Swarm
            --purge Purge everything deployed. Danger will robinson!
            --scale Check scalings
            --watch Wait for changes to settings files and run --scale when it changes!
            -c --connect SSH into a manager instance
            ";
        $arguments.="-h --help Show this help\n";

        $values = CLIOpts::run($arguments);

        return $values;
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
                case 'dns':
                    $this->cloudDoctor->deploy_dnsEnforce();
                    break;
                case 'swarm':
                    $this->cloudDoctor->deploy_swarmify();
                    break;
                case 'scale':
                    $this->cloudDoctor->scale();
                    break;
                case 'watch':
                    $this->cloudDoctor->watch($this);
                    break;
                case 'download-certs':
                    $this->cloudDoctor->downloadCerts();
                    break;
                case 'update-meta':
                    $this->cloudDoctor->updateMetaData();
                    break;
                case 'update-stacks':
                    $this->cloudDoctor->updateStacks();
                    break;
                case 'connect':
                    $this->cloudDoctor->terminalConnect();
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
