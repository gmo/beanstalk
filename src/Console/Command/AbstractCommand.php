<?php
namespace GMO\Beanstalk\Console\Command;

use GMO\Beanstalk\BeanstalkServiceProvider;
use GMO\Console\ContainerAwareCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class AbstractCommand extends ContainerAwareCommand {

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->logger = new ConsoleLogger($output);
		$this->output = $output;
		$this->output->getFormatter()->setStyle('warn', new OutputFormatterStyle('red'));
	}

	public function getConsoleWidth() {
		$app = $this->getApplication();
		if (!$app) {
			return null;
		}
		$dimensions = $app->getTerminalDimensions();
		return $dimensions[0];
	}

	protected function getDefaultContainer() {
		return parent::getDefaultContainer()
			->registerService(new BeanstalkServiceProvider());
	}

	/**
	 * @inheritdoc
	 * @return $this
	 */
	public function setName($name) {
		return parent::setName($name);
	}

	/**
	 * @inheritdoc
	 * @return $this
	 */
	public function setDescription($description) {
		return parent::setDescription($description);
	}

	/**
	 * @inheritdoc
	 * @return $this
	 */
	public function addArgument($name, $mode = null, $description = '', $default = null) {
		return parent::addArgument($name, $mode, $description, $default);
	}

	/**
	 * @inheritdoc
	 * @return $this
	 */
	public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null) {
		return parent::addOption($name, $shortcut, $mode, $description, $default);
	}

	/**
	 * Returns a string representation of the variable.
	 *
	 * Symfony VarDumper is used if installed, else print_r is used.
	 *
	 * @param mixed $var
	 *
	 * @return string
	 */
	protected function dumpVar($var) {
		if (!class_exists('\Symfony\Component\VarDumper\VarDumper')) {
			return print_r($var, true);
		}

		$data = $this->getCloner()->cloneVar($var);

		$res = fopen('php://temp', 'r+');
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
	private function getCloner() {
		if (!$this->cloner) {
			$this->cloner = new VarCloner();
		}

		return $this->cloner;
	}

	/** @var LoggerInterface */
	protected $logger;
	/** @var OutputInterface */
	private $output;
	/** @var VarCloner */
	private $cloner;
}
