<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AutostartMarking extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'platypus:autostart_marking';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check if assignments are due to automatically start the marking process.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		AssignmentControlPanelController::autostartMarking();
		
		ValueStore::setTime(StoredValue::lastAutostartMarking, Carbon::now());
		
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}

}
