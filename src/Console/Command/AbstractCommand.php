<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command;

use Gmo\Common\Console\ContainerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class AbstractCommand extends Command implements CompletionAwareInterface
{
    use ContainerAwareTrait;

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var string|null */
    protected $host;
    /** @var int|null */
    protected $port;
    /** @var LoggerInterface */
    protected $logger;
    /** @var VarCloner */
    private $cloner;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->logger = new NullLogger();
    }

    public function completeOptionValues($optionName, CompletionContext $context)
    {
        return false;
    }

    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        return false;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($input->hasOption('host')) {
            $this->host = $input->getOption('host');
        }
        if ($input->hasOption('port')) {
            $this->port = $input->getOption('port');
        }

        $this->logger = new ConsoleLogger($output);
        $this->output->getFormatter()->setStyle('warn', new OutputFormatterStyle('red'));
    }

    protected function initializeFromContext(CompletionContext $context)
    {
        $words = $context->getWords();
        $words = array_filter($words);
        $this->mergeApplicationDefinition();

        $input = new ArgvInput($words, $this->getDefinition());

        $this->initialize($input, new NullOutput());
    }

    public function getConsoleWidth()
    {
        return (new Terminal())->getWidth();
    }

    /**
     * @inheritdoc
     *
     * @return $this
     */
    public function setName($name)
    {
        return parent::setName($name);
    }

    /**
     * @inheritdoc
     *
     * @return $this
     */
    public function setDescription($description)
    {
        return parent::setDescription($description);
    }

    /**
     * @inheritdoc
     *
     * @return $this
     */
    public function addArgument($name, $mode = null, $description = '', $default = null)
    {
        return parent::addArgument($name, $mode, $description, $default);
    }

    /**
     * @inheritdoc
     *
     * @return $this
     */
    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        return parent::addOption($name, $shortcut, $mode, $description, $default);
    }

    /**
     * Calls an existing command
     *
     * @param OutputInterface $output
     * @param string          $name A command name or a command alias
     * @param array           $args
     *
     * @throws \Throwable
     *
     * @return int The command exit code
     */
    protected function callCommand(OutputInterface $output, $name, $args = [])
    {
        $args = array_merge(['command' => $name], $args);

        return $this->getApplication()->doRun(new ArrayInput($args), $output);
    }

    /**
     * Returns a string representation of the variable.
     *
     * @param mixed $var
     *
     * @return string
     */
    protected function dumpVar($var)
    {
        $data = $this->getCloner()->cloneVar($var);

        $res = fopen('php://memory', 'r+b');
        $dumper = new CliDumper($res);
        $dumper->setColors($this->output->isDecorated());
        $dumper->dump($data);
        $str = stream_get_contents($res, -1, 0);
        fclose($res);

        return $str;
    }

    /**
     * @return VarCloner
     */
    private function getCloner()
    {
        if (!$this->cloner) {
            $this->cloner = new VarCloner();
        }

        return $this->cloner;
    }
}
